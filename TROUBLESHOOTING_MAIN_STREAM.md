# Troubleshooting Main Stream di Live Preview

## Masalah Umum

### 1. Main Stream Tidak Muncul (Layar Hitam)

**Penyebab:**
- Main stream URL tidak valid
- NVR membatasi concurrent main streams
- Bandwidth tidak cukup
- go2rtc tidak bisa connect ke RTSP main stream

**Solusi:**
1. Buka browser console (F12) dan lihat error
2. Cek Laravel log: `tail -f storage/logs/laravel.log`
3. Cek go2rtc log: `tail -f storage/logs/go2rtc.log`
4. Test RTSP URL manual dengan VLC

**Cek di Browser Console:**
```javascript
// Seharusnya muncul:
Stream 278 register failed: [error message]
// Atau
Stream 278 register error: [error]
```

### 2. Main Stream Timeout Lalu Fallback ke Sub

**Penyebab:**
- Main stream membutuhkan waktu lebih lama untuk connect
- NVR lambat merespons
- Timeout 10 detik terlalu pendek

**Solusi:**
Edit `resources/views/live/index.blade.php` line ~565:
```javascript
// Ubah timeout dari 10000 (10 detik) ke 20000 (20 detik)
}, 20000);
```

### 3. Main Stream Berjalan Tapi Patah-patah

**Penyebab:**
- Bandwidth tidak cukup
- Terlalu banyak main stream concurrent
- NVR overload

**Solusi:**
- Tutup main stream lain yang tidak digunakan
- Gunakan main stream hanya untuk 1-2 kamera sekaligus
- Upgrade bandwidth network

### 4. Backend Return Error "Failed to start stream"

**Cek Permission:**
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->canLiveView(278);  // Ganti 278 dengan camera ID
```

**Cek Camera Status:**
```bash
php artisan tinker
>>> $camera = App\Models\Camera::find(278);
>>> $camera->status;  // Harus 'online'
>>> $camera->is_active;  // Harus true
>>> $camera->getMainStreamUrl();  // Cek URL
```

### 5. go2rtc Tidak Bisa Connect ke RTSP

**Test Manual:**
```bash
# Test dengan curl
curl "http://127.0.0.1:1984/api/streams"

# Test RTSP dengan FFmpeg
ffmpeg -rtsp_transport tcp -i "rtsp://admin:password@172.16.0.1:554/Streaming/Channels/101" -frames:v 1 test.jpg
```

**Cek go2rtc log:**
```bash
tail -f storage/logs/go2rtc.log | grep "error\|timeout\|failed"
```

## Debug Mode

Untuk debugging lebih detail, tambahkan logging di `startStream`:

```javascript
async startStream(cameraId, streamType = 'sub') {
    console.log(`[DEBUG] Starting stream ${cameraId} type: ${streamType}`);
    
    // ... existing code ...
    
    if (streamType === 'main') {
        console.log(`[DEBUG] Registering main stream for camera ${cameraId}`);
        try {
            const res = await fetch(`/live/stream/${cameraId}`, {
                method: 'POST', headers: HDR,
                body: JSON.stringify({ stream_type: streamType })
            });
            const data = await res.json();
            console.log(`[DEBUG] Backend response:`, data);
            
            if (!data.success) {
                console.error(`[ERROR] Registration failed:`, data.message);
                // ...
            }
        } catch (e) {
            console.error(`[ERROR] Fetch failed:`, e);
            // ...
        }
    }
    
    console.log(`[DEBUG] Creating video element with stream: ${streamName}`);
    this.createVideoElement(cameraId, streamName);
}
```

## Checklist Verifikasi

- [ ] go2rtc running di port 1984
- [ ] Camera status = 'online'
- [ ] Camera is_active = true
- [ ] User punya permission live view
- [ ] Main stream URL valid (test dengan VLC)
- [ ] NVR tidak membatasi concurrent streams
- [ ] Bandwidth cukup (minimal 4 Mbps per main stream)
- [ ] Browser console tidak ada error
- [ ] Laravel log tidak ada error

## Konfigurasi Optimal

**Untuk NVR dengan banyak kamera (>50):**
- Gunakan sub stream untuk monitoring grid
- Main stream hanya untuk focus mode (1 kamera)
- Batasi concurrent main stream maksimal 3-5

**Untuk NVR dengan kamera sedikit (<20):**
- Bisa gunakan main stream untuk grid fullscreen
- Tapi tetap monitor bandwidth

## Contact

Jika masalah masih berlanjut, kumpulkan informasi berikut:
1. Browser console log (F12 → Console)
2. Laravel log: `storage/logs/laravel.log` (100 baris terakhir)
3. go2rtc log: `storage/logs/go2rtc.log` (100 baris terakhir)
4. Camera ID yang bermasalah
5. Screenshot error

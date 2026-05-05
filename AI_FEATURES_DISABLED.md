# AI Features Disabled

AI features have been disabled to reduce system load and resource usage.

## What Was Disabled

### 1. **AI Scheduled Job**
- File: `routes/console.php`
- `ProcessPlateRecognitionJob` is commented out
- No longer runs every 5 seconds

### 2. **AI Routes**
- File: `routes/web.php`
- All AI-related routes are commented out:
  - `/ai/cameras` - AI Camera Assignment
  - `/ai/detections` - Plate Detection Logs
  - `/ai/watchlist` - Watchlist Management
  - `/ai/incidents` - Incident Timeline
  - `/ai/reports` - AI Reports

### 3. **AI Menu (Sidebar)**
- File: `resources/views/layouts/app.blade.php`
- "AI Analytics" section is commented out
- Menu items hidden from sidebar

### 4. **AI Microservice**
- Python service stopped
- No longer consuming resources
- EasyOCR model not loaded

## System Impact

**Before (AI Enabled):**
- Python process: ~2-3 GB RAM
- Queue worker processing AI jobs every 5 seconds
- EasyOCR model loaded in memory
- Continuous frame capture from cameras

**After (AI Disabled):**
- No Python process
- No AI-related queue jobs
- Reduced CPU usage
- Reduced memory usage
- Reduced network traffic (no frame capture)

## How to Re-enable AI Features

If you need AI features in the future, follow these steps:

### Step 1: Uncomment Scheduled Job

Edit `routes/console.php`:
```php
// Remove the comment slashes:
Schedule::job(new ProcessPlateRecognitionJob)
    ->everyFiveSeconds()
    ->withoutOverlapping();
```

### Step 2: Uncomment AI Routes

Edit `routes/web.php` (around line 197):
```php
// Remove the /* and */ comment blocks around AI routes
Route::middleware(['role:superadmin'])->prefix('ai')->name('ai.')->group(function () {
    // ... all AI routes
});
```

### Step 3: Uncomment AI Menu

Edit `resources/views/layouts/app.blade.php` (around line 125):
```php
// Remove the {{-- and --}} comment blocks around AI menu section
<div class="pt-4 pb-2">
    <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">AI Analytics</p>
</div>
// ... all AI menu items
```

### Step 4: Start AI Microservice

```bash
cd ai-service
python main.py
```

Or double-click `ai-service/start-ai-service.bat`

### Step 5: Clear Caches

```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

### Step 6: Restart Services

```bash
# Restart queue worker
php artisan queue:restart

# Restart scheduler (if using schedule:work)
# Stop and start again
```

## Files Modified

- `routes/console.php` - Scheduled job commented
- `routes/web.php` - AI routes commented
- `resources/views/layouts/app.blade.php` - AI menu commented
- `AI_FEATURES_DISABLED.md` - This documentation (new)

## Database Tables (Not Deleted)

AI-related tables are still in the database but not being used:
- `ai_camera_settings`
- `plate_detection_logs`
- `watchlist_plates`
- `ai_incidents`

Data is preserved in case you want to re-enable AI later.

## Configuration (Not Changed)

AI configuration in `.env` and `config/monc.php` is still present:
- `AI_SERVICE_URL=http://127.0.0.1:8100`
- `AI_DEFAULT_CONFIDENCE=50`
- `AI_DEFAULT_INTERVAL=5`

No need to change these unless you want to.

---

**Date Disabled:** 2026-05-04  
**Reason:** Reduce system load, AI features not currently needed  
**Can be re-enabled:** Yes, follow steps above

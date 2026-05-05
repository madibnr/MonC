<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Camera;
use App\Models\Nvr;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = [
            ['name' => 'Gedung Utama', 'code' => 'GU', 'address' => 'Jl. Utama No. 1', 'description' => 'Gedung kantor utama Bea Cukai'],
            ['name' => 'Gedung Pemeriksaan', 'code' => 'GP', 'address' => 'Jl. Utama No. 2', 'description' => 'Gedung pemeriksaan barang'],
            ['name' => 'Gudang Penimbunan', 'code' => 'GPN', 'address' => 'Jl. Pelabuhan No. 5', 'description' => 'Gudang penimbunan sementara'],
            ['name' => 'Pos Jaga', 'code' => 'PJ', 'address' => 'Jl. Pelabuhan No. 1', 'description' => 'Pos jaga dan gerbang utama'],
        ];

        $ipSegments = ['192.168.1', '192.168.2', '192.168.3', '192.168.4'];

        foreach ($buildings as $index => $buildingData) {
            $building = Building::updateOrCreate(
                ['code' => $buildingData['code']],
                $buildingData
            );

            $nvr = Nvr::updateOrCreate(
                ['ip_address' => $ipSegments[$index].'.64'],
                [
                    'building_id' => $building->id,
                    'name' => 'NVR '.$building->name,
                    'ip_address' => $ipSegments[$index].'.64',
                    'port' => 554,
                    'username' => 'admin',
                    'password' => 'admin12345',
                    'model' => 'Hikvision DS-8664N',
                    'total_channels' => 64,
                    'status' => 'offline',
                    'description' => 'NVR untuk '.$building->name,
                    'is_active' => true,
                ]
            );

            // Create 64 cameras per NVR
            for ($ch = 1; $ch <= 64; $ch++) {
                Camera::updateOrCreate(
                    ['nvr_id' => $nvr->id, 'channel_no' => $ch],
                    [
                        'building_id' => $building->id,
                        'name' => $building->code.'-CAM-'.str_pad($ch, 2, '0', STR_PAD_LEFT),
                        'location' => $building->name.' - Channel '.$ch,
                        'status' => 'offline',
                        'is_active' => true,
                        'sort_order' => $ch,
                    ]
                );
            }
        }
    }
}

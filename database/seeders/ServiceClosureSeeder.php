<?php

namespace Database\Seeders;

use App\Models\ServiceClosure;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceClosureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'service_type' => 'icv',
                'permit_type' => null,
                'is_closed' => false,
                'closure_reason' => null,
            ],
            [
                'service_type' => 'izin',
                'permit_type' => null,
                'is_closed' => false,
                'closure_reason' => null,
            ],
            [
                'service_type' => 'izin',
                'permit_type' => 'izin_baru',
                'is_closed' => false,
                'closure_reason' => null,
            ],
            [
                'service_type' => 'izin',
                'permit_type' => 'izin_perpanjangan',
                'is_closed' => false,
                'closure_reason' => null,
            ],
            [
                'service_type' => 'account',
                'permit_type' => null,
                'is_closed' => false,
                'closure_reason' => null,
            ],
        ];

        foreach ($services as $service) {
            ServiceClosure::updateOrCreate(
                [
                    'service_type' => $service['service_type'],
                    'permit_type' => $service['permit_type'],
                ],
                [
                    'is_closed' => $service['is_closed'],
                    'closure_reason' => $service['closure_reason'],
                ]
            );
        }

        $this->command->info('Service closures seeded successfully!');
    }
}

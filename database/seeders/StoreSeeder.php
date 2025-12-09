<?php

namespace Database\Seeders;

use App\Enums\StoreGroupEnum;
use App\Enums\StoreStatusEnum;
use App\Enums\StoreTypeEnum;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();
        $stores = [];

        $franchisees = DB::table('franchisees')->get();

        // Load location config
        $provinces = config('locations.provinces');

        // Flatten province, city, barangay combinations
        $locationPool = collect();
        foreach ($provinces as $province => $provinceData) {
            foreach ($provinceData['cities'] ?? [] as $city => $cityData) {
                foreach ($cityData['barangays'] ?? [] as $barangay) {
                    $locationPool->push([
                        'province' => $province,
                        'city' => $city,
                        'barangay' => $barangay,
                    ]);
                }
            }
        }

        foreach ($franchisees as $franchisee) {
            $storeCount = random_int(1, 5);

            for ($i = 0; $i < $storeCount; $i++) {
                $location = $locationPool->random();

                $stores[] = [
                    'franchisee_id' => $franchisee->id,
                    'jbs_name' => $faker->company.' Store',
                    'cluster_code' => strtoupper(Str::random(2)).'-'.random_int(100, 999),
                    'store_type' => StoreTypeEnum::from(array_rand(StoreTypeEnum::toArray())),
                    'store_group' => StoreGroupEnum::from(array_rand(StoreGroupEnum::toArray())),
                    'store_status' => StoreStatusEnum::from(array_rand(StoreStatusEnum::toArray())),
                    'store_code' => strtoupper(Str::random(4)).'-'.random_int(1000, 9999),
                    'region' => collect(['LUZ', 'VIS', 'MIN'])->random(),
                    'area' => $faker->city,
                    'district' => $faker->city,
                    'store_province' => $location['province'],
                    'store_city' => $location['city'],
                    'store_barangay' => $location['barangay'],
                    'is_draft' => false,
                    'created_by_id' => $franchisee->created_by_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('stores')->insert($stores);
    }
}

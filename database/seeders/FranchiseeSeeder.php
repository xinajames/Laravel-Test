<?php

namespace Database\Seeders;

use App\Enums\FranchiseeStatusEnum;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FranchiseeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();

        $franchisees = [];

        $createdBy = User::where('email', 'admin@jfm.test')->first();
        for ($i = 0; $i < 15; $i++) {
            $franchisees[] = [
                'franchisee_code' => strtoupper(Str::random(4)).'-'.random_int(1000, 9999),
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'corporation_name' => $faker->company,
                'residential_address_province' => $faker->state,
                'residential_address_city' => $faker->city,
                'residential_address_barangay' => 'Barangay '.Str::random(5),
                'residential_address_street' => $faker->streetAddress,
                'residential_address_postal' => $faker->numerify('####'),
                'email' => $faker->unique()->safeEmail,
                'contact_number' => $faker->phoneNumber,
                'status' => FranchiseeStatusEnum::Active()->value,
                'is_draft' => false,
                'created_by_id' => $createdBy?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('franchisees')->insert($franchisees);
    }
}

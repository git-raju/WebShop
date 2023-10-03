<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $users = User::factory()->count(1)->create();
    }
}

// php artisan make:seeder UserSeeder  [artisan command for creating a seeder class]
// php artisan db:seed --class=UserSeeder [run the seeder class]

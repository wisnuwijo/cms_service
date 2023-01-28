<?php

use Illuminate\Database\Seeder;
use App\Model\Role;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::insert([
            [
                "id" => Str::uuid(),
                "name" => "administrator",
                "created_at" => now()
            ],
            [
                "id" => Str::uuid(),
                "name" => "employee",
                "created_at" => now()
            ]
        ]);
    }
}

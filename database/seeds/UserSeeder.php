<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Model\Role;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::first();
        User::insert([
            [
                "id" => Str::uuid(),
                "role_id" => $role->id,
                "name" => "administrator",
                "username" => "administrator",
                "password" => bcrypt("123123123"),
                'api_token' => Str::random(80),
                "created_at" => now()
            ]
        ]);
    }
}

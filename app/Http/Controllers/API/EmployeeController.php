<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Model\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function index(Request $req)
    {
        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got employee list request');
        
        // return all employee except the active user
        $user = User::select([
            "users.id",
            "roles.name as role",
            "users.role_id",
            "users.name",
            "users.username",
            "users.created_at"
        ])
        ->leftJoin("roles","users.role_id","roles.id")
        ->where("users.id","!=", $req->user()->id)
        ->orderBy('users.created_at','DESC')
        ->get();
        
        Log::info(now() .' '. $uuid . ' Success return employee list');
        return response([
            "message" => "Success",
            "data" => [
                "employee" => $user
            ]
        ]);
    }

    public function role_list(Request $req)
    {
        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got role list request');
        
        $role = Role::all();
        
        Log::info(now() .' '. $uuid . ' Success return role list');
        return response([
            "message" => "Success",
            "data" => [
                "role" => $role
            ]
        ]);
    }

    public function detail(Request $req)
    {
        $req->validate([
            "employee_id" => "required|exists:users,id"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got employee detail request');
        
        $user = User::select([
            "users.id",
            "roles.name as role",
            "users.role_id",
            "users.name",
            "users.username",
            "users.created_at"
        ])
        ->leftJoin("roles","users.role_id","roles.id")
        ->where("users.id", $req->employee_id)
        ->first();
        
        Log::info(now() .' '. $uuid . ' Success return employee detail');
        return response([
            "message" => "Success",
            "data" => [
                "employee" => $user
            ]
        ]);
    }

    public function create(Request $req)
    {
        $req->validate([
            "role_id" => "required|exists:roles,id",
            "name" => "required",
            "username" => "required|unique:users,username",
            "password" => "required|min:8"
        ]);
        
        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got employee create request');
        
        // replace password with bcrypt
        $req['password'] = bcrypt($req->password);
        // add api token
        $req['api_token'] = Str::random(80);
        // add created_at timestamp
        $req['created_at'] = now();
        // add id
        $req['id'] = $uuid;

        Log::info(now() .' '. $uuid . ' Insert to users table, data : ' . json_encode($req->all()));
        $insert_employee = User::insert($req->all());
        
        if (!$insert_employee) {
            Log::info(now() .' '. $uuid . ' Failed insert to users table');
            Log::info(now() .' '. $uuid . ' Return failed');

            return response([
                "message" => "Failed to create employee, please try again"
            ], 500);
        }

        Log::info(now() .' '. $uuid . ' Success insert to users table');
        Log::info(now() .' '. $uuid . ' Return success');
        return response([
            "message" => "Success"
        ]);
    }

    public function update(Request $req)
    {
        $req->validate([
            "id" => "required|exists:users,id",
            "role_id" => "required|exists:roles,id",
            "name" => "required",
            "username" => "required",
            "password" => "nullable|min:8"
        ]);
        
        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got employee update request');
        
        // replace password with bcrypt
        if (isset($req['password'])) {
            Log::info(now() .' '. $uuid . ' Request contain password, convert password to bcrypt');
            $req['password'] = bcrypt($req->password);
        }

        // add created_at timestamp
        $req['updated_at'] = now();

        Log::info(now() .' '. $uuid . ' Update to users table, data : ' . json_encode($req->all()));
        $update_employee = User::where("id", $req['id'])->update($req->all());
        
        if (!$update_employee) {
            Log::info(now() .' '. $uuid . ' Failed update to users table');
            Log::info(now() .' '. $uuid . ' Return failed');

            return response([
                "message" => "Failed to update employee, please try again"
            ], 500);
        }

        Log::info(now() .' '. $uuid . ' Success update to users table');
        Log::info(now() .' '. $uuid . ' Return success');
        return response([
            "message" => "Success"
        ]);
    }

    public function delete(Request $req)
    {
        $req->validate([
            "id" => "required|exists:users,id"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got employee delete request : ' . json_encode($req->all()));
        $delete_employee = User::where("id", $req['id'])->forceDelete();
        
        if (!$delete_employee) {
            Log::info(now() .' '. $uuid . ' Failed to delete the user');
            Log::info(now() .' '. $uuid . ' Return failed');

            return response([
                "message" => "Failed to delete employee, please try again"
            ], 500);
        }

        Log::info(now() .' '. $uuid . ' Success delete user');
        Log::info(now() .' '. $uuid . ' Return success');
        return response([
            "message" => "Success"
        ]);
    }
}

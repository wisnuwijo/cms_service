<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Auth;
use Hash;
use App\User;

class LoginController extends Controller
{
    public function login(Request $req)
    {
        $req->validate([
            "username" => "required|exists:users,username",
            "password" => "required"
        ]);
        
        $username = $req->username;
        $password = $req->password;

        $user = User::select(["users.*","roles.name as role_name"])
            ->leftJoin("roles","users.role_id","roles.id")
            ->where("username", $username)
            ->first();
            
        if (Hash::check($password, $user->password)) {
            // auth valid, update api token
            $newToken = Str::random(80);
            User::where("username", $username)->update([
                "api_token" => $newToken
            ]);

            $user->api_token = $newToken;

            return response([
                "message" => "Success",
                "code" => 200,
                "data" => [
                    "user" => $user
                ]
            ],401);
        } else {
            // auth invalid
            return response([
                "message" => "Failed, wrong username or password",
                "code" => 401
            ],401);
        }
    }
}

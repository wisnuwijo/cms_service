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
            $new_token = Str::random(80);
            User::where("username", $username)->update([
                "api_token" => $new_token
            ]);

            $user->api_token = $new_token;

            return response([
                "message" => "Success",
                "data" => [
                    "user" => $user
                ]
            ]);
        } else {
            // auth invalid
            return response([
                "message" => "Failed, wrong username or password"
            ],401);
        }
    }

    public function change_password(Request $req)
    {
        $req->validate([
            "new_password" => "required|min:8"
        ]);

        $update_password = User::where('id', $req->user()->id)
                            ->update([
                                "password" => bcrypt($req->new_password)
                            ]);
        
        if ($update_password) return response([
            "message" => "Success, password updated"
        ],200);

        return response([
            "message" => "Failed, something went wrong"
        ],500);
    }
}

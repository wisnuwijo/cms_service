<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Client;
use App\Model\Transaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index(Request $req)
    {
        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got customer list request');
        $customer = Client::select(["id","phone_number","name","address","created_at"])->orderBy('created_at','DESC')->get();
        
        Log::info(now() .' '. $uuid . ' Success return customer list');
        return response([
            "message" => "Success",
            "data" => [
                "customer" => $customer
            ]
        ]);
    }

    public function detail(Request $req)
    {
        $req->validate([
            "customer_id" => "required|exists:clients,id"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got customer detail request : ' . json_encode($req->all()));

        $customer = Client::select(["id","phone_number","name","address","created_at"])
        ->where("id", $req->customer_id)
        ->first();

        $transactions = Transaction::where("client_id", $req->customer_id)->orderBy('created_at','DESC')->get();
        Log::info(now() .' '. $uuid . ' Success return customer detail');
        return response([
            "message" => "Success",
            "data" => [
                "customer" => $customer,
                "transaction" => $transactions
            ]
        ]);
    }

    public function create(Request $req)
    {
        $req->validate([
            "name" => "required",
            "phone_number" => "required|int|unique:clients,phone_number",
            "address" => "required"
        ]);

        $uuid = Str::uuid();
        $req['id'] = (string) $uuid;
        $req['created_at'] = now();
        Log::info(now() .' '. $uuid . ' Got customer create request : ' . json_encode($req->all()));

        $insert_customer = Client::insert($req->all());
        if (!$insert_customer) {
            Log::info(now() .' '. $uuid . ' Failed insert customer ');
            return response([
                "message" => "Failed to create customer, please try again"
            ], 500);
        }

        Log::info(now() .' '. $uuid . ' Success insert customer ');
        return response([
            "message" => "Success, customer created"
        ]);
    }

    public function update(Request $req)
    {
        $req->validate([
            "id" => "required|exists:clients,id",
            "name" => "required",
            "phone_number" => "required|int",
            "address" => "required"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got customer update request : ' . json_encode($req->all()));

        Log::info(now() .' '. $uuid . ' Validating phone number ...');
        // check phone number, make sure the phone number is not taken by other account
        $check_phone_number = Client::where([
            ["phone_number", $req->phone_number],
            ["id", "!=", $req->id]
        ])
        ->first();

        if (isset($check_phone_number)) {
            Log::info(now() .' '. $uuid . ' Phone number '. $req->phone_number .' already been taken by other account. Update failed');
            return response([
                "message" => "Failed, phone number is taken",
                "errors" => [
                    "phone_number" => [
                        "Phone number is already registered by other account. Please use other phone number"
                    ]
                ]
            ], 422);
        }

        Log::info(now() .' '. $uuid . ' Phone number is unique, proceeding the request ...');
        $update_customer = Client::where("id", $req->id)->update($req->all());

        if (!$update_customer) {
            Log::info(now() .' '. $uuid . ' Failed update customer ');
            return response([
                "message" => "Failed to update customer, please try again"
            ], 500);
        }

        Log::info(now() .' '. $uuid . ' Success update customer ');
        return response([
            "message" => "Success, customer updated"
        ]);
    }

    public function delete(Request $req)
    {
        $req->validate([
            "id" => "required|exists:clients,id"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got customer delete request : ' . json_encode($req->all()));
        $delete_customer = Client::find($req->id)->forceDelete();

        if (!$delete_customer) {
            Log::info(now() .' '. $uuid . ' Failed delete customer ');
            return response([
                "message" => "Failed to delete customer, please try again"
            ], 500);
        }
        
        Log::info(now() .' '. $uuid . ' Success delete customer');
        return response([
            "message" => "Success, customer created"
        ]);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Client;
use App\Model\Transaction;
use App\Model\TransactionDetail;
use App\Model\Delivery;
use App\Model\Invoice;
use App\Model\InvoicePayment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(Request $req)
    {
        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got transaction list request');
        $transaction = Transaction::select([
            "transactions.*",
            "clients.name as client_name",
            "clients.phone_number as client_phone_number"
        ])
        ->leftJoin('clients','transactions.client_id','clients.id')
        ->get();
        
        Log::info(now() .' '. $uuid . ' Success return transaction list');
        return response([
            "message" => "Success",
            "data" => [
                "transaction" => $transaction
            ]
        ]);
    }

    public function detail(Request $req)
    {
        $req->validate([
            "transaction_id" => "required|exists:transactions,id"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got transaction detail request : ' . json_encode($req->all()));

        $transaction = Transaction::select([
            "transactions.*",
            "clients.name as client_name",
            "clients.phone_number as client_phone_number"
        ])
        ->leftJoin('clients','transactions.client_id','clients.id')
        ->where("transactions.id", $req->transaction_id)
        ->first();

        $transaction_detail = TransactionDetail::where("transaction_id", $req->transaction_id)->get();
        $delivery = Delivery::where("transaction_id", $req->transaction_id)->first();
        
        $invoice = Invoice::where("transaction_id", $req->transaction_id)->first();
        $invoice_payment_history = InvoicePayment::where("transaction_id", $req->transaction_id)->orderBy('created_at','DESC')->get();
        $invoice->payment_history = $invoice_payment_history;

        Log::info(now() .' '. $uuid . ' Success return transaction detail');
        return response([
            "message" => "Success",
            "data" => [
                "transaction" => $transaction,
                "transaction_detail" => $transaction_detail,
                "delivery" => $delivery,
                "invoice" => $invoice
            ]
        ]);
    }
}

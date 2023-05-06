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
use App\Model\FinishedOrderNotification;
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

    public function paymentConfirmation(Request $req)
    {
        return response([
            "message" => "Success",
            "data" => [
                "invoice_payments" => InvoicePayment::orderBy('created_at','desc')->get()
            ]
        ]);
    }

    public function sendMessage($uuid, $to, $invoice, $status1, $status2)
    {
        $logPrefix = now() .' '. $uuid . ' sendMessage() ';
        Log::info($logPrefix . "Send whatsapp message to client: " . $to);

        $whatsappEndpoint = env('WHATSAPP_MESSAGE_ENDPOINT');
        $whatsappToken = env('WHATSAPP_TOKEN');

        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $to,
            "type" => "template",
            "template" => [
                "name" => "payment_confirmed_v1",
                "language" => [
                    "code" => "id",
                    "policy" => "deterministic"
                ],
                "components"=> [
                    [
                        "type" => "body",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $invoice
                            ],
                            [
                                "type" => "text",
                                "text" => $status1,
                            ],
                            [
                                "type" => "text",
                                "text" => $status2
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = null;
        try {
            $client = new \GuzzleHttp\Client();
            $headers = ['Authorization' => 'Bearer ' . $whatsappToken];
            $payload = [
                "headers" => $headers,
                "form_params" => $payload
            ];

            Log::info(now() ." ". $uuid . " sendMessage() Send request to whatsapp");
            Log::info(now() ." ". $uuid . " sendMessage() Endpoint: " . $whatsappEndpoint);
            Log::info(now() ." ". $uuid . " sendMessage() Payload: " . json_encode($payload));
            $response = $client->request('POST', $whatsappEndpoint, $payload);

            $content = json_decode($response->getBody()->getContents());
            Log::info(now() ." ". $uuid . " sendMessage() Response: " . json_encode($content));
        } catch (ClientException $e) {
            $response = Psr7\Message::toString($e->getResponse());
            Log::info(now() ." ". $uuid . " sendMessage() Error: " . $response);
        }

        return $response;
    }

    public function verifyPayment(Request $req)
    {
        $req->validate([
            "invoice_payment_id" => "required|exists:invoice_payments,id",
            "status" => "required|in:VALID,INVALID"
        ]);

        $status1 = "BELUM";
        $status2 = "BELUM LUNAS";
        $statusInvoice = "unpaid";
        $statusTrx = "unpaid";

        if ($req->status == "VALID") {
            $status1 = "SUDAH";
            $status2 = "SUDAH LUNAS";

            $status = "payment_valid";
            $statusInvoice = "paid";
            $statusTrx = "prepare_for_delivery";
        } else {
            $status = "payment_invalid";
        }

        $getInvoice = InvoicePayment::select(["invoice_payments.*", "invoices.price","clients.phone_number"])
            ->selectRaw("GROUP_CONCAT(transaction_details.product_name) AS product_name")
            ->where("invoice_payments.id",$req->invoice_payment_id)
            ->leftJoin('invoices','invoice_payments.transaction_id','invoices.transaction_id')
            ->leftJoin('transactions','invoice_payments.transaction_id','transactions.id')
            ->leftJoin('clients','transactions.client_id','clients.id')
            ->leftJoin('transaction_details','transactions.id','transaction_details.transaction_id')
            ->groupBy('invoice_payments.id')
            ->groupBy('invoice_payments.account_number')
            ->groupBy('invoice_payments.account_number_owner')
            ->groupBy('invoice_payments.invoice_id')
            ->groupBy('invoice_payments.transaction_id')
            ->groupBy('invoice_payments.status')
            ->groupBy('invoice_payments.amount')
            ->groupBy('invoice_payments.transfer_slip_img')
            ->groupBy('invoice_payments.created_by')
            ->groupBy('invoice_payments.created_at')
            ->groupBy('invoice_payments.updated_at')
            ->groupBy('invoice_payments.deleted_at')
            ->groupBy('invoices.price')
            ->groupBy('clients.phone_number')
            ->get();

        if (count($getInvoice) > 0) {
            $getInvoice = $getInvoice[0];
        } else {
            return response([
                "message" => "Failed, invoice not found",
                "data" => []
            ], 400);
        }

        $uuid = Str::uuid();

        $paymentAmount = $getInvoice->amount;
        $invoiceAmount = $getInvoice->price;
        if ($paymentAmount < $invoiceAmount) {
            return response([
                "message" => "Failed, payment amount less then billed amount",
                "data" => []
            ], 400);
        }

        if (!is_null($getInvoice->updated_at)) {
            return response([
                "message" => "Rejected, invoice already reviewed before",
                "data" => []
            ], 400);
        }

        $update = InvoicePayment::where([
            ["id",$req->invoice_payment_id],
            ["updated_at", null]
        ])->update([
            "status" => $status
        ]);

        $updateInvoice = Invoice::where('transaction_id', $getInvoice->transaction_id)->update([
            "status" => $statusInvoice
        ]);

        $updateTrx = Transaction::where('id', $getInvoice->transaction_id)->update([
            "status" => $statusTrx
        ]);

        $logPrefix = now() .' '. $uuid . ' verifyPayment() ';
        Log::info($logPrefix . "Updating invoice payment status to " . $status);
        Log::info($logPrefix . "Update invoice payment result: " . $update);
        Log::info($logPrefix . "Updating invoice status to " . $statusInvoice);
        Log::info($logPrefix . "Update invoice status result: " . $updateInvoice);
        Log::info($logPrefix . "Updating transaction status to " . $statusTrx);
        Log::info($logPrefix . "Update transaction status result: " . $updateTrx);

        if (!$update) {
            return response([
                "message" => "Failed, something went wrong",
                "data" => []
            ], 500);
        }

        $sendMsg = $this->sendMessage($uuid, $getInvoice->phone_number, $getInvoice->product_name, $status1, $status2);

        return response([
            "message" => "Success",
            "data" => [
                "invoice_payment_id" => $req->invoice_payment_id,
                "status" => $req->status,
            ]
        ]);
    }

    private function sendProductArrivedMessage($to, $clientName, $productList, $datetime, $transaction_id) {
        $uuid = Str::uuid();
        $logPrefix = now() .' '. $uuid . ' sendProductArrivedMessage() ';
        Log::info($logPrefix . "Send whatsapp message to client to choose courier");

        $whatsappEndpoint = env('WHATSAPP_MESSAGE_ENDPOINT');
        $whatsappToken = env('WHATSAPP_TOKEN');

        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $to,
            "type" => "template",
            "template" => [
                "name" => "product_arrived_confirmation_v1",
                "language" => [
                    "code" => "id",
                    "policy" => "deterministic"
                ],
                "components"=> [
                    [
                        "type" => "body",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => "$clientName"
                            ],
                            [
                                "type" => "text",
                                "text" => "$productList"
                            ],
                            [
                                "type" => "text",
                                "text" => date('d M Y h:i', strtotime($datetime))
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = null;
        try {
            $client = new \GuzzleHttp\Client();
            $headers = ['Authorization' => 'Bearer ' . $whatsappToken];
            $payload = [
                "headers" => $headers,
                "form_params" => $payload
            ];

            Log::info(now() ." ". $uuid . " sendProductArrivedMessage() Send request to whatsapp");
            Log::info(now() ." ". $uuid . " sendProductArrivedMessage() Endpoint: " . $whatsappEndpoint);
            Log::info(now() ." ". $uuid . " sendProductArrivedMessage() Payload: " . json_encode($payload));
            $response = $client->request('POST', $whatsappEndpoint, $payload);

            $content = json_decode($response->getBody()->getContents());
            if (isset($content->messages[0]->id)) {
                $insertNotif = FinishedOrderNotification::insert([
                    "wa_msg_id" => (String) $content->messages[0]->id,
                    "product_list" => $productList,
                    "transaction_id" => (String) $transaction_id
                ]);

                Log::info(now() ." ". $uuid . " sendProductArrivedMessage() Save to finished_order_notification tbl");
            }

            Log::info(now() ." ". $uuid . " sendProductArrivedMessage() Response: " . json_encode($content));
        } catch (ClientException $e) {
            $response = Psr7\Message::toString($e->getResponse());
            Log::info(now() ." ". $uuid . " sendProductArrivedMessage() Error: " . $response);
        }

        return $response;
    }

    public function finishTransaction(Request $req)
    {
        $req->validate([
            "transaction_id" => "required|exists:transactions,id"
        ]);

        $update = Transaction::where('id', $req->transaction_id)->update([
            'status' => 'success'
        ]);

        if (!$update) {
            return response([
                "message" => "Failed, something went wrong",
                "data" => []
            ], 500);
        }

        $trx = Transaction::select([
                'transactions.created_at',
                'clients.name',
                'clients.phone_number'
            ])
            ->selectRaw("GROUP_CONCAT(transaction_details.product_name) AS product_name")
            ->leftJoin('clients','transactions.client_id','clients.id')
            ->leftJoin('transaction_details','transactions.id','transaction_details.transaction_id')
            ->where('transaction_id', $req->transaction_id)
            ->groupBy(['transactions.created_at', 'clients.name', 'clients.phone_number'])
            ->get();

        $transaction = null;
        if (count($trx) > 0) {
            $transaction = $trx[0];
            $this->sendProductArrivedMessage($transaction->phone_number, $transaction->name, $transaction->product_name, $transaction->created_at, $req->transaction_id);
        }

        return response([
            "message" => "Success, transaction updated",
            "data" => []
        ]);
    }
}

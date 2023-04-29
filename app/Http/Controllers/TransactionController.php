<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Model\Token;
use App\Model\Transaction;
use App\Model\TransactionDetail;
use App\Model\InvoicePayment;
use App\Model\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    private function validateToken($token) {
        $findTrxId = Token::find($token);
        if (!isset($findTrxId)) {
            return abort(404, 'URL tidak ditemukan, mohon periksa kembali URL yang dimasukkan');
        } else if (strtotime($findTrxId->expired_at) < strtotime('now')) {
            return abort(419, "Token kadaluarsa");
        }

        return $findTrxId;
    }

    private function validateTrxId($trxid) {
        $findTrxId = Transaction::find($trxid);
        if (!isset($findTrxId)) {
            return abort(404, 'URL tidak ditemukan, mohon periksa kembali URL yang dimasukkan');
        } else if (strtotime($findTrxId->payment_expired_at) < strtotime('now')) {
            return abort(419, "Transaksi kadaluarsa");
        }

        return $findTrxId;
    }

    public function transactionList($token)
    {
        $findTrxId = $this->validateToken($token);

        $transactions = Transaction::select([
            "transactions.*",
            "clients.name",
            "clients.phone_number"
        ])
        ->where([
            ['client_id', $findTrxId->client_id],
            ['payment_expired_at', '>=', date('Y-m-d h:i:s')]
        ])
        ->leftJoin('clients','transactions.client_id','clients.id')
        ->orderBy('transactions.created_at','desc')
        ->get();

        foreach ($transactions as $k => $trx) {
            $transactions[$k]->details = TransactionDetail::where('transaction_id', $trx->id)->get();
        }

        $data = [
            "transactions" => $transactions
        ];

        return view('confirm_payment.index', $data);
    }

    public function confirmPaymentForm($token, $trxid)
    {
        $this->validateToken($token);
        $transaction = $this->validateTrxId($trxid);
        $transactionDetails = TransactionDetail::where("transaction_id", $transaction->id)->get();

        return view('confirm_payment.form', [
            'transaction' => $transaction,
            'detail' => $transactionDetails
        ]);
    }

    public function sendMessage($uuid, $to, $price, $productlist)
    {
        $logPrefix = now() .' '. $uuid . ' sendMessage() ';
        Log::info($logPrefix . "Send whatsapp message to client: " . $to);

        $whatsappEndpoint = env('WHATSAPP_MESSAGE_ENDPOINT');
        $whatsappToken = env('WHATSAPP_TOKEN');

        $body = "*Konfirmasi Pembayaran*\n==============================\n{{product-list}}\n\nPembayaran: Rp. {{price}}\n==============================\n\nKonfirmasi Pembayaran untuk transaksi diatas sedang kami verifikasi. Terima kasih sudah belanja di Belili.";
        $body = str_replace("{{price}}", $price, $body);
        $body = str_replace("{{product-list}}", $productlist, $body);

        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $to,
            "type" => "text",
            "text" => [
                "body" => $body
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

    public function rupiah($angka){
        $hasil_rupiah = "Rp " . number_format($angka,2,',','.');
        return $hasil_rupiah;
    }

    public function savePayment(Request $req)
    {
        $path = 'app/' . $req->file('transfer_proof')->store('public/payment_proof');
        $explodePath = explode("/", $path);
        $filename = $explodePath[count($explodePath) - 1];
        $fileurl = url('storage/payment_proof/' . $filename);

        $trxid = $req->transaction_id;
        $invoice = Invoice::where("transaction_id", $trxid)->first();
        $transaction = Transaction::select([
                "clients.phone_number"
            ])
            ->where("transactions.id", $trxid)
            ->leftJoin("clients","transactions.client_id","clients.id")
            ->first();

        if (!isset($invoice)) return abort(404, "Transaksi yang kamu pilih invalid");

        $uuid = Str::uuid();

        InvoicePayment::insert([
            "id" => $uuid,
            "account_number" => $req->bank_account,
            "account_number_owner" => $req->bank_account_owner,
            "invoice_id" => $invoice->id,
            "transaction_id" => $trxid,
            "status" => 'waiting_verification',
            "amount" => $req->amount_of_transfer,
            "transfer_slip_img" => $fileurl,
            "created_by" => "",
            "created_at" => now()
        ]);

        if (isset($transaction)) {
            $trxDetail = TransactionDetail::select(['product_name'])->where('transaction_id', $trxid)->get();
            $productlist = '';
            for ($i=0; $i < count($trxDetail); $i++) {
                $dt = $trxDetail[$i];
                $productlist .= ($i + 1) . ". " . $dt->product_name;

                if (isset($trxDetail[$i + 1])) {
                    $productlist .= " \n";
                }
            }

            $this->sendMessage($uuid, $transaction->phone_number, $this->rupiah($req->amount_of_transfer), $productlist);
        }

        return redirect('/confirm-payment/feedback');
    }

    public function feedback()
    {
        return view('confirm_payment.feedback', [
            "msg" => "Berhasil disimpan, konfirmasi pembayaran dalam peninjauan"
        ]);
    }

    public function transactionHistory($token)
    {
        $tokenData = $this->validateToken($token);

        $transactions = Transaction::select([
            "transactions.*",
            "clients.name",
            "clients.phone_number"
        ])
        ->where([
            ['client_id', $tokenData->client_id]
        ])
        ->leftJoin('clients','transactions.client_id','clients.id')
        ->orderBy('transactions.created_at','desc')
        ->get();

        foreach ($transactions as $k => $trx) {
            $transactions[$k]->details = TransactionDetail::where('transaction_id', $trx->id)->get();
        }

        return view('transaction_history.index', [
            "transactions" => $transactions
        ]);
    }
}

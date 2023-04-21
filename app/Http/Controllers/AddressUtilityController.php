<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\SetAddress;
use App\Model\Client;
use App\Model\Address;
use App\Model\Product;
use App\Model\PendingTransaction;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;

class AddressUtilityController extends Controller
{

    public function index($token)
    {
        $getToken = SetAddress::where("token", $token)->first();
        date_default_timezone_set('Asia/Jakarta');
        if (!isset($getToken)) {
            $data = [
                "error_type" => "token_not_found"
            ];

            return view('address_utility.error', $data);
        } else if (isset($getToken) && strtotime($getToken->expired_at) < strtotime('now')) {
            $data = [
                "error_type" => "expired"
            ];

            return view('address_utility.error', $data);
        }

        $errorMsg = "";
        $provinceList = [];

        try {
            $client = new \GuzzleHttp\Client();
            $provinceUrl = env('RAJAONGKIR_PROVINCE_URL');
            $key = env('RAJAONGKIR_API_KEY');
            $headers = ['key' => $key];

            $response = $client->request('GET', $provinceUrl, [
                "headers" => $headers
            ]);

            if ($response->getStatusCode() == 200) {
                $provinceResponse = json_decode($response->getBody()->getContents());
                $provinceList = $provinceResponse->rajaongkir->results;
            }
        } catch (ClientException $e) {
            $errorMsg = Psr7\Message::toString($e->getResponse());
        }

        $client = Client::find($getToken->client_id);

        $data = [
            "province_list" => $provinceList,
            "set_address" => $getToken,
            "error" => $errorMsg,
            "client" => $client
        ];

        return view('address_utility.index', $data);
    }

    public function cityList($provinceId)
    {
        $cityList = [];
        try {
            $client = new \GuzzleHttp\Client();
            $cityUrl = env('RAJAONGKIR_CITY_URL') . "?province=" . $provinceId;
            $key = env('RAJAONGKIR_API_KEY');
            $headers = ['key' => $key];

            $response = $client->request('GET', $cityUrl, [
                "headers" => $headers
            ]);

            if ($response->getStatusCode() == 200) {
                $provinceResponse = json_decode($response->getBody()->getContents());
                $cityList = $provinceResponse->rajaongkir->results;
            }
        } catch (ClientException $e) {
            $errorMsg = Psr7\Message::toString($e->getResponse());
        }

        return response([
            "data" => $cityList
        ]);
    }

    public function getDeliveryFee($uuid, $courierName, $origin, $dst, $weight)
    {
        $results = [];
        $errorMsg = "";
        try {
            $client = new \GuzzleHttp\Client();
            $calculateCostURL = env('RAJAONGKIR_COST_URL');
            $key = env('RAJAONGKIR_API_KEY');
            $headers = ['key' => $key];
            $payload = [
                "headers" => $headers,
                "form_params" => [
                    "origin" => $origin,
                    "destination" => $dst,
                    "weight" => $weight,
                    "courier" => $courierName
                ]
            ];

            $response = $client->request('POST', $calculateCostURL, $payload);

            if ($response->getStatusCode() == 200) {
                $response = json_decode($response->getBody()->getContents());
                $results = $response->rajaongkir->results;
            }
        } catch (ClientException $e) {
            $errorMsg = Psr7\Message::toString($e->getResponse());
        }

        if ($errorMsg != "") return $errorMsg;

        return $results;
    }

    public function sendMessage($uuid, $pendingTrx, $address, $couriers)
    {
        $logPrefix = now() .' '. $uuid . ' sendMessage() ';
        Log::info($logPrefix . "Send whatsapp message to client to choose courier");

        $whatsappEndpoint = env('WHATSAPP_MESSAGE_ENDPOINT');
        $whatsappToken = env('WHATSAPP_TOKEN');

        $courierList = [];
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => $pendingTrx->client_phone_number,
            "type" => "interactive",
            "interactive" => [
                "type" => "list",
                "header" => [
                    "type" => "text",
                    "text" => "Alamat berhasil disimpan, selanjutnya mohon pilih kurir"
                ],
                "body" => [
                    "text" => "📍 Alamat kamu: " . $address
                ],
                "action" => [
                    "button" => "Pilih Kurir",
                    "sections" => [
                        [
                            "title" => "Kurir",
                            "rows" => $couriers
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

    public function saveAddress(Request $req)
    {
        $uuid = Str::uuid();
        $logPrefix = now() .' '. $uuid . ' saveAddress() ';
        Log::info($logPrefix . "Save address request received");

        // prevent duplication
        $preventDup = Address::where('pending_transaction_id', $req->pending_transaction_id)->first();
        if (isset($preventDup)) {
            Log::info($logPrefix . "Request rejected as data already saved");
            $data = [
                "error_type" => "Info",
                "error_data" => "Kamu sudah menambahkan alamat"
            ];

            return view('address_utility.error', $data);
        }

        $rajaongkirRef = [
            "origin_city_id" => $req->city,
            "origin_province_id" => $req->province
        ];

        $pendingTrx = PendingTransaction::where("id", $req->pending_transaction_id)->first();
        $productSkuList = [];
        $totalProductWeight = null;
        if (isset($pendingTrx)) {
            $productSkuList = [];
            $parseProductList = json_decode($pendingTrx->product_list);
            $totalWeight = 0;
            for ($i=0; $i < count($parseProductList->ordered_product); $i++) {
                $productSkuList[] = $parseProductList->ordered_product[$i]->sku;
                $getProduct = Product::where("sku", $parseProductList->ordered_product[$i]->sku)->first();
                if (isset($getProduct)) {
                    $totalWeight += $getProduct->weight * $parseProductList->ordered_product[$i]->qty;
                }
            }

            Log::info($logPrefix . "Total product on pending transaction : " . count($productSkuList));
            Log::info($logPrefix . "Total product on pending transaction price : " . $totalProductWeight);
        }

        // get courier price
        $couriers = [];
        $jnePrice = $this->getDeliveryFee($uuid, "jne", 399, $req->city, $totalWeight);
        Log::info($logPrefix . "Response get price JNE : " . json_encode($jnePrice));
        for ($i=0; $i < count($jnePrice[0]->costs); $i++) {
            $couriers[] = [
                "title" => "JNE_" . $jnePrice[0]->costs[$i]->service . " " . $jnePrice[0]->costs[$i]->cost[0]->etd . "H" . " Rp.". ($jnePrice[0]->costs[$i]->cost[0]->value/1000) . "k",
                "id" => "select-courier." . $req->pending_transaction_id . '.' . "JNE_" . $jnePrice[0]->costs[$i]->service . '.' . $jnePrice[0]->costs[$i]->cost[0]->value
            ];
        }

        $posPrice = $this->getDeliveryFee($uuid, "pos", 399, $req->city, $totalWeight);
        Log::info($logPrefix . "Response get price POS : " . json_encode($posPrice));
        for ($i=0; $i < count($posPrice[0]->costs); $i++) {
            $couriers[] = [
                // "title" => $posPrice[0]->costs[$i]->service . " " . $posPrice[0]->costs[$i]->cost[0]->etd . "H" . " Rp.". ($posPrice[0]->costs[$i]->cost[0]->value/1000) . "k",
                "title" => "POS_R".($i + 1) . " " . $posPrice[0]->costs[$i]->cost[0]->etd . " Rp.". ($posPrice[0]->costs[$i]->cost[0]->value/1000) . "k",
                // "title" => "POS_REG 8H" . " Rp.". ($posPrice[0]->costs[$i]->cost[0]->value/1000) . "k",
                "id" => "select-courier." . $req->pending_transaction_id . '.' . "POS_" . $posPrice[0]->costs[$i]->service . '.' . $posPrice[0]->costs[$i]->cost[0]->value
            ];
        }

        $tikiPrice = $this->getDeliveryFee($uuid, "tiki", 399, $req->city, $totalWeight);
        Log::info($logPrefix . "Response get price TIKI : " . json_encode($tikiPrice));
        for ($i=0; $i < count($tikiPrice[0]->costs); $i++) {
            $couriers[] = [
                "title" => "TIKI_" . $tikiPrice[0]->costs[$i]->service . " " . $tikiPrice[0]->costs[$i]->cost[0]->etd . "H" . " Rp.". ($tikiPrice[0]->costs[$i]->cost[0]->value/1000) . "k",
                "id" => "select-courier." . $req->pending_transaction_id . '.' . "TIKI_" . $tikiPrice[0]->costs[$i]->service . '.' . $tikiPrice[0]->costs[$i]->cost[0]->value
            ];
        }

        // send address courier option message
        $sendMsgResp = null;
        if ($pendingTrx) $sendMsgResp = $this->sendMessage($uuid, $pendingTrx, $req->full_address, $couriers);


        // check if failed
        if (
            $sendMsgResp == null ||
            is_string($sendMsgResp) ||
            !($sendMsgResp != null && $sendMsgResp->getStatusCode() == 200)
        ) {
            Log::info($logPrefix . "Transaction failed");
            $data = [
                "error_type" => "something_went_wrong",
                "error_data" => $sendMsgResp
            ];

            return view('address_utility.error', $data);
        }

        $saveAddress = Address::insert([
            "id" => Str::uuid(),
            "pending_transaction_id" => $req->pending_transaction_id,
            "client_id" => $req->client_id,
            "address" => $req->full_address,
            "recipient_name" => $req->receiver_name,
            "phone_number" => $req->receiver_phone_number,
            "rajaongkir_ref" => json_encode($rajaongkirRef),
            "created_at" => now()
        ]);

        return view('address_utility.success_set_address');
    }
}

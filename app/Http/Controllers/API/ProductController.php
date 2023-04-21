<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Model\Product;
use App\Model\StockHistory;

class ProductController extends Controller
{

    public function index()
    {
        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got product list request');

        $get_products = Product::select(["fb_data","status","sku"])->orderBy('created_at','DESC')->get();
        $products = [];
        for ($i=0; $i < count($get_products); $i++) {
            $product = json_decode($get_products[$i]->fb_data);
            $product->status = $get_products[$i]->status;
            $product->retailer_id = $get_products[$i]->sku;
            $products[] = $product;
        }

        Log::info(now() .' '. $uuid . ' Success return product list');
        return response([
            "message" => "Success",
            "data" => $products
        ]);
    }

    public function detail(Request $req)
    {
        $req->validate([
            "retailer_id" => "required|exists:products,sku"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got product detail request : ' . json_encode($req->all()));

        $product_detail = Product::where('sku', $req->retailer_id)->first();
        $detail = json_decode($product_detail->fb_data);
        $detail->status = $product_detail->status;
        $stock_history = StockHistory::where('product_id', $product_detail->id)->orderBy('created_at','DESC')->get();

        Log::info(now() .' '. $uuid . ' Success return product detail');
        return response([
            "message" => "Success",
            "data" => [
                "product_detail" => $detail,
                "stock_history" => $stock_history
            ]
        ]);
    }

    public function update(Request $req)
    {
        $req->validate([
            "requests" => "required",
            "requests.*.retailer_id" => "required|exists:products,sku",
            "requests.*.data" => "required",
            "requests.*.data.availability" => "nullable",
            "requests.*.data.stock" => "nullable|int|min:1",
            "requests.*.data.brand" => "nullable",
            "requests.*.data.category" => "nullable",
            "requests.*.data.description" => "nullable",
            "requests.*.data.image_url" => "nullable",
            "requests.*.data.additional_image_link" => "nullable|array",
            "requests.*.data.name" => "nullable",
            "requests.*.data.price" => "nullable|numeric|min:1",
            "requests.*.data.weight" => "required:integer|min:1",
            "requests.*.data.currency" => "nullable",
            "requests.*.data.shipping" => "nullable",
            "requests.*.data.shipping.*.country" => "nullable",
            "requests.*.data.shipping.*.region" => "nullable",
            "requests.*.data.shipping.*.service" => "nullable",
            "requests.*.data.shipping.*.price_value" => "nullable",
            "requests.*.data.shipping.*.price_currency" => "nullable",
            "requests.*.data.condition" => "nullable",
            "requests.*.data.url" => "nullable",
            "requests.*.data.retailer_product_group_id" => "nullable",
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got product update request : ' . json_encode($req->all()));

        $client = new \GuzzleHttp\Client();
        $fb_endpoint = env('FB_ENDPOINT');
        $fb_token = env('FB_MARKETING_API_TOKEN');

        $payload = [
            "form_params" => [
                "access_token" => $fb_token,
                "requests" => []
            ]
        ];

        // generate request body before send request to FB & query
        for ($i=0; $i < count($req->requests); $i++) {
            $item = $req->requests[$i];
            $current_product = Product::where("sku", $item['retailer_id'])->first();

            // two following variable will be use later down in query after successful send request to FB
            $update_arr[$i]['updated_at'] = now();
            $where_arr[$i][] = ["sku", $item['retailer_id']];

            $update_arr[$i]['weight'] = $item["weight"];

            if (isset($item['data']['name'])) {
                Log::info(now() .' '. $uuid . ' Got name update request on product : ' . $item["retailer_id"]);
                $update_arr[$i]['name'] = $item['data']['name'];
            }

            if (isset($item['data']['price'])) {
                Log::info(now() .' '. $uuid . ' Got price update request on product : ' . $item["retailer_id"]);
                $update_arr[$i]['price'] = $item['data']['price'];
            }

            if (isset($item['data']['stock'])) {
                Log::info(now() .' '. $uuid . ' Got stock update request on product : ' . $item["retailer_id"]);
                $update_arr[$i]['stock'] = $item['data']['stock'];

                // insert stock history
                $current_stock = $current_product->stock;
                if ($current_stock != $item['data']['stock']) {
                    Log::info(now() .' '. $uuid . ' Got request with stock different from current stock');
                    Log::info(now() .' '. $uuid . ' Insert stock update to stock history');

                    $stock_change_type = "in";
                    $stock_change_amount = $item['data']['stock'] - $current_stock;
                    if ($current_stock > $item['data']['stock']) {
                        $stock_change_type = "out";
                        $stock_change_amount = $current_stock - $item['data']['stock'];
                    }

                    $stock_history = [
                        "id" => (string) $uuid,
                        "product_id" => $current_product->id,
                        "transaction_id" => (string) $uuid,
                        "stock_change_amount" => $stock_change_amount,
                        "stock_before" => $current_stock,
                        "stock_after" => $item['data']['stock'],
                        "note" => "STOCK UPDATE FROM CMS",
                        "type" => $stock_change_type,
                        "created_at" => now()
                    ];

                    Log::info(now() .' '. $uuid . ' Insert stock history : ' . json_encode($stock_history));
                    $insert_stock_history = StockHistory::insert($stock_history);
                }
            }

            if (isset($item['data']['description'])) {
                Log::info(now() .' '. $uuid . ' Got description update request on product : ' . $item["retailer_id"]);
                $update_arr[$i]['description'] = $item['data']['description'];
            }

            // update fb_data json value based on payload
            $fb_data = json_decode($current_product->fb_data);
            foreach ($item['data'] as $key => $value) {
                $fb_data->$key = $value;
            }

            $update_arr[$i]['fb_data'] = json_encode($fb_data);

            // remove stock key from payload as FB can't process unstandard payload request
            unset($item['data']['stock']);
            $payload['form_params']['requests'][] = [
                'method' => 'UPDATE',
                'retailer_id' => $item['retailer_id'],
                'data' => $item['data']
            ];
        }

        Log::info(now() .' '. $uuid . ' Sending request to FB : ', ['endpoint' => $fb_endpoint, 'payload' => $payload]);
        $create_item = $client->request('POST', $fb_endpoint, $payload);
        $response_body = $create_item->getBody()->getContents();
        $response_body_json = json_decode($response_body);
        $response_status_code = $create_item->getStatusCode();
        Log::info(now() .' '. $uuid . ' Response : ', ["RESPONSE BODY" => $response_body, "STATUS CODE" => $response_status_code]);

        if (isset($response_body_json->validation_status[0]->errors) && count($response_body_json->validation_status[0]->errors) > 0) {
            // request failed
            $errors = [];
            for ($i=0; $i < count($response_body_json->validation_status[0]->errors); $i++) {
                $errors["message"][] = $response_body_json->validation_status[0]->errors[$i]->message;
            }

            Log::info(now() .' '. $uuid . ' Got error from FB : ', ['errors' => $errors]);
            Log::info(now() .' '. $uuid . ' Return response 422');
            return response([
                "message" => "Looks like something is missing",
                "errors" => $errors
            ], 422);
        } else {
            // request success
            Log::info(now() .' '. $uuid . ' Executing sql update query ...');
            for ($j=0; $j < count($where_arr); $j++) {
                $update_product = Product::where($where_arr[$j])->update($update_arr[$j]);

                if (!$update_product) {
                    Log::info(now() .' '. $uuid . ' Failed to update db');
                } else {
                    Log::info(now() .' '. $uuid . ' Success to update db');
                }
            }

            Log::info(now() .' '. $uuid . ' Update success');
            return response([
                "message" => "Success, product updated"
            ]);
        }
    }

    public function create(Request $req)
    {
        $req->validate([
            "requests" => "required",
            "requests.*.retailer_id" => "nullable|unique:products,sku",
            "requests.*.data" => "required",
            "requests.*.data.availability" => "required",
            "requests.*.data.stock" => "required|int",
            "requests.*.data.brand" => "required",
            "requests.*.data.category" => "required",
            "requests.*.data.description" => "required",
            "requests.*.data.image_url" => "required",
            "requests.*.data.additional_image_link" => "required|array|min:2",
            "requests.*.data.name" => "required",
            "requests.*.data.price" => "required:integer",
            "requests.*.data.weight" => "required:integer|min:1",
            "requests.*.data.currency" => "required",
            "requests.*.data.shipping" => "required",
            "requests.*.data.shipping.*.country" => "required",
            "requests.*.data.shipping.*.region" => "required",
            "requests.*.data.shipping.*.service" => "required",
            "requests.*.data.shipping.*.price_value" => "required",
            "requests.*.data.shipping.*.price_currency" => "required",
            "requests.*.data.condition" => "required",
            "requests.*.data.url" => "required",
            "requests.*.data.retailer_product_group_id" => "required",
        ]);

        $uuid = Str::uuid();
        $client = new \GuzzleHttp\Client();
        $fb_endpoint = env('FB_ENDPOINT');
        $fb_token = env('FB_MARKETING_API_TOKEN');

        $payload = [
            "form_params" => [
                "access_token" => $fb_token,
                "requests" => []
            ]
        ];

        $saveToDB = [
            "form_params" => [
                "access_token" => $fb_token,
                "requests" => []
            ]
        ];

        for ($j=0; $j < count($req->requests); $j++) {
            $item = $req->requests[$j];

            if (!isset($item['retailer_id'])) {
                // retailer id is not present
                // add retailer id to payload
                $retailerId = (string) $uuid;
            } else {
                // retailer id present, use from payload
                $retailerId = $item['retailer_id'];
            }

            $payload["form_params"]["requests"][] = [
                "method" => "CREATE",
                "retailer_id" => $retailerId,
                "data" => [
                    "availability" => $item['data']['availability'],
                    "brand" => $item['data']['brand'],
                    "category" => $item['data']['category'],
                    "description" => $item['data']['description'],
                    "image_url" => $item['data']['image_url'],
                    "name" => $item['data']['name'],
                    "price" => $item['data']['price'],
                    "currency" => $item['data']['currency'],
                    "shipping" => $item['data']['shipping'],
                    "condition" => $item['data']['condition'],
                    "url" => $item['data']['url'],
                    "retailer_product_group_id" => $item['data']['retailer_product_group_id']
                ]
            ];

            $saveToDB["form_params"]["requests"][] = [
                "method" => "CREATE",
                "retailer_id" => $retailerId,
                "data" => [
                    "availability" => $item['data']['availability'],
                    "brand" => $item['data']['brand'],
                    "category" => $item['data']['category'],
                    "description" => $item['data']['description'],
                    "image_url" => $item['data']['image_url'],
                    "name" => $item['data']['name'],
                    "price" => $item['data']['price'],
                    "weight" => $item['data']['weight'],
                    "currency" => $item['data']['currency'],
                    "shipping" => $item['data']['shipping'],
                    "condition" => $item['data']['condition'],
                    "url" => $item['data']['url'],
                    "retailer_product_group_id" => $item['data']['retailer_product_group_id']
                ]
            ];
        }

        Log::info(now() .' '. $uuid . ' Sending request to FB : ', ['endpoint' => $fb_endpoint, 'payload' => $payload]);
        $create_item = $client->request('POST', $fb_endpoint, $payload);
        $response_body = $create_item->getBody()->getContents();
        $response_body_json = json_decode($response_body);
        $response_status_code = $create_item->getStatusCode();
        Log::info(now() .' '. $uuid . ' Response : ', ["RESPONSE BODY" => $response_body, "STATUS CODE" => $response_status_code]);

        if (isset($response_body_json->validation_status[0]->errors) && count($response_body_json->validation_status[0]->errors) > 0) {
            // request failed
            $errors = [];
            for ($i=0; $i < count($response_body_json->validation_status[0]->errors); $i++) {
                $errors["message"][] = $response_body_json->validation_status[0]->errors[$i]->message;
            }

            Log::info(now() .' '. $uuid . ' Got error from FB : ', ['errors' => $errors]);
            Log::info(now() .' '. $uuid . ' Return response 422');
            return response([
                "message" => "Looks like something is missing",
                "errors" => $errors
            ], 422);
        } else {
            for ($j=0; $j < count($saveToDB['form_params']['requests']); $j++) {
                $item = $saveToDB['form_params']['requests'][$j];
                $newProduct = [
                    "id" => $uuid,
                    "status" => "in_review",
                    "sku" => $item['retailer_id'],
                    "name" => $item['data']['name'],
                    "price" => $item['data']['price'],
                    "weight" => $item['data']['weight'],
                    "stock" => $req->requests[$j]['data']['stock'],
                    "fb_data" => json_encode($item["data"]),
                    "description" => $item['data']['description'],
                    "created_at" => now()
                ];

                Log::info(now() .' '. $uuid . ' Insert ' . json_encode($newProduct) . ' to product table');

                $insert = Product::insert($newProduct);
            }

            Log::info(now() .' '. $uuid . ' Return response success');
            return response([
                "message" => "Success, product created"
            ]);
        }
    }

    public function delete(Request $req)
    {
        $req->validate([
            "requests" => "required",
            "requests.*.retailer_id" => "required|exists:products,sku"
        ]);

        $uuid = Str::uuid();
        Log::info(now() .' '. $uuid . ' Got delete request : ' . json_encode($req->all()));

        $client = new \GuzzleHttp\Client();
        $fb_endpoint = env('FB_ENDPOINT');
        $fb_token = env('FB_MARKETING_API_TOKEN');

        $payload = [
            "form_params" => [
                "access_token" => $fb_token,
                "requests" => []
            ]
        ];

        $sku = [];

        for ($i=0; $i < count($req->requests); $i++) {
            $sku[] = $req->requests[$i]['retailer_id'];
            $payload['form_params']['requests'][] = [
                "method" => "DELETE",
                "retailer_id" => $req->requests[$i]['retailer_id']
            ];
        }

        Log::info(now() .' '. $uuid . ' Sending delete request to FB : ', ['endpoint' => $fb_endpoint, 'payload' => $payload]);

        $delete_item = $client->request('POST', $fb_endpoint, $payload);
        $response_body = $delete_item->getBody()->getContents();
        $response_body_json = json_decode($response_body);
        $response_status_code = $delete_item->getStatusCode();

        Log::info(now() .' '. $uuid . ' Response : ', ["RESPONSE BODY" => $response_body, "STATUS CODE" => $response_status_code]);

        if (isset($response_body_json->validation_status[0]->errors) && count($response_body_json->validation_status[0]->errors) > 0) {
            // request failed
            $errors = [];
            for ($i=0; $i < count($response_body_json->validation_status[0]->errors); $i++) {
                $errors["message"][] = $response_body_json->validation_status[0]->errors[$i]->message;
            }

            Log::info(now() .' '. $uuid . ' Got error from FB : ', ['errors' => $errors]);
            Log::info(now() .' '. $uuid . ' Return response 422');
            return response([
                "message" => "Looks like something went wrong",
                "errors" => $errors
            ], 422);
        } else {
            Log::info(now() .' '. $uuid . ' Item in FB commerce successfully deleted');
            $delete = Product::whereIn('sku', $sku)->forceDelete();
            if ($delete) {
                Log::info(now() .' '. $uuid . ' Item in CMS DB also successfully deleted');
            } else {
                Log::info(now() .' '. $uuid . ' Item in CMS DB failed to deleted');
            }

            Log::info(now() .' '. $uuid . ' Return response success');

            return response([
                "message" => "Success, product deleted"
            ]);
        }
    }
}

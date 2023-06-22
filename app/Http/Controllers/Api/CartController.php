<?php

namespace App\Http\Controllers\Api;

use Str;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Xendit\Xendit as Xendit;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function getMeta($request) {
        $product = Product::where('id', $request->product_id)->first();
        $customer = Customer::where('token', $request->token)->first();
        $query = CustomerOrderItem::where([
            ['customer_id', $customer->id],
            ['product_id', $request->product_id],
        ])
        ->whereNull('order_id');
        $item = $query->first();

        return [
            'product' => $product,
            'customer' => $customer,
            'query' => $query,
            'item' => $item,
        ];
    }
    public function increase($username, Request $request) {
        $meta = $this->getMeta($request);

        if ($meta['item'] == "") {
            $createItem = CustomerOrderItem::create([
                'product_id' => $request->product_id,
                'customer_id' => $meta['customer']->id,
                'user_id' => $meta['customer']->user_id,
                'price' => $meta['product']->price,
                'quantity' => 1,
                'total_price' => $meta['product']->price,
            ]);
        } else {
            $newQuantity = $meta['item']->quantity + 1;
            $meta['query']->update([
                'quantity' => $newQuantity,
                'total_price' => $newQuantity * $meta['product']->price,
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => "Berhasil menambahkan item",
        ]);
    }
    public function decrease($username, Request $request) {
        $meta = $this->getMeta($request);
        $newQuantity = $meta['item']->quantity - 1;
        if ($newQuantity == 0) {
            $meta['query']->delete();
        } else {
            $newPrice = $newQuantity * $meta['product']->price;
            $meta['query']->update([
                'quantity' => $newQuantity,
                'total_price' => $newPrice,
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => "Berhasil mengurangi item",
        ]);
    }
    public function checkout($username, Request $request) {
        $customer = Customer::where('token', $request->token)->first();
        $itemQuery = CustomerOrderItem::where([
            ['customer_id', $customer->id],
            ['user_id', $customer->user_id],
        ])
        ->whereNull('order_id')
        ->with(['product']);
        $items = $itemQuery->get();
        $totalWeight = 0;
        $totalPay = 0;

        foreach ($items as $item) {
            $product = $item->product;
            if ($product->type == "physic") {
                $totalWeight += $product->weigth;
            }
            $totalPay += $item->total_price;
        }

        $code = Str::random(16);

        $createOrder = CustomerOrder::create([
            'user_id' => $customer->user_id,
            'customer_id' => $customer->id,
            'total_weight' => $totalWeight,
            'total_pay' => $totalPay,
            'code' => $code,
            'has_withdrawn' => 0,
        ]);

        $itemQuery->update([
            'order_id' => $createOrder->id,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Berhasil checkout",
            'code' => $code,
        ]);
    }

    public function detailOrder(Request $request, $username, $code = NULL) {
        if ($code != NULL) {
            $order = CustomerOrder::where('code', $code)->with(['user','items.product.image'])->first();
            $user = $order->user;
        } else {
            $user = User::where('username', $username)->first();
            $customer = Customer::where('token', $request->token)->first();
            $order = CustomerOrder::where([
                ['customer_id', $customer->id],
                ['user_id', $user->id],
            ])
            ->with(['items'])
            ->get();
        }

        return response()->json([
            'status' => 200,
            'order' => $order,
            'user' => $user,
        ]);
    }
    public static function escapeVaName($name) {
        $replacements = [
            '0' => "NOL",'1' => "SATU",
            '2' => "DUA",'3' => "TIGA",
            '4' => "EMPAT",'5' => "LIMA",
            '6' => "ENAM",'7' => "TUJUH",
            '8' => "DELAPAN",'9' => "SEMBILAN",
            '.' => "", '@' => "", '"' => "", "'" => "",
            ':' => "", '?' => "",
        ];

        foreach ($replacements as $key => $replacer) {
            $name = str_replace($key, $replacer, $name);
        }

        return $name;
    }
    public function placeOrder($username, $code, Request $request) {
        $secretKey = env('XENDIT_MODE') == 'sandbox' ? env('XENDIT_SECRET_KEY_SANDBOX') : env('XENDIT_SECRET_KEY');
        Xendit::setApiKey($secretKey);
        $orderQuery = CustomerOrder::where('code', $code);
        $order = $orderQuery->with('user')->first();
        $user = $order->user;
        $now = Carbon::now();

        $payments = explode("|", $request->payments); // expected {method}_channel
        if ($payments[0] == "va") {
            $name = self::escapeVaName($user->name);;
            $vaArgs = [
                'external_id' => $order->code,
                'bank_code' => strtoupper($payments[1]),
                'expected_amount' => $order->total_pay,
                'suggested_amount' => $order->total_pay,
                'is_single_use' => true,
                'expiration_date' => $now->addDay()->format('Y-m-d H:i:s'),
            ];

            $vaArgs['name'] = self::escapeVaName($name);
            Log::info($vaArgs);
            $trx = \Xendit\VirtualAccounts::create($vaArgs);

            $orderQuery->update([
                'payment_id' => $trx['id'],
                'payment_status' => $trx['status'],
                'payment_method' => strtoupper($payments[0]),
                'payment_channel' => strtoupper($payments[1])
            ]);
        } else if ($payments[0] == "ewallet") {
            $ewalletArgs = [
                'reference_id' => $order->code,
                'currency' => "IDR",
                'amount' => $order->total_pay,
                'checkout_method' => "ONE_TIME_PAYMENT",
                'channel_code' => strtoupper($payments[1]),
            ];
            $walletProperties = [
                'id_ovo' => "mobile_number",
                'id_jeniuspay' => "cashtag",
            ];

            if (isset($walletProperties[$payments[1]])) {
                $ewalletArgs['channel_properties'][$walletProperties[$payments[1]]] = $request->channel_properties;
            }

            $trx = \Xendit\EWallets::createEWalletCharge($ewalletArgs);
        } else if ($payments[0] == "retail") {
            // 
        }

        return response()->json([
            'status' => 200,
            'trx' => $trx,
        ]);
    }

    public function ewalletCallback(Request $request) {
        return response()->json([
            'message' => "ID Pembayaran :" . $request->data['id'],
        ]);
    }
}

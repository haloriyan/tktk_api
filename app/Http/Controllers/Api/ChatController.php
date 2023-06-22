<?php

namespace App\Http\Controllers\Api;

use Sastrawi\Stemmer\StemmerFactory;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function customerGet(Request $request) {
        $customer = Customer::where('token', $request->token)->first();
        $chats = Chat::where([
            ['customer_id', $customer->id],
            ['user_id', $request->user_id]
        ])->take(15)->orderBy('created_at', 'DESC')->get();

        return response()->json([
            'status' => 200,
            'chats' => $chats
        ]);
    }
    public function removeConjunction($sentences) {
        return array_diff($sentences, ["yang","kalau","terus","dengan"]);
    }
    public function removeUnnecessary($sentences) {
        $toRemove = [
            "permisi","mau","tanya","nanya","halo","nggak","gak","ngga","tidak","ga","itu","anu","saya",
            "anda","kamu","diri","situ","aku","nama","pengen","ingin","aja","saja","mo","pengen","ingin",
            "ada","tinggal","berapa","apa","siapa", "kak", "mas", "mba", "mbak","cek","check","kode","nomor","punya"
        ];
        return array_diff($sentences, $toRemove);
    }
    public function toIdr($angka){
		return 'Rp '.strrev(implode('.',str_split(strrev(strval($angka)),3)));
	}
    public function customerSend(Request $request) {
        $stemmerFactory = new StemmerFactory();
        $stemmer = $stemmerFactory->createStemmer();
        $customer = Customer::where('token', $request->token)->first();
        $latestChat = Chat::where([
            ['customer_id', $customer->id],
            ['user_id',  $request->user_id],
        ])->orderBy('created_at', 'DESC')->first();
        
        $body = $request->body;
        $bodies = explode(" ", $body);
        $bodies = $this->removeUnnecessary($bodies);
        $bodies = $this->removeConjunction($bodies);
        $stemmedBody = $stemmer->stem($body);

        $processed = $this->processConversation($bodies);
        $bodies = $processed['bodies'];

        $toCreate = [
            'customer_id' => $customer->id,
            'user_id' => $request->user_id,
            'body' => $body,
            'stemmed_body' => $stemmedBody,
            'sender' => "customer",
            'context' => $processed['context']
        ];
        if ($processed['product'] != null) {
            $toCreate['interested_product_id'] = $processed['product']->id;
        }
        $saveData = Chat::create($toCreate);
        
        sleep(1);

        $this->reply($processed['context'], [
            'user_id' => $request->user_id,
            'customer_id' => $customer->id,
            'bodies' => $bodies,
            'product' => $processed['product'],
        ]);

        return response()->json(['status' => 200]);
    }
    public function processConversation($bodies) {
        $context = null;
        $product = null;

        if (count($bodies) == 0) {
            $context = "greetings";
        } else if (in_array('produk', $bodies) || in_array('barang', $bodies) || in_array('tentang', $bodies)) {
            $context = "ask-product";
            $bodies = array_diff($bodies, ['tentang', 'barang', 'produk', 'product']);
            $body = implode(" ", $bodies);
            $product = Product::where('name', 'LIKE', '%'.$body.'%')->first();
        } else if (in_array('pesan', $bodies) || in_array('order', $bodies) || in_array('pesanan', $bodies) || in_array('orderan', $bodies) || in_array('tagihan', $bodies)) {
            $context = "ask-order";
            $bodies = array_diff($bodies, ['tentang', 'barang', 'produk', 'product', 'pesanan', 'orderan']);
        } else {
            $context = "unrecognized";
        }

        return [
            'context' => $context,
            'product' => $product,
            'bodies' => $bodies,
        ];
    }
    public function reply($context, $payload) {
        $stemmerFactory = new StemmerFactory();
        $stemmer = $stemmerFactory->createStemmer();
        $actions = [];
        $response = "";
        $user = User::where('id', $payload['user_id'])->first();

        if ($context == "greetings") {
            $response = "Halo, ada yang bisa dibantu?";
        } else if ($context == "ask-product") {
            if ($payload['product'] == null) {
                $response = "Maaf, kami tidak memiliki produk " . implode(" ", $payload['bodies']);
            } else {
                $product = $payload['product'];
                $response = "Berikut adalah deskripsi dari ".$product->name." :<br /><br /><i>".$product->description."</i>";
                array_push($actions, [
                    'action' => env('BASE_URL') . "/" . $user->username . "/product/" . $product->slug,
                    'text' => "Lihat Produk",
                    'type' => "link"
                ]);
            }
        } else if ($context == "ask-order") {
            $order = CustomerOrder::where('code', 'LIKE', '%'.implode(" ", $payload['bodies']).'%')->first();

            if ($order == "") {
                $orders = CustomerOrder::where([
                    ['customer_id', $payload['customer_id']]
                ])->orderBy('created_at', 'DESC')->take(5)->get();
    
                if ($orders->count() > 0) {
                    $response = "Berikut ini adalah beberapa pesanan terakhirmu";
                    foreach ($orders as $order) {
                        array_push($actions, [
                            'action' => env('BASE_URL') . "/" . $user->username . "/order/" . $order->code,
                            'text' => "Pesanan #".$order->code,
                            'type' => "link"
                        ]);
                    }
                } else {
                    $response = "Kamu belum memiliki orderan sama sekali";
                    array_push($actions, [
                        'action' => env('BASE_URL') . "/" . $user->username,
                        'text' => "Lihat katalog",
                        'type' => "link"
                    ]);
                }
            } else {
                $response = "Pesanan dengan kode " . $order->code . " memiliki tagihan sebesar " . $this->toIdr($order->total_pay);
                array_push($actions, [
                    'action' => env('BASE_URL') . "/" . $user->username . "/order/" . $order->code,
                    'text' => "Lihat Pesanan #".$order->code,
                    'type' => "link"
                ]);
            }
        } else if ($context == "unrecognized") {
            // search product then custom question
            $product = Product::where('name', 'LIKE',  '%'.implode(" ", $payload['bodies']).'%')->first();
            if ($product == "") {
                // search custom question
                $response = "Maaf saya tidak mengerti.";
            } else {
                $response = "Berikut adalah deskripsi dari ".$product->name." :<br /><br /><i>".$product->description."</i>";
                array_push($actions, [
                    'action' => env('BASE_URL') . "/" . $user->username . "/product/" . $product->slug,
                    'text' => "Lihat Produk",
                    'type' => "link"
                ]);
            }
        }

        $saveResponse = Chat::create([
            'customer_id' => $payload['customer_id'],
            'user_id' => $payload['user_id'],
            'sender' => "bot",
            'body' => $response,
            'stemmed_body' => $stemmer->stem($response),
            'actions' => json_encode($actions)
        ]);

        return $saveResponse;
    }
}

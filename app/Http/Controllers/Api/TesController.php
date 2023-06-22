<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Http\Request;

use Sastrawi\Stemmer\StemmerFactory;
use Sastrawi\String\Span\Span;

class UserController extends Controller
{
    public static function me() {
        return Auth::guard('user')->user();
    }
    public function indexs() {
        $stemmerFactory = new StemmerFactory();
        $stemmer = $stemmerFactory->createStemmer();

        $span = new Span(4, 6);

        $string = "Riyan";
        echo $span->getCoveredText($string);
        // $output = $stemmer->stem($string);
    }
    public function index() {
        $groups = [
            "stok" => ["masih","ada","sedia"]
        ];
        return view('index');
    }
    public function removeConjunction($sentences) {
        return array_diff($sentences, ["yang","kalau","terus"]);
    }
    public function removeUnnecessary($sentences) {
        $toRemove = [
            "permisi","mau","tanya","halo","nggak","gak","ngga","tidak","ga","itu","anu","saya",
            "anda","kamu","diri","situ","aku","nama","pengen","ingin","aja","saja","mo","pengen","ingin",
            "ada","tinggal","berapa","apa","siapa"
        ];
        return array_diff($sentences, $toRemove);
    }
    public function penyebut($nilai) {
        $nilai = abs($nilai);
		$huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
		$temp = "";
		if ($nilai < 12) {
			$temp = " ". $huruf[$nilai];
		} else if ($nilai <20) {
			$temp = $this->penyebut($nilai - 10). " belas";
		} else if ($nilai < 100) {
			$temp = $this->penyebut($nilai/10)." puluh". $this->penyebut($nilai % 10);
		} else if ($nilai < 200) {
			$temp = " seratus" . $this->penyebut($nilai - 100);
		} else if ($nilai < 1000) {
			$temp = $this->penyebut($nilai/100) . " ratus" . $this->penyebut($nilai % 100);
		} else if ($nilai < 2000) {
			$temp = " seribu" . $this->penyebut($nilai - 1000);
		} else if ($nilai < 1000000) {
			$temp = $this->penyebut($nilai/1000) . " ribu" . $this->penyebut($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = $this->penyebut($nilai/1000000) . " juta" . $this->penyebut($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = $this->penyebut($nilai/1000000000) . " milyar" . penyebut(fmod($nilai,1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = $this->penyebut($nilai/1000000000000) . " trilyun" . penyebut(fmod($nilai,1000000000000));
		}     
		return $temp;
    }
    public function terbilang($nilai) {
        return trim($this->penyebut($nilai));
    }
    public function toIdr($number) {
        return 'Rp '.strrev(implode('.',str_split(strrev(strval($number)),3)));
    }
    public function getQuantity($sentences) {
        for ($i = 1; $i <= 100; $i++) {
            $numbers[] = $i;
        }
        $sentences = array_diff($sentences, ["buah", "biji", "item"]);
        $quantity = 0;
        foreach ($sentences as $sentence) {
            if (is_numeric($sentence)) {
                $sentences = array_diff($sentences, [$sentence]);
                $quantity += $sentence;
            }
        }
        return [
            'sentences' => $sentences,
            'quantity' => $quantity
        ];
    }
    public function getNumber($sentences) {
        $ret = "";
        foreach ($sentences as $sentence) {
            if (is_numeric($sentence)) {
                $sentences = array_diff($sentences, [$sentence]);
                $ret = $sentence;
            }
        }
        return [
            'number' => $ret,
            'sentences' => $sentences
        ];
    }
    public function conversation(Request $req) {
        $product = null;
        $shopID = $req->shop_id;
        $shop = ShopController::get([
            ['id', '=', $shopID]
        ])
        ->with('bot')
        ->first();
        $bot = $shop->bot;

        $customerQuery = Customer::where('token', $req->token);
        $customer = $customerQuery->first();

        $stemmerFactory = new StemmerFactory();
        $stemmer = $stemmerFactory->createStemmer();
        
        $sentence = $stemmer->stem($req->text);
        $sentences = explode(" ", $sentence);
        $sentencesOriginal = $sentence;

        if (isset($customer->last_context)) {
            $lastContext = $customer->last_context;
            $context = $lastContext;
            $lastSentence = Message::where('customer_id', $customer->id)->orderBy('created_at', 'DESC')->paginate(5);
            if ($lastContext == "buy-product") {
                $lastSentence = $lastSentence[0];
                $product = ProductController::get([
                    ['name', 'LIKE', '%'.$lastSentence->processed_body.'%']
                ])->first();
                $processBuy = $this->getQuantity($sentences);

                if ($product->available_stock < $processBuy['quantity']) {
                    $message = "Maaf kak ".$customer->name.", kami ngga punya stok sebanyak itu";
                    $customerQuery->update([
                        'last_context' => null,
                    ]);
                }else {
                    $customerQuery->update([
                        'last_context' => "buy-product-confirmation-whatsapp",
                    ]);

                    $totalPay = $processBuy['quantity'] * $product->price;

                    $placeOrder = OrderController::place([
                        'shop_id' => $shopID,
                        'product_id' => $product->id,
                        'customer_id' => $customer->id,
                        'quantity' => $processBuy['quantity'],
                        'total_pay' => $totalPay
                    ]);

                    $message = "Kamu akan beli ".$product->name." sebanyak ".$processBuy['quantity']." buah dengan total harga ".$this->toIdr($totalPay).". Boleh minta nomor Whatsappnya kak? Buat hubungi kamu lebih lanjut";
                }
            }else if ($lastContext == "buy-product-confirmation-whatsapp") {
                if (in_array('jangan', $sentences) || in_array('tidak', $sentences) || in_array('batal', $sentences) || in_array('ga', $sentences) || in_array('gak', $sentences)) {
                    $message = "Baik kak. Kalau mau order lagi, ".$bot->name." tunggu ya";
                    $sentences = array_diff($sentences, ["jangan","tidak","batal","ga","gak"]);
                    
                    $customerQuery->update([
                        'last_context' => null
                    ]);
                }else {
                    $lastSentence = $lastSentence[1];
                
                    $product = ProductController::get([
                        ['name', 'LIKE', '%'.$lastSentence->processed_body.'%']
                    ])->first();
                    
                    $customerQuery->update([
                        'phone' => $this->getNumber($sentences)['number'],
                        'last_context' => "buy-product-address"
                    ]);
                    
                    $message = "Baik. Ini pesanannya mau dikirim ke mana? Mohon tuliskan alamat lengkap beserta keterangannya (seperti warna pagar atau ciri rumah yang lainnya)";
                }
            }else if ($context == "buy-product-address") {
                $confirmOrder = OrderController::confirm($customer->id);
                $message = "Baik, terima kasih kak ".$customer->name." telah membeli dari ".$shop->name.".<br /><br /> Sekarang kak ".$customer->name." tolong segera melakukan pembayaran di <a href='".route('shop.invoice', [$shop->username, $confirmOrder->id])."'>halaman ini</a> untuk melanjutkan pesanannya";
                $orderData = OrderController::get([
                    ['id', $confirmOrder->id]
                ])
                ->with(['customer','product'])
                ->first();
                $customerQuery->update([
                    'address' => $req->text,
                    'last_context' => null
                ]);

                $sendEmail = ShopController::sendOrderNotification($shopID, $orderData);
            }
        }else {
            $sentences = $this->removeConjunction($sentences);
            
            $customQuestion = QuestionController::get([
                ['question_stemmed', 'LIKE', '%'.$sentencesOriginal.'%']
            ])->first();
            if ($customQuestion != "") {
                $context = "custom-question";
            }else {
                if (
                    in_array('ada', $sentences) || 
                    in_array('sedia', $sentences) || 
                    in_array('stok', $sentences) || 
                    in_array('punya', $sentences) ||
                    (in_array("sudah", $sentences) && in_array("laku", $sentences))
                ) {
                    $sentences = array_diff($sentences, ["ada", "sedia", "masih", "apa", "punya"]);
                    if (in_array("sekitar", $sentences) || in_array("harga", $sentences)) {
                        $context = "price-recommendation";
                    }else if(in_array('aja', $sentences)) {
                        $context = "catalog";
                    }else {
                        $context = "ask-stock";
                    }
                }else if (in_array('laku', $sentences) || in_array('ngapain', $sentences) || in_array('bisa', $sentences)) {
                    $context = "help";
                    $sentences = array_diff($sentences, ["laku","ngapain","aja","bisa"]);
                }else if (in_array('katalog', $sentences) || in_array('punya', $sentences) && in_array('apa', $sentences)) {
                    $context = "catalog";
                    $sentences = array_diff($sentences, ["katalog"]);
                }else if (in_array("apa", $sentences) || in_array('gimana', $sentences) || in_array('bagaimana', $sentences)) {
                    if (in_array("saya", $sentences) || in_array('aku', $sentences) || in_array('sudah', $sentences)) {
                        $context = "ask-order";
                        $sentences = array_diff($sentences, ["saya","aku","sudah"]);
                    }else {
                        $context = "ask-product";
                        $sentences = array_diff($sentences, ["apa","gimana","bagaimana"]);
                    }
                }else if(in_array("berapa", $sentences) || in_array("harga", $sentences)) {
                    if (in_array("sekitar", $sentences)) {
                        $context = "price-recommendation";
                        $sentences = array_diff($sentences, ["sekitar"]);
                    }else {
                        $context = "ask-price";
                        $sentences = array_diff($sentences, ["berapa","harga"]);
                    }
                }else if (in_array('beli', $sentences) || in_array('order', $sentences) || in_array('pesan', $sentences)) {
                    if (in_array('cara', $sentences) || in_array('gimana', $sentences)) {
                        $context = "how-to-buy";
                        $sentences = array_diff($sentences, ["cara","gimana"]);
                    } else {
                        if (in_array("status", $sentences) || in_array('sudah', $sentences) || in_array('gimana', $sentences)) {
                            $context = "ask-order";
                            $sentences = array_diff($sentences, ["saya","aku","sudah"]);
                        }else {
                            $context = "buy-product";
                            $sentences = array_diff($sentences, ["beli","order","pesan"]);
                        }
                    }
                }else if (in_array('semua', $sentences) && (in_array('barang', $sentences) || in_array('produk', $sentences))) {
                    $context = "all-product";
                    $sentences = array_diff($sentences, ["produk","semua","lihat"]);
                }else if (in_array('bantuan', $sentences) || in_array('ngerti', $sentences)) {
                    $context = "help";
                    $sentences = array_diff($sentences, ["bantuan","ngerti"]);
                }else if (in_array("batal", $sentences) || (in_array("tidak", $sentences) || in_array("nggak", $sentences)) && in_array("jadi", $sentences)) {
                    $context = "abort-anything";
                    $sentences = array_diff($sentences, ["tidak","ngerti","ngga","ga","nggak","gak"]);
                }else {
                    if (in_array("hai", $sentences) || in_array("halo", $sentences)) {
                        $context = "greetings";
                    }else {
                        $context = "nggak-ngerti";
                    }
                }
            }

            $sentences = $this->removeUnnecessary($sentences);

            // process data
            if ($context == "custom-question") {
                $message = $customQuestion->answer;
            }else if ($context == "all-product") {
                $message = "Untuk lihat semua produk, bisa cek <a href='".route('shop.products', $shop->username)."' target='_blank'>katalog</a> kami kak";
            }else if ($context == "ask-order") {
                $orders = OrderController::get([
                    ['customer_id', $customer->id],
                    ['confirmed_by_customer', 1]
                ])
                ->orderBy('created_at', 'DESC')
                ->take(5)
                ->with('product')
                ->get();

                $message = "Ini pesanan terakhir kak ".$customer->name."<br /><br />";
                foreach ($orders as $order) {
                    $message .= "#".$order->id."<br />";
                    $message .= "<b>".$order->product->name."</b><br />";
                    $message .= $order->quantity." x ".$this->toIdr($order->total_pay)."<br />";
                    $message .= $order->status."<br />";
                    $message .= "<a href='".route('shop.invoice', [$shop->username, $order->id])."'>Lihat detail invoice</a>";
                    $message .= "<br /><br />";
                }
            }else if ($context == "how-to-buy") {
                $message = 'Untuk beli produk kami, kamu tinggal bilang "<b>saya ingin beli &lt;nama-produk&gt;</b>"';
            }else if ($context == "help") {
                $message = "Biar ".$bot->name." bantu ya.<br /><br />Di sini kamu bisa menanyakan stok produk, harga produk, hingga rekomendasi produk yang sesuai dengan budgetmu.<br /><br />Oh iya, kamu juga bisa langsung order di sini loh, caranya kamu ketik aja <b>saya ingin beli &lt;nama produk&gt; &lt;banyaknya item&gt; buah</b>";
            }else if ($context == "greetings") {
                $message = "Hai juga, ".$customer->name.".  Ada yang bisa ".$bot->name." bantu?";
            }else if ($context == "catalog") {
                $message = "Untuk lihat katalog, bisa cek di <a href='".route('shop.catalog', $shop->username)."' target='_blank'>Halaman Katalog</a> kami";
            }else if ($context == "abort-anything") {
                $message = "Baik kak, pesanannya ".$bot->name." batalkan";
                $customerQuery->update([
                    'last_context' => null
                ]);
            }else if ($context == "buy-product") {
                $processBuy = $this->getQuantity($sentences);
                $product = ProductController::get([
                    ["name", "LIKE", "%".implode(" ", $processBuy['sentences'])."%"]
                ])->first();
                if ($product != null) {
                    $quantity = $processBuy['quantity'];
                    if ($product->available_stock == 0) {
                        $message = "Maaf kak, produk ".$product->name." stoknya sedang kosong";
                    }else if ($product->available_stock < $quantity) {
                        $message = "Maaf kak, kami hanya punya stok sebanyak ".$product->available_stock." buah";
                    }else {
                        if ($quantity == 0) {
                            $message = "Mau beli ".$product->name." berapa banyak?";
                            $customerQuery->update([
                                'last_context' => "buy-product"
                            ]);
                        }else {
                            $totalPay = $quantity * $product->price;
                            $message = "Siap kak. ".$shop->bot->name." ulangi ya.<br /><br />Kamu akan membeli ".$product->name." sebanyak ".$processBuy['quantity']." buah ya. Jadi totalnya ".$this->toIdr($totalPay).". Boleh minta nomor whatsappnya kak?";
                            
                            $customerQuery->update([
                                'last_context' => "buy-product-confirmation-whatsapp"
                            ]);

                            $placeOrder = OrderController::place([
                                'shop_id' => $shopID,
                                'product_id' => $product->id,
                                'customer_id' => $customer->id,
                                'quantity' => $quantity,
                                'total_pay' => $totalPay
                            ]);
                        }
                    }
                }else {
                    $message = "Maaf kak, kami tidak memiliki produk itu";
                }
            }else {
                $product = ProductController::get([
                    ["name", "LIKE", "%".implode(" ", $sentences)."%"]
                ])->first();

                if ($product != "") {
                    if ($context == "ask-price") {
                        // $message = "itu harganya ".$this->terbilang($product->price)." rupiah";
                        $message = $product->name." harganya ".$this->toIdr($product->price);
                    }else if ($context == "ask-product") {
                        $message = "Berikut ini deskrispi dari ".$product->name."<br /><br /><img class='lebar-100' src='".asset('storage/shop_assets/'.$shopID.'/product_image/'.$product->featured_image)."'><br /><br /><i>".$product->description."</i><br /><br />Harganya cuma ".$this->toIdr($product->price);
                        if ($product->available_stock <= 10) {
                            $message .= "<br /><br />Psst... barang ini cuma ada ".$product->available_stock." loh";
                        }
                    }else if ($context == "ask-stock") {
                        $message = "Ada kok, masih ada ".$product->available_stock." barang yang tersedia";
                        if ($product->available_stock <= 10) {
                            $message .= ". Buruan beli kak, sebelum kehabisan";
                        }
                    }
                }else {
                    if ($context == "nggak-ngerti") {
                        $message = "Maaf kak, aku ngga ngerti maksudnya";
                    }else {
                        $message = "Maaf, kami tidak memiliki produk itu";
                    }
                }
            }
        }

        $toCreate = [
            'shop_id' => $shopID,
            'customer_id' => $customer->id,
            'type' => "question",
            'body' => $req->text,
            'processed_body' => implode(" ", $sentences),
        ];
        if ($product != "") {
            $toCreate['interested_product'] = $product->id;
        }

        /**
         * question : from user
         * answer : from bot
         */
        $saveQuestion = Message::create($toCreate);
        sleep(1);
        $saveAnswer = Message::create([
            'shop_id' => $shopID,
            'customer_id' => $customer->id,
            'body' => $message,
            'type' => "answer",
            'processed_body' => ""
        ]);

        if ($product != "") {
            $update = ProductController::get([
                ['id', $product->id]
            ])->increment('asked_count', 1);
        }

        $conversations = Message::where([
            ['shop_id', $shopID],
            ['customer_id', $customer->id]
        ])->orderBy('created_at', 'DESC')->take(40)->get();

        return response()->json([
            'context' => $context,
            'product' => $product,
            'message' => $message,
            'conversations' => $conversations,
            'orig' => $sentencesOriginal,
            'question' => implode(" ", $sentences)
        ]);
    }
    public function introduction(Request $req) {
        $sentences = explode(" ", $req->text);
        $name = $this->removeUnnecessary($sentences);
        
        $name = ucwords(implode(" ", $name));
        $token = ShopController::generateRandomString(15);

        $saveData = Customer::create([
            'shop_id' => $req->shop_id,
            'name' => $name,
            'token' => $token,
        ]);

        return response()->json([
            'token' => $token,
            'conversations' => [
                ["body" => "Halo ".$name.", senang berkenalan dengan kamu"]
            ],
            'message' => "Halo ".$name.", senang berkenalan dengan kamu"
        ]);
    }
    public function getMyInfo(Request $req) {
        $token = $req->token;
        $shopID = $req->shop_id;
        $customer = Customer::where([
            ['token', $token],
            ['shop_id', $shopID]
        ])->first();

        return response()->json(['data' => $customer]);
    }
    public function getConversationInit(Request $req) {
        $token = $req->token;
        $shopID = $req->shop_id;

        $customer = Customer::where('token', $token)->first();
        
        $conversations = Message::where([
            ['shop_id', $shopID],
            ['customer_id', $customer->id]
        ])->orderBy('created_at', 'DESC')->take(8)->get();

        return $conversations;
    }
    public function loginPage() {
        // 
    }
    public function login(Request $req) {
        $token = $req->token;

        $customer = Customer::where('token', $token)->first();
        $loggingIn = Auth::guard('customer')->loginWithId($customer->id);
        if (!$loggingIn) {
            return redirect()->route('customer.loginPage')->withErrors(['Login gagal']);
        }

        return redirect()->route('customer.myOrders');
    }
    public function logout() {
        $loggingOut = Auth::guard('customer')->logout();
        return redirect()->route('customer.loginPage');
    }
    public function pricing() {
        return view('pricing');
    }
    public function about() {
        return view('about');
    }
    public function faq() {
        return view('faq');
    }
}

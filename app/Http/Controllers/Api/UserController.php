<?php

namespace App\Http\Controllers\Api;

use Str;
use Hash;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Mail\Register;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\Product;
use App\Models\ProductStat;
use App\Notifications\Register as NotificationsRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function testEmail(Request $request) {
        // $send = Mail::to('takotoko.com@gmail.com')->send(new Register());
        // Notification::send(new NotificationsRegister());
        $user = User::where('id', 7)->first();
        Notification::send($user, new NotificationsRegister());
    }
    public static function getByToken($token) {
        return User::where('token', $token);
    }
    public static function isEmail($data) {
        $d = explode("@", $data);
        if (@$d[1] != "") {
            return true;
        }
        return false;
    }
    public function auth(Request $request) {
        $user = User::where('token', $request->token)->with('premium')->first();
        $res = ['status' => 200];
        if ($user == "") {
            $res['status'] = 401;
        } else {
            $res['user'] = $user;
        }

        return response()->json($res);
    }

    public function register(Request $request) {
        $email = $request->email;
        // $username = explode("@", $email)[0];
        $username = Str::random(8);

        $user = User::where('email', $email)->first();
        if ($user != "") {
            // $username = Str::random(8);
        }
        $token = Str::random(32);

        $saveData = User::create([
            'package_id' => 1,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'username' => $username,
            'bio' => "I just found this wonderful app",
            'photo' => 'default',
            'cover' => 'default',
            'accent_color' => "#3498db",
            'font_family' => "inter",
            'use_custom_virtual_account_payment' => false,
            'flat_shipping_fee' => null,
            'is_active' => 0,
            'token' => $token,
        ]);

        $createOtp = OtpController::create($saveData, 'register');

        return response()->json([
            'status' => 200,
            'user' => $saveData,
            'message' => "Berhasil mendaftar"
        ]);
    }
    public function login(Request $request) {
        $email = $request->email;
        $password = $request->password;
        $user = null;

        if (self::isEmail($email)) {
            // Login with email
            $using = "email";
            $query = User::where('email', $email);
        } else {
            // Login with phone
            $using = "no. telepon";
            $query = User::where('phone', $email);
        }

        $user = $query->first();

        if ($user == "") {
            //  belum terdaftar
            $status = 500;
            $message = "Kami tidak dapat menemukan akunmu. Mungkin kamu ingin mendaftar akun baru?";
        } else {
            if (Hash::check($password, $user->password)) {
                $status = 200;
                $message = "Berhasil login";

                $token = Str::random(32);
                $query->update([
                    'token' => $token,
                ]);
                $user = $query->first();

                $createOtp = OtpController::create($user, 'login');
            } else {
                $status = 500;
                $message = "Login gagal. Kombinasi " . $using . " dan password tidak tepat";
            }
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'user' => $user,
        ]);
    }
    public function profile($username) {
        $user = User::where('username', $username)->first();

        return response()->json([
            'status' => 200,
            'user' => $user,
        ]);
    }
    public function update($username, Request $request) {
        $query = User::where('token', $request->token);
        $user = $query->first();
        $toUpdate = [
            'name' => $request->name,
            'bio' => $request->bio,
        ];

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoFileName = time() ."_".$photo->getClientOriginalName();
            $deleteOldPhoto = Storage::delete('public/user_photos/' . $user->photo);
            $toUpdate['photo'] = $photoFileName;
            $photo->storeAs('public/user_photos', $photoFileName);
        }
        if ($request->hasFile('cover')) {
            $cover = $request->file('cover');
            $coverFileName = time() ."_".$cover->getClientOriginalName();
            $deleteOldCover = Storage::delete('public/user_covers/' . $user->cover);
            $toUpdate['cover'] = $coverFileName;
            $cover->storeAs('public/user_covers', $coverFileName);
        }

        $update = $query->update($toUpdate);
        $user = $query->first();
        
        return response()->json([
            'status' => 200,
            'message' => 'Berhasil mengubah profil toko',
            'user' => $user,
        ]);
    }
    public function products($username) {
        $user = User::where('username', $username)->first();
        $products = Product::where('user_id', $user->id)
        ->with(['images'])
        ->get();

        return response()->json([
            'status' => 200,
            'products' => $products,
        ]);
    }
    public function customer($username, Request $request) {
        $user = User::where('username', $username)->first();
        $filter = [
            ['user_id', $user->id]
        ];
        if ($request->search != "") {
            array_push($filter, ['name', 'LIKE', '%'.$request->search.'%']);
        }
        $customers = Customer::where($filter)->get();

        return response()->json([
            'status' => 200,
            'customers' => $customers
        ]);
    }
    public function customerDetail($username, $customerID) {
        $customer = Customer::where('id', $customerID)
        ->with(['orders.items.product.image'])
        ->first();

        $histories = ProductStat::where('customer_id', $customer->id)
        ->orderBy('hit', 'DESC')
        ->with(['product.image'])
        ->take(5)
        ->get();
        $customer->histories = $histories;

        return response()->json([
            'status' => 200,
            'customer' => $customer
        ]);
    }

    public function home(Request $request) {
        $revenue = 0;
        $orderWaiting = 0;
        $orderPaid = 0;

        $user = User::where('token', $request->token)->first();
        $orders = CustomerOrder::where([
            ['user_id', $user->id],
            ['has_withdrawn', 0]
        ])
        ->get();

        foreach ($orders as $order) {
            if ($order->payment_status == null || $order->payment_status == "PENDING") {
                $orderWaiting += 1;
            } else {
                $orderPaid += 1;
                $revenue += $order->total_pay;
            }
        }

        return response()->json([
            'status' => 200,
            'user' => $user,
            'revenue' => $revenue,
            'order_waiting' => $orderWaiting,
            'order_paid' => $orderPaid,
        ]);
    }
}

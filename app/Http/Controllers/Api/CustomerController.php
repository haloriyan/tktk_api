<?php

namespace App\Http\Controllers\Api;

use Str;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerOrderItem;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function register($username, Request $request) {
        $user = User::where('username', $username)->first();
        $token = Str::random(32);
        $customerQuery = Customer::where([
            ['user_id', $user->id],
            ['email', $request->email]
        ]);
        $customer = $customerQuery->first();

        if ($customer == "") {
            $customer = Customer::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'token' => $token,
            ]);
        } else {
            $customerQuery->update(['token' => $token]);
        }

        $createOtp = OtpController::customerCreate($customer);

        return response()->json([
            'status' => 200,
            'message' => "Berhasil mendaftar",
            'token' => $token,
        ]);
    }
    public function auth($username, Request $request) {
        $user = User::where('username', $username)->first();
        $customer = Customer::where([
            ['token', $request->token],
            ['user_id', $user->id]
        ])->first();

        return response()->json([
            'status' => $customer == "" ? 429 : 200,
            'user' => $user
        ]);
    }
    public function cart($username, Request $request) {
        $user = User::where('username', $username)->first();
        $customer = Customer::where([
            ['user_id', $user->id],
            ['token', $request->token]
        ])->first();

        $items = CustomerOrderItem::where([
            ['customer_id', $customer->id]
        ])
        ->whereNull('order_id')
        ->with(['product.images'])
        ->get();

        return response()->json([
            'status' => 200,
            'items' => $items,
            'user' => $user,
        ]);
    }
}

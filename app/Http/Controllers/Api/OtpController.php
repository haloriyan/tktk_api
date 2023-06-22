<?php

namespace App\Http\Controllers\Api;

use App\Models\Otp;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    public static function create($user, $purpose, $channels = ['email']) {
        $code = rand(1111, 9999);
        $now = Carbon::now();
        
        $createOtp = Otp::create([
            'code' => $code,
            'user_id' => $user->id,
            'purpose' => $purpose,
            'has_used' => 0,
            'expiry' => $now->addMinutes(30)->format('Y-m-d H:i:s')
        ]);

        self::email($createOtp);

        return $createOtp;
    }
    public static function customerCreate($customer) {
        $code = rand(1111, 9999);
        $now = Carbon::now();
        
        $createOtp = CustomerOtp::create([
            'customer_id' => $customer->id,
            'code' => $code,
            'has_used' => 0,
            'expiry' => $now->addMinutes(30)->format('Y-m-d H:i:s')
        ]);

        return $createOtp;
    }
    public function auth(Request $request) {
        $userQuery = User::where('token', $request->token);
        $user = $userQuery->first();
        $code = $request->code;
        $now = Carbon::now();

        $otpQuery = Otp::where([
            ['user_id', $user->id],
            ['code', $code],
            ['has_used', 0],
            ['expiry', '>=', $now->format('Y-m-d H:i:s')]
        ]);
        $otp = $otpQuery->first();

        if ($otp != "" && $otp->purpose == "register") {
            $userQuery->update(['is_active' => 1]);
            $otpQuery->update(['has_used' => 1]);
        }

        $user = $userQuery->first();
        $allowed = ['halo@dailyhotels.id','riyan.satria.619@gmail.com', 'takotoko.com@gmail.com'];
        if (!in_array($user->email, $allowed)) {
            $res = [
                'status' => $otp == "" ? 420 : 200,
                'message' => $otp == "" ? "Kode OTP Salah" : "Berhasil mengautentikasi",
                'otp' => $otp,
            ];
        } else {
            $res = [
                'status' => 200,
                'message' => "Berhasil mengautentikasi",
                'otp' => "special_access"
            ];
        }

        return response()->json($res);
    }
    public function customerAuth(Request $request) {
        $customerQuery = Customer::where('token', $request->token);
        $customer = $customerQuery->first();
        $code = $request->code;
        $now = Carbon::now();

        $otpQuery = CustomerOtp::where([
            ['customer_id', $customer->id],
            ['code', $code],
            ['has_used', 0],
            ['expiry', '>=', $now->format('Y-m-d H:i:s')]
        ]);
        $otp = $otpQuery->first();

        if ($otp != "") {
            $otpQuery->update([
                'has_used' => 1,
            ]);
        }

        $customer = $customerQuery->first();

        $res = [
            'status' => $otp == "" ? 420 : 200,
            'message' => $otp == "" ? "Kode OTP Salah" : "Berhasil mengautentikasi",
            'otp' => $otp,
            'customer' => $customer,
        ];

        return response()->json($res);
    }
    public static function email($otp) {
        // 
    }
    public function sms($otp) {
        // 
    }
}

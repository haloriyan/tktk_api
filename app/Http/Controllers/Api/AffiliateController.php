<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affiliator;
use App\Models\Package;
use App\Models\PackageDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Str;

class AffiliateController extends Controller
{
    public function login(Request $request) {
        $query = Affiliator::where('email', $request->email);
        $affiliator = $query->first();
        $status = 200;
        $message = "Berhasil login";
        $token = null;

        if ($affiliator != "") {
            $check = Hash::check($request->password, $affiliator->password);
            if (!$check) {
                $status = 500;
                $message = "Password tidak tepat";
            } else {
                $token = Str::random(32);
                $query->update(['token' => $token]);
                $affiliator = $query->first();
            }
        } else {
            $status = 500;
            $message = "Kami tidak dapat menemukan akun Anda";
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'token' => $token,
            'affiliator' => $affiliator,
        ]);
    }
    public function coupon(Request $request) {
        $affiliator = Affiliator::where('token', $request->token)->first();
        $coupons = PackageDiscount::where('affiliator_id', $affiliator->id)->get();
        $packages = Package::all();

        return response()->json([
            'status' => 200,
            'coupons' => $coupons,
            'packages' => $packages,
        ]);
    }
}

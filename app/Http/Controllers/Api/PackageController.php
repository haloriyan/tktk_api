<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageDiscount;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function get() {
        $packages = Package::all();
        
        return response()->json([
            'status' => 200,
            'packages' => $packages,
        ]);
    }
    public function createDiscount(Request $request) {
        $saveData = PackageDiscount::create([
            'package_id' => $request->package_id,
            'promo_code' => $request->code,
            'discount_amount' => $request->amount,
            'expiration_date' => $request->expiration_date,
        ]);
        
        return response()->json([
            'status' => 200,
            'message' => "Berhasil membuat kode promo"
        ]);
    }
    public function deleteDiscount(Request $request) {
        $data = PackageDiscount::where('id', $request->id);
        $coupon = $data->first();
        $deleteData = $data->delete();

        return response()->json([
            'status' => 200,
            'message' => "Berhasil menghapus kode promo " . $coupon->promo_code
        ]);
    }
}

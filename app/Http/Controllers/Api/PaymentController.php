<?php

namespace App\Http\Controllers\Api;

use Xendit\Xendit as Xendit;
use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function getVA($paymentID) {
        $secretKey = env('XENDIT_MODE') == 'sandbox' ? env('XENDIT_SECRET_KEY_SANDBOX') : env('XENDIT_SECRET_KEY');
        Xendit::setApiKey($secretKey);
        $va = \Xendit\VirtualAccounts::retrieve($paymentID);

        return response()->json([
            'va' => $va
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use Str;
use Storage;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductAdditional;
use App\Models\ProductImage;
use App\Models\ProductSchedule;
use App\Models\ProductStat;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function get($id, Request $request) {
        $product = Product::where('id', $id)->orWhere('slug', $id)
        ->with(['images', 'additionals', 'schedules'])
        ->first();
        $user = User::where('id', $product->user_id)->first();
        $customer = Customer::where('token', $request->token)->first();

        // hit stat
        if ($request->nohit != "yayaya") {
            $statFilter = [['product_id', $product->id]];
            $hitProps = ['product_id' => $product->id,'hit' => 1,'user_id' => $user->id];
            if ($customer != "") {
                $statFilter[] = ['customer_id', $customer->id];
                $hitProps['customer_id'] = $customer->id;
            }
            $statQuery = ProductStat::where($statFilter);
            $stat = $statQuery->first();

            if ($stat == "") {
                $hitStat = ProductStat::create($hitProps);
            } else {
                $statQuery->increment('hit');
            }
        }

        return response()->json([
            'status' => 200,
            'product' => $product,
            'user' => $user,
        ]);
    }
    public function mine(Request $request) {
        $user = User::where('token', $request->token)->first();
        $products = Product::where([
            ['user_id', $user->id],
            ['name', 'LIKE', '%'.$request->search.'%']
        ])
        ->with(['images'])
        ->get();

        return response()->json([
            'status' => 200,
            'products' => $products,
        ]);
    }
    public function create(Request $request) {
        $user = User::where('token', $request->token)->first();
        $isService = $request->is_service;
        $images = $request->file('images');

        $toCreate = [
            'user_id' => $user->id,
            'name' => $request->name,
            'type' => $request->type,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'price' => $request->price,
            'is_service' => $isService == false ? 0 : 1,
            'is_visible' => true,
        ];
        if ($request->type == "digital" && !$isService) {
            $productFile = $request->file('product_file');
            $productFileName = $productFile->getClientOriginalName();
            $productFile->storeAs('public/u' . $user->id .'/product_files', $productFileName);

            $toCreate['filename'] = $productFileName;
            $toCreate['download_limit'] = $request->download_limit === 'null' ? null : $request->download_limit;
            $toCreate['download_expiration'] = $request->download_expiration === 'null' ? null : $request->download_expiration;
        } else if ($request->type != "digital" && !$isService) {
            $toCreate['dimension'] = $request->dimension;
            $toCreate['weight'] = $request->weight;
        } 

        $saveData = Product::create($toCreate);

        if ($isService) {
            // save additional service and schedules
            $additionals = json_decode($request->additionals);
            foreach ($additionals as $additional) {
                $saveAdditional = ProductAdditional::create([
                    'product_id' => $saveData->id,
                    'name' => $additional->name,
                    'price' => $additional->price,
                ]);
            }

            // save schedules
            $schedules = json_decode($request->schedules);
            foreach ($schedules as $schedule) {
                $saveSchedule = ProductSchedule::create([
                    'product_id' => $saveData->id,
                    'day' => $schedule->day,
                    'start_hour' => Carbon::parse($schedule->start_hour)->format('H:i'),
                    'end_hour' => Carbon::parse($schedule->end_hour)->format('H:i'),
                    'max_order' => $schedule->max_orders,
                ]);
            }
        }

        foreach ($images as $image) {
            $imgFileName = $saveData->id."_".$image->getClientOriginalName();
            $saveImage = ProductImage::create([
                'product_id' => $saveData->id,
                'filename' => $imgFileName,
                'priority' => 0,
            ]);
            $image->storeAs('public/u' . $user->id .'/product_images', $imgFileName);
        }

        return response()->json([
            'status' => 200,
            'message' => "Berhasil menambahkan produk"
        ]);
    }
    public function delete(Request $request) {
        $query = Product::where('id', $request->id);
        $product = $query->with(['images'])->first();

        $deleteData = $query->delete();
        foreach ($product->images as $image) {
            $deleteImages = Storage::delete('public/u'.$product->user_id.'/product_images/' . $image->filename);
        }

        if ($product->type == "digital") {
            $deleteFile = Storage::delete('public/u'.$product->user_id.'/product_files/' . $product->filename);
        }

        return response()->json([
            'status' => 200,
            'message' => "Berhasil menghapus produk"
        ]);
    }
    public function statistic($id, Request $request) {
        $now = Carbon::now();
        $startDate = $now->format('Y-m-d H:i:s');
        $endDate = $now->subDays($request->range)->format('Y-m-d H:i:s');

        $summaryRaw = ProductStat::where('product_id', $id)
        ->whereBetween('created_at', [$endDate, $startDate])
        ->with('customer')
        ->get();

        $summary = [
            'labels' => [],
            'datasets' => []
        ];
        $customers = [];
        foreach (CarbonPeriod::create($endDate, $startDate) as $date) {
            $hit = 0;
            foreach ($summaryRaw as $data) {
                if (Carbon::parse($data->created_at)->format('Y-m-d') == $date->format('Y-m-d')) {
                    $hit += $data->hit;
                }
            }
            array_push($summary['labels'], $date->format('d M'));
            array_push($summary['datasets'], $hit);
        }

        foreach ($summaryRaw as $summ) {
            if (isset($customers['customer_' . $summ->customer_id])) {
                $customers['customer_' . $summ->customer_id]['hit'] += $summ->hit;
            } else {
                $customers['customer_' . $summ->customer_id]['customer'] = $summ->customer;
                $customers['customer_' . $summ->customer_id]['hit'] = $summ->hit;
            }
        }

        return response()->json([
            'status' => 200,
            'summary' => $summary,
            'customers' => $customers,
        ]);
    }

    public function schedule($id) {
        Carbon::setLocale('id_ID');
        $now = Carbon::now();
        $lastDay = Carbon::now()->addDays(30);
        $schedulesRaw = ProductSchedule::where('product_id', $id)->get();
        $availableSchedules = json_decode(json_encode(CarbonPeriod::create($now, $lastDay)), false);
        $schedules = [];
        $disabled = [];

        foreach ($schedulesRaw as $schedule) {
            if (!isset($scheduleDays[$schedule->day])) {
                $schedules[$schedule->day] = [
                    'start' => $schedule->start_hour,
                    'end' => $schedule->end_hour,
                ];
            }
        }
        
        foreach ($availableSchedules as $i => $available) {
            $theDay = Carbon::parse($available)->isoFormat('dddd');
            $availableDay = ucwords($theDay);
            if (!isset($schedules[$availableDay])) {
                array_push($disabled, Carbon::parse($available)->format('Y-m-d H:i:s'));
                array_splice($availableSchedules, $i, 1);
            }
        }
        foreach ($availableSchedules as $i => $available) {
            $availableSchedules[$i] = Carbon::parse($available)->format('Y-m-d H:i:s');
        }

        return response()->json([
            'status' => 200,
            'available' => $availableSchedules,
            'disabled_dates' => $disabled,
        ]);
    }
}

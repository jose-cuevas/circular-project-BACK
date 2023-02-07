<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = DB::table('purchases')
            ->join('prices', function ($join) {
                $join->on('purchases.country', '=', 'prices.country')
                    ->on(DB::raw('YEAR(purchases.purchase_date)'), '=', 'prices.year')
                    ->on('purchases.medicine', '=', 'prices.medicine');
            })
            ->select('purchases.id', 'purchases.patient_id', 'purchases.country', 'purchases.purchase_date', 'purchases.medicine', 'purchases.quantity', 'prices.price')
            ->get();

        return response()->json([
            "status" => true,
            "message" => 'Fetching data successfully',
            "data" => $purchases
        ], 200);
    }

    public function store(Request $request)
    {
        $purchase = new Purchase;
        $purchase->country = $request->country;
        $purchase->medicine = $request->medicine;
        $purchase->quantity = $request->quantity;
        $purchase->patient_id = $request->patient_id;
        $purchase->purchase_date = $request->purchase_date;

        $purchase->save();
        // $purchase_id = $purchase->id;

        $year = Carbon::createFromFormat('Y-m-d', $request->purchase_date)->format('Y');

        $price = new Price;
        $price->country = $request->country;
        $price->year = $year;
        $price->medicine = $request->medicine;
        $price->price = $request->price;

        $price->save();
        $price_id = $price->id;


        $purchase->prices()->attach($price_id);

        return response()->json([
            "status" => true,
            "message" => 'Posting data successfully',
            "data-purchase" => $purchase,
            "data-price" => $price
        ], 201);
    }

    public function show(Purchase $purchase)
    {
        // $purchase = Purchase::find(1);

        return $purchase;
    }

    public function update(Request $request, Purchase $purchase)
    {

        $purchase->country = $request->country;
        $purchase->patient_id = $request->patient_id;
        $purchase->medicine = $request->medicine;
        $purchase->quantity = $request->quantity;
        $purchase->purchase_date = $request->purchase_date;

        // $purchase->update();

Price


        return response()->json([
            "status" => true,
            "message" => 'Updating data successfully',
            "data-purchase" => $purchase,
        ], 201);
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->prices()->detach($priceid);
        return Purchase::all()->prices();
    }
}

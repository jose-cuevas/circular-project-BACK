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
        $purchases = DB::table('price_purchase')
            ->join('purchases', 'purchases.id', '=', 'price_purchase.purchase_id')
            ->join('prices', 'prices.id', '=', 'price_purchase.price_id')
            ->select('purchases.id', 'purchases.patient_id', 'purchases.country', 'purchases.medicine', 'purchases.quantity', 'prices.price')
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $purchase = Purchase::where('country', $request->country)
            ->where('medicine', $request->medicine)
            ->where('quantity', $request->quantity)
            ->where('purchase_date', $request->purchase_date)
            ->first();

        if ($purchase == null) {
            $purchase = new Purchase;
            $purchase->country = $request->country;
            $purchase->medicine = $request->medicine;
            $purchase->quantity = $request->quantity;
            $purchase->patient_id = "no patient id";
            $purchase->purchase_date = $request->purchase_date;
            $purchase->save();

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

        return response()->json([
            "status" => false,
            "message" => 'This puschase already exits',
            "data-purchase" => $purchase
        ], 404);
    }

    public function update(Request $request, $id)
    {
        $purchase = Purchase::find($id);

        if ($purchase) {
            $year = (int)Carbon::createFromFormat('Y-m-d', $purchase->purchase_date)->format('Y');

            $price = Price::where('country', $purchase->country)
                ->where('year', $year)
                ->where('medicine', $purchase->medicine)
                ->first();

            $purchase->country = $request->country;
            $purchase->patient_id = $request->patient_id;
            $purchase->medicine = $request->medicine;
            $purchase->quantity = (int)$request->quantity;
            $purchase->purchase_date = $request->purchase_date;
            $purchase->update();

            $price->country = $request->country;
            $price->year = $year;
            $price->medicine = $request->medicine;
            $price->price = (int)$request->price;
            $price->update();

            return response()->json([
                "status" => true,
                "message" => 'Updating data successfully',
                "data-purchase" => $purchase,
                "data-price" => $price
            ], 201);
        }

        return response()->json([
            "status" => false,
            "message" => 'Imposible to update, purchase id no exits',
            "data-purchase" => $purchase,
        ], 404);
    }

    public function destroy(Purchase $purchase)
    {
        $year = Carbon::createFromFormat('Y-m-d', $purchase->purchase_date)->format('Y');
        $priceToDelete = DB::table('prices')
            ->select()
            ->where('country', $purchase->country)
            ->where('year', $year)
            ->where('medicine', $purchase->medicine)
            ->first();

        if ($priceToDelete) {
            $purchase->prices()->detach($priceToDelete);
        }
        $purchase->delete();

        return response()->json([
            "status" => true,
            "message" => 'Deleted successfully',
            "data-purchase" => $purchase,
            "data-price" => $priceToDelete
        ], 200);
    }
}

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
            ->select('purchases.id', 'purchases.country', 'purchases.patient_id', 'purchases.purchase_date', 'purchases.medicine', 'purchases.quantity', 'prices.price')
            ->orderBy('id', 'DESC')
            ->get();
        
        return response()->json($purchases);        
    }

    public function store(Request $request)
    {
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

    public function update(Request $request, $id)
    {
        $purchase = Purchase::find($id);
        $purchase = Purchase::where('id', $id)->first();        

        $year = (int)Carbon::createFromFormat('Y-m-d', $purchase->purchase_date)->format('Y');

        $price = DB::table('prices')
            ->where('country', $purchase->country)
            ->where('year', $year)
            ->where('medicine', $purchase->medicine)
            ->pluck('id');        
        
        $priceToUpdate_ID = $price[0];        
        $priceToUpdate =  Price::find($priceToUpdate_ID);         

        $purchase->country = $request->country;
        $purchase->patient_id = $request->patient_id;
        $purchase->medicine = $request->medicine;
        $purchase->quantity = (int)$request->quantity;
        $purchase->purchase_date = $request->purchase_date;
        $purchase->update();        

        $priceToUpdate->country = $request->country;
        $priceToUpdate->year = $year;
        $priceToUpdate->medicine = $request->medicine;
        $priceToUpdate->price = (int)$request->price;
        $priceToUpdate->update();             

        return response()->json([
            "status" => true,
            "message" => 'Updating data successfully',
            "data-purchase" => $purchase,

        ], 201);
    }

    public function destroy(Purchase $purchase)
    {
        $year = Carbon::createFromFormat('Y-m-d', $purchase->purchase_date)->format('Y');
        $priceToDelete = DB::table('prices')
            ->select()
            ->where('country', $purchase->country)
            ->where('year', $year)
            ->where('medicine', $purchase->medicine)
            ->get();
        
        if (sizeof($priceToDelete) > 0) {
            $purchase->prices()->detach($priceToDelete[0]->id);
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

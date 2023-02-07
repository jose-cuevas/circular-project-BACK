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
            ->leftJoin('prices', function ($join) {
                $join->on('purchases.country', '=', 'prices.country')
                    ->on(DB::raw('YEAR(purchases.purchase_date)'), '=', 'prices.year')
                    ->on('purchases.medicine', '=', 'prices.medicine');
            })
            ->select('purchases.id', 'purchases.country', 'purchases.purchase_date', 'purchases.medicine', 'purchases.quantity', 'prices.price')
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json([
            "status" => true,
            "message" => 'Fetching data successfully',
            "data" => $purchases
        ], 200);
    }

    public function store(Request $request)
    {
        
        // $validated = $request->validate([
        //     'country' => 'required|string',
        //     'medicine' => 'required|string',
        //     'quantity' => 'required|string',
        //     'patient_id' => 'required|string',
        //     'purchase_data' => 'required|string',
        //     'price' => 'required | integer'
        // ]);

// ! 1 SOLUTION Duplicates objects
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

        

// * 2 SOLUTION  Avoiding duplicates
        // $purchase = Purchase::firstOrNew([
        //     'country' => $request->country,
        //     'medicine' => $request->medicine,
        //     'quantity' => $request->quantity,
        //     'patient_id' => $request->patient_id,
        //     'purchase_date' => $request->purchase_date
        // ]);

        // $purchase->save();

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
        $year = Carbon::createFromFormat('Y-m-d', $purchase->purchase_date)->format('Y');
        $priceToUpdate = DB::table('prices')
            ->select()
            ->where('country', $purchase->country)
            ->where('year', $year)
            ->where('medicine', $purchase->medicine)
            ->get();

        $priceToUpdate[0]->country = $request->country;
        $priceToUpdate[0]->year = $request->year;
        $priceToUpdate[0]->medicine = $request->medicine;
        $priceToUpdate[0]->price = $request->price;


        $purchase->country = $request->country;
        $purchase->patient_id = $request->patient_id;
        $purchase->medicine = $request->medicine;
        $purchase->quantity = $request->quantity;
        $purchase->purchase_date = $request->purchase_date;

        $purchase->update();
        $purchase->prices()->attach($request->price);

        return response()->json([
            "status" => true,
            "message" => 'Updating data successfully',
            "data-purchase" => $purchase,
            "data-price" => $priceToUpdate
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

        return $priceToDelete;

        // $purchase->prices()->detach($priceToDelete[0]->id);
        // $purchase->delete();
        // $priceToDelete->delete();

        foreach ($priceToDelete as $key => $price) {
            $purchase->prices()->detach($priceToDelete[$key]->id);
            $purchase->delete();
        }
        $purchase->delete();


        // return response()->json([
        //     "status" => true,
        //     "message" => 'Deleted successfully',
        //     "data-purchase" => $purchase,
        //     "data-price" => $priceToDelete
        // ], 200);
    }
}

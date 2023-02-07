<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'year',
        'medicine',
        'price'
    ];

    public $timestamps = false;
    public function purchases()
    {
        return $this->belongsToMany(Purchase::class)
        ->withPivot('purchase_id', 'price_id');
    }
}

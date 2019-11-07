<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'products';

    //Relacion de muchos a uno(Muchos post puede
    // pertenercer a un solo business)
    public function business(){
       return $this->belongsTo('App\Business','business_id');
    }

    public function category(){
        return $this->belongsTo('App\Category','category_id');
     }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';
    protected $hidden = [
        'password', 'notification_token',
    ];
    
    //Relacion de uno a muchos
    public function products(){
        return $this->hasMany('App\Products');
    }

}


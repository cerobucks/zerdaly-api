<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $table = 'business';

        //Relacion de uno a muchos
        public function products(){
            return $this->hasMany('App\Products');
        }

    protected $hidden = [
            'password',
            'dob',
            'id_image',
            'face_image',
    ];
}

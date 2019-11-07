<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $table = 'delivery';
    protected $hidden = [
        'password',
        "id_image",
        "face_image",
        "moto_image"
    ];

}

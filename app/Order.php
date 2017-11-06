<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'orders';
    protected $fillable = [
		'name',
		'address',
		'city',
		'state',
		'zip_code',
		'country',
		'phone_number',
    ];

}

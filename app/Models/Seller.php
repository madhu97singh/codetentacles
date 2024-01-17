<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'email', 'mobile_no', 'country', 'state', 'skills', 'password'];

    protected $hidden = ['password'];

    protected $casts = [
        'skills' => 'json',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

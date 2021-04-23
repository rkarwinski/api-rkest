<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 

class Parameter extends Model 
{

    protected $table    = 'parametros';
    protected $fillable = [
        'nome',
        'valor'
    ];

    public $timestamps = false;

    
}
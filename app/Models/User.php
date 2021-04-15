<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 

class User extends Model 
{

    protected $table    = 'usuarios';
    protected $fillable = [
        'nome',
        'email',
        'senha',
        'data_criado'
    ];
    protected $casts = [
        'data_criado' => 'Timestamp'
    ];

    public $timestamps = false; 



}
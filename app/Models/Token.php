<?php

namespace App\Models; 
//use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;


class Token extends Model 
{

    protected $table    = 'usuarios_tokens';
    protected $fillable = [
        'id_user',
        'token',
        'data_gerado',
        'data_expirado'
    ];

    public $timestamps = false;

}
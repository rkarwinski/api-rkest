<?php

namespace App\Models; 
//use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use DB;


class Token extends Model 
{

    protected $table    = 'usuarios_tokens';
    protected $fillable = [
        'id_user',
        'token',
        'data_gerado',
        'data_expirado'
    ];

    protected $primaryKey = "id_user";
    public $timestamps = false;

    public function insertOrUpdate($data)
    {
        $sql = "INSERT INTO usuarios_tokens (id_user, token, data_gerado, data_expirado) 
                VALUES ('{$data['id_user']}', '{$data['token']}', '{$data['data_gerado']}', '{$data['data_expirado']}')
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                data_gerado = VALUES(data_gerado), 
                data_expirado = VALUES(data_expirado)";

        return DB::statement($sql);
    }

}
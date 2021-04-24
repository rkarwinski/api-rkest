<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 
use DB;

class Parameter extends Model 
{

    protected $table    = 'parametros';
    protected $fillable = [
        'nome',
        'valor'
    ];

    protected $primaryKey = "nome";
    public $timestamps = false;

    public function insertOrUpdate(string $name, string $data)
    {
        $sql = "INSERT INTO parametros (nome, valor) 
                VALUES ('{$name}', '{$data}')
                ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor)"; 

        return DB::statement($sql);
    }

}
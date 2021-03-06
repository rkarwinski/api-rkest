<?php

namespace App\Models; 
//use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Models\Token;

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

    public function validateLogin( array $data ) :bool
    {
        $data['senha'] = sha1($data['senha']);
        $user = $this->select('*')
                ->where([
                    ['email', '=', $data['email']],
                    ['senha', '=', $data['senha']]
                ])
                ->limit(1)
                ->get();
        
        $user = $user->toArray();

        if(isset($user[0])){
            return true;
        }else{
            return false; 
        }
        
    }

    public function createToken( array $data ) :array
    {
        $return = []; 
        $tokens = new Token();

        $data['id_user'] = $data['email']; //email
        $data['token']   = Hash::make($data['senha'].date('YmdHis'));
        $data['data_gerado'] = date('Y-m-d H:i:s');
        $data['data_expirado'] = date('Y-m-d H:i:s', strtotime('+2 Hours'));

        if($tokens->insertOrUpdate($data)){
            return $data;
        }else{
            return $return;
        }

    }

    public function hasLogged( string $token, string $userId ) : bool
    {
        $mToken = new Token();
        $user = $this->find($userId);

        if(isset($user->email)){
            $userToken = $mToken->find($user->email);
            if(isset($userToken->token) && $userToken->token == $token){
                if( strtotime(date('Y-m-d H:s:i')) <= strtotime($userToken->data_expirado) ){
                    return true; 
                }
            }
        }
        
        return false;
    }



}
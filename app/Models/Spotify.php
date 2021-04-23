<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 
use DB;

class Spotify extends Model 
{
    
    protected $table    = 'spotify_tokens_usuarios';
    protected $fillable = [
        'id_user',
        'token',
        'reflesh_token',
        'data_gerado',
        'data_expirado'
    ];

    protected $primaryKey = "id_user";
    public $timestamps = false;

    const URL_REDIRECT = 'http://localhost:8000/api/spotify/login';

    public function insertOrUpdate($data)
    {
        $sql = "INSERT INTO spotify_tokens_usuarios (id_user, token, reflesh_token, data_gerado, data_expirado) 
                VALUES ('{$data['id_user']}', '{$data['token']}', '{$data['reflesh_token']}', '{$data['data_gerado']}', '{$data['data_expirado']}')
                ON DUPLICATE KEY UPDATE 
                token = VALUES(token), 
                reflesh_token = VALUES(reflesh_token), 
                data_gerado = VALUES(data_gerado), 
                data_expirado = VALUES(data_expirado)";

        return DB::statement($sql);
    }

    public function insertOrUpdatePlaylist($data)
    {
        $count = 0;
        $sql = "INSERT INTO spotify_playlists (id, 
                                               user_id, 
                                               titulo, 
                                               url_externa, 
                                               url_api, 
                                               url_musicas, 
                                               url_imagem, 
                                               total_musicas, 
                                               data_criado, 
                                               data_atualizado) 
                VALUES ";
        
        foreach ($data as $key => $value) {
            $virgula = '';
            $count++;

            if( $count < count($data) ){
                $virgula = ',';
            }

            $sql .= "('{$value['id']}', 
                      '{$value['user_id']}', 
                      '{$value['titulo']}', 
                      '{$value['url_externa']}', 
                      '{$value['url_api']}', 
                      '{$value['url_musicas']}', 
                      '{$value['url_imagem']}', 
                       {$value['total_musicas']}, 
                       NOW(), 
                       NOW())".$virgula; 
        }
                
        $sql .= "ON DUPLICATE KEY UPDATE 
                    titulo = VALUES(titulo), 
                    url_externa = VALUES(url_externa), 
                    url_api = VALUES(url_api), 
                    url_musicas = VALUES(url_musicas), 
                    url_imagem = VALUES(url_imagem), 
                    data_atualizado = NOW()";

        //print_r($sql); die;

        return DB::statement($sql);
    }

    public function hasLogged( string $email )
    {
        if($this->find($email)){
            return true;
        }

        return false;
    }

    public function isValideToken( string $email )
    {
        $userToken = $this->find($email);
        if(isset($userToken->token)){
            if( strtotime(date('Y-m-d H:s:i')) <= strtotime($userToken->data_expirado) ){
                return true; 
            }
        }

        return false;
    }

    public function refleshLogin( $email )
    {

        $token = $this->find($email);

        if(!isset($token->reflesh_token)){
            return false; 
        }

        $mParameter = new Parameter();
        
        $pUrl      = $mParameter->find('spotify-url_auth_api');
        $pClientId = $mParameter->find('spotify-client_id');
        $pSecretId = $mParameter->find('spotify-client_secret');

        $url  = $pUrl->valor . 'api/token';
        $basic_token = 'Basic ' . base64_encode($pClientId->valor . ':' .$pSecretId->valor);

        $body = [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $token->reflesh_token
                ];

        $header = [
                    'Authorization' => $basic_token,
                    'Content-Type'  => 'application/x-www-form-urlencoded'
                ];

        $mBase = new Base();

        $cResponse = $mBase->urlCall($url, 'POST', 'application/x-www-form-urlencoded', $body, $header);
        
        if(!is_object($cResponse)){
            $cResponse = json_decode($cResponse);
        }

        //salva o token no banco
        if(isset($cResponse->access_token)){
            session_start();
            $data['id_user'] = $email;
            $data['token'] = $cResponse->access_token;
            $data['reflesh_token'] = $token->reflesh_token;
            $data['data_gerado'] = date('Y-m-d H::s:i');
            $data['data_expirado'] = date('Y-m-d H:i:s', strtotime('+50 minutes'));

            if($this->insertOrUpdate($data)){
                return true;
            }
        }

        return false; 

    }
    
}
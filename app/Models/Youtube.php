<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 
use DB;

class Youtube extends Model 
{
    
    protected $table    = 'youtube_tokens_usuarios';
    protected $fillable = [
        'id_user',
        'token',
        'reflesh_token',
        'data_gerado',
        'data_expirado'
    ];

    protected $primaryKey = "id_user";
    public $timestamps = false;

    const URL_REDIRECT = 'http://localhost:8000/api/youtube/login';

    public function insertOrUpdate($data)
    {
        $sql = "INSERT INTO youtube_tokens_usuarios (id_user, token, reflesh_token, data_gerado, data_expirado) 
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
        $sql = "INSERT INTO youtube_playlists (id, 
                                               user_id, 
                                               canal_id,
                                               titulo, 
                                               url_imagem, 
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
                      '{$value['canal_id']}', 
                      '{$value['titulo']}', 
                      '{$value['url_imagem']}', 
                       NOW(), 
                       NOW())".$virgula; 
        }
                
        $sql .= "ON DUPLICATE KEY UPDATE 
                    titulo = VALUES(titulo), 
                    url_imagem = VALUES(url_imagem), 
                    data_atualizado = NOW()";

        //print_r($sql); die;

        return DB::statement($sql);
    }

    public function insertOrUpdateTrackPlaylist($data)
    {
        $count = 0;
        $sql = "INSERT INTO youtube_playlist_tracks (id, 
                                               playlist_id, 
                                               video_id, 
                                               titulo, 
                                               canal, 
                                               canal_id, 
                                               url_imagem, 
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
                      '{$value['playlist_id']}', 
                      '{$value['video_id']}', 
                      '{$value['titulo']}', 
                      '{$value['canal']}', 
                      '{$value['canal_id']}', 
                      '{$value['url_imagem']}', 
                       NOW(), 
                       NOW())".$virgula; 
        }
                
        $sql .= "ON DUPLICATE KEY UPDATE 
                    titulo = VALUES(titulo), 
                    canal = VALUES(canal), 
                    video_id = VALUES(video_id), 
                    canal_id = VALUES(canal_id), 
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

        $pUrl      = $mParameter->find('youtube-url_auth2_api');
        $pClientId = $mParameter->find('youtube-client_id');
        $pSecretId = $mParameter->find('youtube-client_secret');

        $url  = $pUrl->valor . 'token';
        $body = [
                'client_id'     => $pClientId->valor,
                'client_secret' => $pSecretId->valor,
                'grant_type' => 'refresh_token',
                'refresh_token'  => $token->reflesh_token
                ];

        $header = [
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

    public function getIdUserYoutube( string $token ) : string
    {

        $mParameter = new Parameter();
        $mBase = new Base();

        $pUrl = $mParameter->find('youtube-url_api');
        $url  = $pUrl->valor . "me";

        $header = [
                 'Authorization' => $token,
                ];

        $response = $mBase->urlCall($url, 'GET', '', [], $header);

        if(!is_object($response)){
            $response = json_decode($response);
        }

        if(isset($response->id)){
            return $response->id; 
        }

        return '';

    }   

    public function searchTrack( string $token, array $list ) : array
    {
        $return = []; 
        $mParameter = new Parameter();
        $mBase = new Base();

        $pUrl = $mParameter->find('youtube-url_api');

        if(is_array($list) && count($list) > 0){
            foreach ($list as $key => $value) {
                $parametros  = "?q={$mBase->tirarAcentos(str_replace(' ', '%20', $value['titulo']))}%20{$mBase->tirarAcentos(str_replace(' ', '%20', $value['artista']))}";
                $parametros .= "&type=track";
                $parametros .= "&market=BR";

                $url  = "{$pUrl->valor}search{$parametros}";

                $header = [
                    'Authorization' => $token,
                   ];
   
                $response = $mBase->urlCall($url, 'GET', '', [], $header);
        
                if(!is_object($response)){
                    $response = json_decode($response);
                }   

                $count = 0;
                if(isset($response->tracks) && $response->tracks->total > 0){
                    foreach ($response->tracks->items as $key => $track) {
                        $count++; 
                        if(mb_strpos(strtoupper($track->name), strtoupper($value['titulo'])) !== false && $count == 1){
                            $tmp = $track->uri; // . ' | ' . $track->name; 
                            $return['uris'][] = $tmp; 
                            unset($tmp);
                        }
                        
                    }
                }

            }
        }

        return $return;        
        
    }   
    
}
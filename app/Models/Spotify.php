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
    public $incrementing = false;
    protected $keyType = 'string';
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

    public function insertOrUpdateTrackPlaylist($data)
    {
        $count = 0;
        $sql = "INSERT INTO spotify_playlist_tracks (id, 
                                               playlist_id, 
                                               titulo, 
                                               artista, 
                                               url_externa, 
                                               url_api, 
                                               url_imagem, 
                                               id_artista, 
                                               url_artista, 
                                               id_album, 
                                               titulo_album, 
                                               url_album, 
                                               url_external_album,
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
                      '{$value['titulo']}', 
                      '{$value['artista']}', 
                      '{$value['url_externa']}', 
                      '{$value['url_api']}', 
                      '{$value['url_imagem']}', 
                      '{$value['id_artista']}', 
                      '{$value['url_artista']}', 
                      '{$value['id_album']}', 
                      '{$value['titulo_album']}', 
                      '{$value['url_album']}', 
                      '{$value['url_external_album']}',
                       NOW(), 
                       NOW())".$virgula; 
        }
                
        $sql .= "ON DUPLICATE KEY UPDATE 
                    titulo = VALUES(titulo), 
                    artista = VALUES(artista), 
                    url_externa = VALUES(url_externa), 
                    url_api = VALUES(url_api), 
                    url_imagem = VALUES(url_imagem), 
                    id_artista = VALUES(id_artista), 
                    url_artista = VALUES(url_artista), 
                    id_album = VALUES(id_album), 
                    titulo_album = VALUES(titulo_album), 
                    url_album = VALUES(url_album), 
                    url_external_album = VALUES(url_external_album), 
                    data_atualizado = NOW()";

        //print_r($sql); die;

        return DB::statement($sql);
    }

    public function getUrlPlaylist( string $id ) :string 
    {
        $return = '';
        
        $sql = "SELECT url_musicas FROM spotify_playlists WHERE id = '{$id}' LIMIT 1";
        $aReturn = DB::select($sql);
        
        if(isset($aReturn[0])){
            $return = $aReturn[0]->url_musicas; 
        }

        return $return;
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

    public function getIdUserSpotify( string $token ) : string
    {

        $mParameter = new Parameter();
        $mBase = new Base();

        $pUrl = $mParameter->find('spotify-url_api');
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

        $pUrl = $mParameter->find('spotify-url_api');

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

    public function listAll(string $id ) : array
    {
        $return = [];
        
        $sql = "SELECT sp.* FROM spotify_playlists sp
        INNER JOIN usuarios u ON u.email = sp.user_id
        WHERE u.id = '{$id}'";

        $aReturn = DB::select($sql);
        
        if(isset($aReturn) && is_array($aReturn)){
            $return = $aReturn; 
        }

        return $return;
    }

    public function listMusicsAll(string $id ) : array
    {
        $return = [];
        
        $sql = "SELECT * FROM spotify_playlist_tracks WHERE playlist_id = '{$id}'";

        $aReturn = DB::select($sql);
        
        if(isset($aReturn) && is_array($aReturn)){
            $return = $aReturn; 
        }

        return $return;
    }
    
}
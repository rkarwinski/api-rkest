<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use App\Models\Youtube;
use App\Models\Base;
use App\Models\Parameter;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class YoutubeController extends Controller
{

    private $model; 

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Youtube $mYoutube)
    {
        $this->model = $mYoutube;
    }

    public function grantPermission( $id )
    {
        try {
            session_start();
            
            $mUser = new User();
            $user  = $mUser->find($id);
            $email = $user->email;

            $_SESSION['youtube']["user_id"] = $email;
            $redirect_url = YOUTUBE::URL_REDIRECT; 

            $mParameter = new Parameter();
            $pUrl      = $mParameter->find('youtube-url_auth_api');
            $pClientId = $mParameter->find('youtube-client_id');
            
            $parametros  = "?client_id={$pClientId->valor}";
            $parametros .= '&access_type=offline';
            $parametros .= "&redirect_uri={$redirect_url}";
            $parametros .= '&response_type=code';
            $parametros .= '&scope=https://www.googleapis.com/auth/youtube';
            

            $url = $pUrl->valor . $parametros;
        
            return redirect()->to($url);
    
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function saveLogin()
    {
        if(isset($_GET['code'])){
            $code = $_GET['code'];

            if(empty($code)){
                return response()->json(['error' => 'Codigo de permissão em branco'], Response::HTTP_FORBIDDEN); 
            }

            $mParameter = new Parameter();
            if($mParameter->insertOrUpdate('youtube-code_token',$code)){
                //gera o token 
                $pUrl      = $mParameter->find('youtube-url_auth2_api');
                $pClientId = $mParameter->find('youtube-client_id');
                $pSecretId = $mParameter->find('youtube-client_secret');

                $url  = $pUrl->valor . 'token';
                $body = [
                        'grant_type' => 'authorization_code',
                        'code'       => $code,
                        'client_id'     => $pClientId->valor,
                        'client_secret' => $pSecretId->valor,
                        'redirect_uri'  => YOUTUBE::URL_REDIRECT
                        ];

                $header = [
                         'Content-Type'  => 'application/x-www-form-urlencoded'
                        ];

                $mBase = new Base();

                $cResponse = $mBase->urlCall($url, 'POST', 'application/x-www-form-urlencoded', $body, $header);

                if(!is_object($cResponse)){
                    $cResponse = json_decode($cResponse);
                }

                if(isset($cResponse->access_token)){
                    session_start();
                    $data['id_user'] = $_SESSION['youtube']["user_id"];
                    $data['token'] = $cResponse->access_token;
                    $data['reflesh_token'] = $cResponse->refresh_token;
                    $data['data_gerado'] = date('Y-m-d H::s:i');
                    $data['data_expirado'] = date('Y-m-d H:i:s', strtotime('+50 minutes'));

                    if($this->model->insertOrUpdate($data)){
                        return response()->json(['message'=>'success'], Response::HTTP_OK);
                    }
                }

                return response()->json(['error' => $cResponse->error], Response::HTTP_FORBIDDEN); 
                
            }
            
        }else{
            return response()->json(['error' => 'Permissão negada pelo usuario'], Response::HTTP_FORBIDDEN); 
        }

    }

    public function getPlaylistsForUser(Request $request)
    {
        $mBase = new Base();
        $mParameter = new Parameter();

        $data = $request->all();

        if(!$this->model->hasLogged($data['email'])){
            return response()->json(['error' => 'Usuario não tem login registrado'], Response::HTTP_FORBIDDEN); 
        }

        if(!$this->model->isValideToken( $data['email'] )){
            if(!$this->model->refleshLogin( $data['email'] )){
                return response()->json(['error' => 'Usuario sem acesso. Não foi possivel renovar o token'], Response::HTTP_FORBIDDEN); 
            }
        }

        $uToken = $this->model->find($data['email']);
        $pUrl   = $mParameter->find('youtube-url_api');
        $token  = 'Bearer ' . $uToken->token;
        $body   = [];
        $header = [
                 'Authorization' => $token,
                ];
        
        $next = null; 

        do {
            $url   = $pUrl->valor . 'playlists?part=snippet&mine=true';
            if($next != null){
                $url .= '&pageToken=' . $next; 
                $next = null; 
            }

            $response = $mBase->urlCall($url, 'GET', '', $body, $header);

            if(!is_object($response)){
                $response = json_decode($response);
            }


            if(isset($response->items) && count($response->items) > 0){
                foreach ($response->items as $key => $value) {
                    $tmp['id']          = $value->id; 
                    $tmp['user_id']     = $data['email'];
                    $tmp['titulo']      = addslashes($value->snippet->title); 
                    $tmp['canal_id']    = $value->snippet->channelId; 
                    $tmp['url_imagem']  = $value->snippet->thumbnails->default->url;

                    $insert[] = $tmp;
                }

            }    

            $next = (isset($response->nextPageToken)) ? $response->nextPageToken : null;

        } while ($next != null);


        if( isset($insert) && count($insert) > 0 ){
            if($this->model->insertOrUpdatePlaylist($insert)){
                return response()->json( ['message' => 'Success', 'total' => count($insert)], Response::HTTP_OK);  
            }

            return response()->json(['error' => 'Erro ao salvar playlists na Base de dados'], Response::HTTP_INTERNAL_SERVER_ERROR);
 
        }

        return response()->json(['error' => 'Nenhuma Playlist Processada'], Response::HTTP_INTERNAL_SERVER_ERROR);

    }

    public function getMusicsForPlaylists(Request $request)
    {
        $mBase = new Base();
        $mParameter = new Parameter();

        $data = $request->all();

        if(!$this->model->hasLogged($data['email'])){
            return response()->json(['error' => 'Usuario não tem login registrado'], Response::HTTP_FORBIDDEN); 
        }

        if(!$this->model->isValideToken( $data['email'] )){
            if(!$this->model->refleshLogin( $data['email'] )){
                return response()->json(['error' => 'Usuario sem acesso. Não foi possivel renovar o token'], Response::HTTP_FORBIDDEN); 
            }
        }

        $uToken = $this->model->find($data['email']);
        $pUrl   = $mParameter->find('youtube-url_api');
        $token  = 'Bearer ' . $uToken->token;
        $body   = [];

        $header = [
                 'Authorization' => $token,
                ];

        $next = null; 
        
        do {
            $url   = $pUrl->valor . "playlistItems?part=snippet&playlistId={$data['id_playlist']}";
            if($next != null){
                $url .= '&pageToken=' . $next; 
                $next = null; 
            }

            $response = $mBase->urlCall($url, 'GET', '', $body, $header);

            if(!is_object($response)){
                $response = json_decode($response);
            }

            if(isset($response->items) && count($response->items) > 0){
                foreach ($response->items as $key => $value) {

                    $tmp['id']          = $value->id; 
                    $tmp['playlist_id'] = $data['id_playlist'];
                    $tmp['video_id']    = $value->snippet->resourceId->videoId;
                    $tmp['titulo']      = addslashes($value->snippet->title); 
                    $tmp['canal']       = isset($value->snippet->videoOwnerChannelTitle) ? $value->snippet->videoOwnerChannelTitle : ''; 
                    $tmp['canal_id']    = isset($value->snippet->videoOwnerChannelId) ? $value->snippet->videoOwnerChannelId : ''; 
                    $tmp['url_imagem']  = isset($value->snippet->thumbnails->default->url) ? $value->snippet->thumbnails->default->url : '';

                    $insert[] = $tmp;
                }

            }    
            $next = (isset($response->nextPageToken)) ? $response->nextPageToken : null;

        } while ($next != null);

        if( isset($insert) && count($insert) > 0 ){
            if($this->model->insertOrUpdateTrackPlaylist($insert)){
                return response()->json( ['message' => 'Success', 'total' => count($insert)], Response::HTTP_OK);  
            }

            return response()->json(['error' => 'Erro ao salvar as Musica da playlist na Base de dados'], Response::HTTP_INTERNAL_SERVER_ERROR);
 
        }

        return response()->json(['error' => 'Nenhuma Musica da Playlist Processada'], Response::HTTP_INTERNAL_SERVER_ERROR);

    }

    public function createPlaylistForUser(Request $request)
    {   
        $mBase = new Base();
        $mParameter = new Parameter();

        $data = $request->all();

        if(!$this->model->hasLogged($data['email'])){
            return response()->json(['error' => 'Usuario não tem login registrado'], Response::HTTP_FORBIDDEN); 
        }

        if(!$this->model->isValideToken( $data['email'] )){
            if(!$this->model->refleshLogin( $data['email'] )){
                return response()->json(['error' => 'Usuario sem acesso. Não foi possivel renovar o token'], Response::HTTP_FORBIDDEN); 
            }
        }

        $uToken = $this->model->find($data['email']);
        $token  = 'Bearer ' . $uToken->token;

        $pUrl = $mParameter->find('youtube-url_api');
        $url  = $pUrl->valor . "playlists?part=snippet,status";

        $body = [
            'snippet' => [
                'title' => addslashes($data['titulo']),
                'description' => addslashes($data['descricao'])
            ], 
            'status'=>[
                'privacyStatus'=> $data['status']
            ]
        ];
        
        $header = [
                 'Authorization' => $token,
                 'Content-Type'  => 'application/json'
                ];

        $response = $mBase->urlCall($url, 'POST', 'application/json', $body, $header);

        if(!is_object($response)){
            $response = json_decode($response);
        }

        if(isset($response->id)){
            $tmp['id']          = $response->id; 
            $tmp['user_id']     = $data['email'];
            $tmp['titulo']      = addslashes($response->snippet->title); 
            $tmp['canal_id']    = $response->snippet->channelId; 
            $tmp['url_imagem']  = $response->snippet->thumbnails->default->url;
    
            $insert[] = $tmp;
            unset($tmp);
    
            if( isset($insert) && count($insert) > 0 ){
                if($this->model->insertOrUpdatePlaylist($insert)){
                    return response()->json( ['message' => 'Success', 'total' => count($insert)], Response::HTTP_OK);  
                }
    
                return response()->json(['error' => 'Erro ao salvar playlists na Base de dados'], Response::HTTP_INTERNAL_SERVER_ERROR);
     
            }
        }

        if(isset($response->error)){
            return response()->json(['error' => $response->error->message], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['error' => 'Erro ao salvar playlists na Base de dados'], Response::HTTP_INTERNAL_SERVER_ERROR);
            
    }

    public function addTracksInPlaylist(Request $request)
    {
        $mBase = new Base();
        $mParameter = new Parameter();

        $data = $request->all();

        if(!$this->model->hasLogged($data['email'])){
            return response()->json(['error' => 'Usuario não tem login registrado'], Response::HTTP_FORBIDDEN); 
        }

        if(!$this->model->isValideToken( $data['email'] )){
            if(!$this->model->refleshLogin( $data['email'] )){
                return response()->json(['error' => 'Usuario sem acesso. Não foi possivel renovar o token'], Response::HTTP_FORBIDDEN); 
            }
        }

        $listTracks = $data['musicas'];

        $uToken = $this->model->find($data['email']);
        $token  = 'Bearer ' . $uToken->token;

        
        //como buscar 
        $tracks = $this->model->searchTrack($token, $listTracks);

        $pUrl = $mParameter->find('youtube-url_api');
        $url  = $pUrl->valor . "playlistItems?part=snippet";

        $add = 0;
        $err = 0;

        if(isset($tracks['uris']) && is_array($tracks['uris']) && count($tracks['uris']) > 0){
            foreach ($tracks['uris'] as $key => $value) {
                $body = [
                    'snippet' => [
                        'playlistId' => $data['id_playlist'],
                        'resourceId' => [
                            'kind' => 'youtube#video',
                            'videoId' => $value
                        ]
                    ]
                ];
    
                $header = [
                        'Authorization' => $token,
                        'Content-Type'  => 'application/json'
                        ];
    
                $response = $mBase->urlCall($url, 'POST', 'application/json', $body, $header);
    
                if(!is_object($response)){
                    $response = json_decode($response);
                }

                if(isset($response->kind)){
                    $add++; 
                }else{
                    $err++; 
                }
            }

        }else{
            return response()->json(['error' => 'Nenhuma musica encontrada'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
        return response()->json( ['message' => 'Success', 'total' => count($tracks['uris']), 'inserted' => $add, 'error' => $err], Response::HTTP_OK);  

    }
    
}

<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use App\Models\Spotify;
use App\Models\Base;
use App\Models\Parameter;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class SpotifyController extends Controller
{

    private $model; 

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Spotify $mSpotify)
    {
        $this->model = $mSpotify;
    }

    public function grantPermission( $id )
    {
        try {
            session_start();
            
            $mUser = new User();
            $user  = $mUser->find($id);
            $email = $user->email;

            $_SESSION['spotify']["user_id"] = $email;
            $redirect_url = SPOTIFY::URL_REDIRECT; 

            $mParameter = new Parameter();
            $pUrl      = $mParameter->find('spotify-url_auth_api');
            $pClientId = $mParameter->find('spotify-client_id');
            
            $parametros  = "?client_id={$pClientId->valor}";
            $parametros .= '&response_type=code';
            $parametros .= '&scope=user-top-read%20playlist-modify-public%20playlist-modify-private%20user-read-currently-playing%20user-library-modify%20playlist-read-private%20user-library-read%20playlist-read-collaborative';
            $parametros .= "&redirect_uri={$redirect_url}";

            $url = $pUrl->valor . 'authorize' . $parametros;
        
            //gera o redirecionamento para a permissao 
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
            if($mParameter->insertOrUpdate($code)){
                //gera o token 
                $pUrl      = $mParameter->find('spotify-url_auth_api');
                $pClientId = $mParameter->find('spotify-client_id');
                $pSecretId = $mParameter->find('spotify-client_secret');

                $url  = $pUrl->valor . 'api/token';
                $basic_token = 'Basic ' . base64_encode($pClientId->valor . ':' .$pSecretId->valor);

                $body = [
                        'grant_type' => 'authorization_code',
                        'code'       => $code,
                        'redirect_uri' => SPOTIFY::URL_REDIRECT
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
                    $data['id_user'] = $_SESSION['spotify']["user_id"];
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
        $pUrl   = $mParameter->find('spotify-url_api');
        
        $url   = $pUrl->valor . 'me/playlists';
        $token = 'Bearer ' . $uToken->token;

        $body = [];

        $header = [
                 'Authorization' => $token,
                ];

        do {
            $next = null; 

            if($next != null && empty($url)){
                $url = $response->next; 
            }

            $response = $mBase->urlCall($url, 'GET', '', $body, $header);

            if(!is_object($response)){
                $response = json_decode($response);
            }

            if(isset($response->total) && $response->total > 0){
                foreach ($response->items as $key => $value) {
                    $tmp['id']          = $value->id; 
                    $tmp['user_id']     = $data['email'];
                    $tmp['titulo']      = addslashes($value->name); 
                    $tmp['url_externa'] = $value->external_urls->spotify; 
                    $tmp['url_api']     = $value->href; 
                    $tmp['url_musicas'] = $value->tracks->href; 
                    $tmp['total_musicas'] = (int)$value->tracks->total; 

                    if(isset($value->images) && count($value->images) > 0){
                        $tmp['url_imagem']  = $value->images[0]->url;
                    }else{
                        $tmp['url_imagem']  = ''; 
                    }

                    $insert[] = $tmp;
                }

            }    

            $url = '';
            $next = (isset($response->next)) ? $response->next : null;

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
        $pUrl   = $mParameter->find('spotify-url_api');
        
        $sUrl  = $this->model->getUrlPlaylist($data['id_playlist']);
        $url   = $sUrl.'?market=BR';
        $token = 'Bearer ' . $uToken->token;

        $body = [];

        $header = [
                 'Authorization' => $token,
                ];

        do {
            $next = null; 

            if($next != null && empty($url)){
                $url = $response->next; 
            }

            $response = $mBase->urlCall($url, 'GET', '', $body, $header);

            if(!is_object($response)){
                $response = json_decode($response);
            }

            if(isset($response->total) && $response->total > 0){
                foreach ($response->items as $key => $value) {
                    $tmp['id']          = $value->track->id; 
                    $tmp['playlist_id'] = $data['id_playlist'];
                    $tmp['titulo']      = addslashes($value->track->name); 
                    $tmp['artista']     = addslashes($value->track->album->artists[0]->name); 
                    $tmp['id_artista']  = $value->track->album->artists[0]->id; 
                    $tmp['url_artista'] = $value->track->album->artists[0]->href; 
                    $tmp['url_externa'] = $value->track->preview_url;
                    $tmp['url_api']     = $value->track->href; 
                    $tmp['titulo_album']   = $value->track->album->name; 
                    $tmp['id_album']       = $value->track->album->id; 
                    $tmp['url_album']      = $value->track->album->href; 
                    $tmp['url_external_album'] = $value->track->album->external_urls->spotify; 

                    if(isset($value->track->album->images) && count($value->track->album->images) > 0){
                        $tmp['url_imagem']  = $value->track->album->images[0]->url;
                    }else{
                        $tmp['url_imagem']  = ''; 
                    }

                    $insert[] = $tmp;
                }

            }    

            $url = '';
            $next = (isset($response->next)) ? $response->next : null;

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

        //capturar o id user 
        $idSpotifyUser = $this->model->getIdUserSpotify($token);
        
        if(empty($idSpotifyUser)){
            return response()->json(['error' => 'ID do Usuario não encontrado'], Response::HTTP_FORBIDDEN); 
 
        }
                    
        $pUrl = $mParameter->find('spotify-url_api');
        $url  = $pUrl->valor . "users/{$idSpotifyUser}/playlists";

        $body = [
            'name'   => addslashes($data['titulo']),
            'public' => $data['publica'],
            'collaborative' => $data['colaborativa'],
            'description'   => addslashes($data['descricao'])
        ];

        $header = [
                 'Authorization' => $token,
                ];

        $response = $mBase->urlCall($url, 'POST', 'application/json', $body, $header);

        if(!is_object($response)){
            $response = json_decode($response);
        }

        if(isset($response->id)){
            $tmp['id']          = $response->id; 
            $tmp['user_id']     = $data['email'];
            $tmp['titulo']      = addslashes($response->name); 
            $tmp['url_externa'] = $response->external_urls->spotify; 
            $tmp['url_api']     = $response->href; 
            $tmp['url_musicas'] = $response->tracks->href; 
            $tmp['total_musicas'] = (int)$response->tracks->total; 
            $tmp['url_imagem']    = ''; 
    
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

        $tracks = $this->model->searchTrack($token, $listTracks);

        $pUrl = $mParameter->find('spotify-url_api');
        $url  = $pUrl->valor . "playlists/{$data['id_playlist']}/tracks";

        $body = [
            'uris'   => $tracks['uris']
        ];

        $header = [
                 'Authorization' => $token,
                ];

        $response = $mBase->urlCall($url, 'POST', 'application/json', $body, $header);

        if(!is_object($response)){
            $response = json_decode($response);
        }
        
        if(isset($response->snapshot_id)){
            return response()->json( ['message' => 'Success', 'total' => count($tracks['uris'])], Response::HTTP_OK);  
        }

        if(isset($response->error)){
            return response()->json(['error' => $response->error->message], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json(['error' => 'Não foi possivel adicionar as musicas na Playlist'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    
}

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
    
}

<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request; 
use App\Models\Spotify;
use App\Models\Base;
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

    public function generateToken()
    {
        try {
            $mBase = new Base();
            $tokens = $mBase->urlCall();
            return response()->json($tokens, Response::HTTP_OK);
    
        } catch (QueryException $exception) {
            return response()->json(['error' => 'Erro interno do Servidor'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    
}

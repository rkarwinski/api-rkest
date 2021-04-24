<?php


use Symfony\Component\HttpFoundation\Response; 

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/api/users', 'UserController@getAll');
$router->get('/api/parameters', 'ParameterController@getAll');

$router->group(['prefix' => '/api/user'], function() use ($router){
    $router->get('/{id}', 'UserController@get');
    $router->post('/', 'UserController@create');
    $router->post('/login', 'UserController@login');
    $router->put('/{id}', 'UserController@update');
    $router->delete('/{id}', 'UserController@delete');
});

$router->group(['prefix' => '/api/spotify'], function() use ($router){
    $router->get('/login', 'SpotifyController@saveLogin');
    $router->get('/grantPermission/{id}', 'SpotifyController@grantPermission');
    $router->post('/playlists', 'SpotifyController@getPlaylistsForUser');
    $router->post('/playlists/tracks', 'SpotifyController@getMusicsForPlaylists');
    $router->post('/playlists/create', 'SpotifyController@createPlaylistForUser');
    $router->post('/playlists/tracks/add', 'SpotifyController@addTracksInPlaylist');
    //$router->put('/{id}', 'UserController@update');
    //$router->delete('/{id}', 'UserController@delete');
});


$router->group(['prefix' => '/api/parameter'], function() use ($router){
    $router->get('/{id}', 'ParameterController@get');
    $router->post('/', 'ParameterController@create');
    $router->put('/{id}', 'ParameterController@update');
    $router->delete('/{id}', 'ParameterController@delete');
});

$router->get('/', function () use ($router) {
    return response()->json(null, Response::HTTP_BAD_REQUEST);
});

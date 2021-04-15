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

$router->group(['prefix' => '/api/user'], function() use ($router){
    $router->get('/{id}', 'UserController@get');
    $router->post('/', 'UserController@create');
    $router->put('/{id}', 'UserController@update');
    $router->delete('/{id}', 'UserController@delete');
});

$router->get('/', function () use ($router) {
    return response()->json(null, Response::HTTP_BAD_REQUEST);
});

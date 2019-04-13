<?php

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

// $router->get('/', function () use ($router) {
//     return $router->app->version();
// });

//Beow route will register the user in the system
$router->post('/register', [
    'as' => 'register', 'uses' => 'UserController@register'
]);

//Below route will be used to generate a JWT token
$router->post('/generateToken', [
    'as' => 'generate.token', 'uses' => 'JWTController@generateToken'
]);

$router->group(['middleware' => 'jwt.auth', 'prefix' => 'api_v1'], function () use ($router) {
   $router->get('/', function() use($router){
    return $router->app->version();
   });
});

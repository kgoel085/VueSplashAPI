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

$router->options(
    '/{any:.*}', 
    [
        'middleware' => ['CORS'], 
        function (){ 
            return response(['status' => 'success']); 
        }
    ]
);
$router->group(['middleware' => 'CORS'], function($router){
    $router->group(['prefix' => 'api_v1'], function () use ($router) {
        //Below route will register the user in the system
        $router->post('/register', [
            'as' => 'register', 'uses' => 'UserController@register'
        ]);

        //Below route will be used to generate a JWT token
        $router->post('/generateToken', [
            'as' => 'generate.token', 'uses' => 'JWTController@generateToken'
        ]);

        $router->group(['middleware' => 'jwt.auth', 'prefix' => 'photos'], function() use ($router){
            $router->get('/{picId}', 'PhotosController@getPhoto');
            $router->get('/', 'PhotosController@index');
        });

        $router->group(['middleware' => 'jwt.auth', 'prefix' => 'users'], function() use ($router){
            $router->get('/{username}', 'SplashUserController@getUser');
        });
    });
});

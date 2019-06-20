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
        'middleware' => 'CORS',
        function (){ 
            return response(['status' => 'success']); 
        }
    ]
);
$router->group(['middleware' => 'CORS'], function($router){
    $router->group(['prefix' => 'vuesplash'], function () use ($router) {

        //Below route will be used to generate a JWT token
        $router->post('/generateToken', ['as' => 'global.generateToken', 'uses' => 'JWTController@generateToken']);

        //Below route will register the user in the system
        $router->post('/register', ['as' => 'global.register', 'middleware' => 'jwt.auth', 'uses' => 'UserController@register']);

        // Returns variable required for login
        $router->get('/initLogin', ['as' => 'init.login', 'middleware' => 'jwt.auth', 'uses' => 'LoginController@initiate']);

        // Get unsplash user auth token for user details
        $router->post('/oauth', ['as' => 'ini.oauth', 'middleware' => 'jwt.auth', 'uses' => 'LoginController@oauth']);

        $router->group(['middleware' => 'jwt.auth', 'prefix' => 'photos'], function() use ($router){
            $router->get('/{picId}/action/{action}', ['as' => 'photos.action', 'uses' => 'EndpointController@getPhoto']);
            $router->get('/{picId}', ['as' => 'photos.specificPhoto', 'uses' => 'EndpointController@getPhoto']);
            $router->get('/', ['as' => 'photos.all', 'uses' => 'EndpointController@getPhoto']);
        });

        $router->group(['middleware' => 'jwt.auth', 'prefix' => 'users'], function() use ($router){
            $router->get('/{username}/action/{action}', ['as' => 'user.action', 'uses' => 'EndpointController@getUser']);
            $router->get('/{username}', ['as' => 'user.specificUser', 'uses' => 'EndpointController@getUser']);
        });

        $router->group(['middleware' => 'jwt.auth', 'prefix' => 'collections'], function() use ($router){
            $router->get('/{id}/{action}', ['as' => 'collection.action', 'uses' => 'EndpointController@getCollection']);
            $router->get('/{id}', ['as' => 'collection.specificCollection', 'uses' => 'EndpointController@getCollection']);
            $router->get('/', ['as' => 'collection.all', 'uses' => 'EndpointController@getCollection']);
        });

        $router->group(['middleware' => 'jwt.auth', 'prefix' => 'search'], function() use ($router){
            $router->get('/{id}/{action}', ['as' => 'data.Search', 'uses' => 'EndpointController@getSearch']);
        });
    });
});

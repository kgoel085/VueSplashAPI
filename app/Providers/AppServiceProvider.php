<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    public function boot(){
        // Sql String length error fix
        Schema::defaultStringLength(191);

        //To add custom roues -- Example
        // $this->app['router']->group(['prefix' => 'my-module'], function ($router) {
        //     $router->get('my-route', 'MyVendor\MyPackage\MyController@action');
        //     $router->get('my-second-route', 'MyVendor\MyPackage\MyController@otherAction');
        // });

        //Adding lumen can() mapping to check for whether user can do an action( have permission ) or not
        // Example - $user->can('edit-settings') 
        
        // ****************************************************************************** //
        // ONLY ENABLE THE FOLLOWING CODE AFTER MIGRATING THE ROLES, PERMISSION TABLES
        // ****************************************************************************** //

        // Permission::get()->map(function($permission){
        //     Gate::define($permission->slug, function($user) use ($permission){
        //         return $user->hasPermissionTo($permission);
        //     });
        // });
    }
}

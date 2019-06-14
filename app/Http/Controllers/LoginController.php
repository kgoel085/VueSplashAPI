<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class LoginController extends BaseController
{
    public function initiate(){
        $returnArr = [
            'client_id' => env('CLIENT_ACCESS_KEY'),
            'response_type' => 'code'
        ];

        return response()->json(['success' => $returnArr], 200);
    }
}

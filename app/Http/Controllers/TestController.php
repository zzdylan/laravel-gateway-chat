<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;

class TestController extends Controller {

    public function index(Request $request) {
        dd($request->server());
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EasyWeChat\Foundation\Application;

class WechatController extends Controller {

    public function wechatOauth(Application $wechat) {
        $oauth = $wechat->oauth;
        // 获取 OAuth 授权结果用户信息
        $user = $oauth->user();
        session(['wechat_userinfo' => $user->getOriginal()]);
        return redirect(session('target_url'));
    }

}

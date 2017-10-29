<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GatewayClient\Gateway;
use Image;
use Carbon\Carbon;
use DB;
use zgldh\QiniuStorage\QiniuStorage;

class ChatController extends Controller {

    public function enter(Request $request) {
        $now = Carbon::now();
        $dayOfWeek = $now->dayOfWeek;
        $weekArr = ['星期天', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];
        $time = $now->toDateTimeString() . ' ' . $weekArr[$dayOfWeek];
        return view('chat.index', ['time' => $time]);
    }

    public function chatRoom() {
        return view('chat.chatbox.index');
    }

    public function bind(Request $request) {
        Gateway::$registerAddress = '127.0.0.1:1238';
        $token = $request->input('token');
        $clientId = $request->input('client_id');
        $user = DB::table('users')->where('token', $token)->first();
        if (!$user) {
            $user = (object) $this->generateUser();
            $pushData = ['type' => 'set_token', 'token' => $user->token];
            Gateway::sendToClient($clientId, json_encode($pushData, true));
        }
        $pushData = ['type' => 'inform', 'content' => "欢迎{$user->nickname}进入聊天室"];
        Gateway::sendToAll(json_encode($pushData, true));
        return ['status' => 1, 'msg' => "绑定成功,uid为$user->id", 'client_id' => $clientId];
    }

    public function sendMessage(Request $request) {
        Gateway::$registerAddress = '127.0.0.1:1238';
        $token = $request->input('token');
        $content = htmlentities($request->input('content'));
        $client_id = $request->input('client_id');
        $user = DB::table('users')->where('token', $token)->first();
        $pushData = ['type' => 'message', 'content' => $content, 'nickname' => $user->nickname, 'avatar' => $user->avatar];
        Gateway::sendToAll(json_encode($pushData, true), null, $client_id);
        return ['status' => 1, 'msg' => '发送成功', 'nickname' => $user->nickname, 'avatar' => $user->avatar, 'content' => $content];
    }

    public function uploadImg(Request $request) {
        Gateway::$registerAddress = '127.0.0.1:1238';
        $img = $request->file('img');
        $token = $request->input('token');
        $client_id = $request->input('client_id');
        $user = DB::table('users')->where('token', $token)->first();
        $width = Image::make($img->getRealPath())->width();
        $image = Image::cache(function($image) use ($img, $width) {
                    if ($width > 300) {
                        $image->make($img->getRealPath())->resize(300, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    } else {
                        $image->make($img->getRealPath());
                    }
                });
        $disk = QiniuStorage::disk('qiniu');
        $extension = $img->getClientOriginalExtension();
        //$fileName = 'chatImg/' . md5_file($img->getRealPath()) . '.' . $extension;
        $fileName = 'chatImg/' . uniqid('chatimg') . '.' . $extension;
        $disk->put($fileName, $image);
        $downloadUrl = (string) $disk->downloadUrl($fileName);
        $content = "<img src=\"{$downloadUrl}\">";
        $pushData = ['type' => 'message', 'content' => $content, 'nickname' => $user->nickname, 'avatar' => $user->avatar];
        Gateway::sendToAll(json_encode($pushData, true), null, $client_id);
        return ['status' => 1, 'msg' => '发送成功', 'nickname' => $user->nickname, 'avatar' => $user->avatar, 'content' => $content];
    }

    //生成token和用户
    public function generateUser() {
        try {
            $token = uniqid('chat_');
            $user = DB::table('users_copy')
                    ->inRandomOrder()
                    ->first();
            $image = Image::cache(function($image) use ($user) {
                        $image->make($user->avatar)->resize(50, 50);
                    });
            $disk = QiniuStorage::disk('qiniu');
            $pathinfo = pathinfo($user->avatar);
            $extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : 'png';
            $fileName = 'chatAvatar/' . md5_file($user->avatar) . '.' . $extension;
            $disk->put($fileName, $image);
            $downloadUrl = (string) $disk->downloadUrl($fileName);
            $userData = [
                'nickname' => $user->nickname,
                'avatar' => $downloadUrl,
                'token' => $token
            ];
            $uid = DB::table('users')->insertGetId($userData);
            $userData['id'] = $uid;
            return $userData;
        } catch (\Exception $e) {
            $this->generateUser();
        }
    }

}

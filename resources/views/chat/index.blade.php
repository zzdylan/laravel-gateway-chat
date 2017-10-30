<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0" />
        <meta name="format-detection" content="telephone=no,email=no,date=no,address=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>聊天室</title>
        <link href="{{asset('Hui/css/Hui.css')}}" rel="stylesheet" type="text/css" />
        <link href="https://cdn.bootcss.com/layer/3.0.3/mobile/need/layer.min.css" rel="stylesheet">
        <style type="text/css">
            html,body{
                height:100%
            }
        </style>
    </head>
    <body class="H-flexbox-vertical">
        <header class="H-chatbox H-padding-vertical-bottom-10">
            <div onclick="clearStorage()" class="H-padding-vertical-top-10 H-text-align-center H-font-size-12 H-theme-font-color-999">{{$time}}</div>

        </header>
        <main id="main" class="H-flex-item H-overflow-y-scroll H-padding-vertical-bottom-10">
            <div id="msg"></div>
        </main>
        <footer class="H-flexbox-horizontal">
            <input oninput="checkValueEmpty(this)" onclick="scrollToBottom()" id="text" type="text" class="H-textbox H-vertical-align-middle H-vertical-middle H-font-size-14 H-flex-item H-box-sizing-border-box H-border-none H-border-vertical-top-after H-padding-12">
            <button id="chice_file" class="H-button H-font-size-15 H-outline-none H-padding-vertical-both-8 H-padding-horizontal-both-20 H-theme-background-color6 H-theme-font-color-white H-theme-border-color6 H-theme-border-color6-click H-theme-background-color6-click H-theme-font-color6-click">选择图片</button>
            <form method="post" action="/upload_img" id="file_form">
                <input id="client_id" name="client_id" type="text" style="display: none">
                <input id="token" name="token" type="text" style="display: none">
                <input name="img" id="file" accept="image/*" type="file" style="display: none">
            </form>
            <button onclick="send()" id="send" disabled class="H-button H-font-size-14 H-border-none H-padding-vertical-both-12">发送</button>
        </footer>
        <script src="{{asset('Hui/js/H.js')}}" type="text/javascript"></script>
        <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.2/jquery.form.min.js"></script>
        <script src="https://cdn.bootcss.com/layer/3.0.3/mobile/layer.js"></script>
<!--        <script src="https://cdn.bootcss.com/zepto/1.0rc1/zepto.min.js"></script>-->
        <script type="text/javascript">
                @php
                    if($isWeixin){
                        echo "localStorage.setItem('token', \"{$userData['token']}\");";
                    }
                @endphp
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                function clearStorage() {
                    localStorage.clear();
                    alert('清除缓存成功');
                    location.reload();
                }
                if (!window.WebSocket) {
                    alert("童鞋, 你的浏览器不支持该功能啊");
                }
                /* 处理 Android 4.4 以下版本兼容性问题 */
                function resizeWidth() {
                    H.cssText(".H-chatbox-content", "max-width:" + (document.body.clientWidth - 60 * 2) + "px");
                }
                resizeWidth();
                $('#chice_file').click(function () {
                    document.getElementById("file").click();
                });
                $('#file').change(function () {
                    $('#client_id').val(localStorage.getItem('client_id'));
                    $('#token').val(localStorage.getItem('token'));
                    $('#file_form').ajaxSubmit({
                        success: function (data) {
                            if (data.status == 1) {
                                msg.innerHTML += msgHtml(data.content, data.nickname, data.avatar, 1);
                                scrollToBottom();
                            } else {
                                msg.innerHTML += '<div style="text-align:center">' + '发送失败' + '</div>';
                                scrollToBottom();
                            }

                        },
                        error: function () {
                            msg.innerHTML += '<div style="text-align:center;color:red">' + '发送失败,请检查网络...' + '</div>';
                            scrollToBottom();
                        }
                    });
                    $('#file').val('');
                });
                //滚动到底部
                function scrollToBottom() {
                    var main = document.getElementById('main');
                    main.scrollTop = main.scrollHeight;
                }
                //检查用户是否输入，启用“发送”按钮
                function checkValueEmpty(ele) {
                    if (ele.value) {
                        $('#send').removeAttr('disabled').addClass('H-theme-background-color1');
                    } else {
                        $('#send').attr('disabled', true).removeClass('H-theme-background-color1');
                    }
                }
                window.onresize = function () {
                    resizeWidth();
                    scrollToBottom();
                }
                document.onkeydown = function () {                //网页内按下回车触发
                    if (event.keyCode == 13)
                    {
                        document.getElementById("send").click();
                        return false;
                    }
                }
                var wsServer = "ws://118.89.190.171:8282";
                ws = new WebSocket(wsServer);

                var onopen = function () {
                    //loading带文字
                    layer.open({
                        type: 2
                        , content: '加载中'
                    });
                }

                ws.onopen = onopen;

                function init(data) {
                    layer.open({
                        type: 2
                        , content: '加载中'
                    });
                    $.ajax({
                        url: '/bind',
                        type: 'post',
                        dataType: 'json',
                        data: {token: localStorage.getItem('token'), 'client_id': data.client_id},
                        success: function (data) {
                            layer.closeAll();
                            if (data.status == 1) {
                                localStorage.setItem('client_id', data.client_id);
                                layer.open({
                                    content: '初始化成功'
                                    , skin: 'msg'
                                    , time: 2 //2秒后自动关闭
                                });
                                console.log('绑定成功');
                            }
                        },
                        error: function () {
                            layer.closeAll();
                            layer.open({
                                content: '初始化失败,尝试重新加载'
                                , skin: 'msg'
                                , time: 2 //2秒后自动关闭
                            });
                            init(data);
                        }
                    });
                }

                var onmessage = function (e) {
                    // json数据转换成js对象
                    var data = eval("(" + e.data + ")");
                    console.log(data);
                    var type = data.type || '';
                    switch (type) {
                        case 'init':
                            init(data);
                            break;
                        case 'set_token':
                            localStorage.setItem('token', data.token);
                            break;
                        case 'inform':
                            msg.innerHTML += '<div style="text-align:center">' + data.content + '</div>';
                            scrollToBottom();
                            break;
                        case 'message':
                            msg.innerHTML += msgHtml(data.content, data.nickname, data.avatar, 0);
                            scrollToBottom();
                            break;
                        default :
                            console.log(e.data);
                    }
                }
                // 服务端主动推送消息时会触发这里的onmessage
                ws.onmessage = onmessage;

                var disConnect = function () {
                    msg.innerHTML += '<div style="text-align:center">重新连接中...</div>';
                    console.log('重新连接中...');
                    setTimeout(function () {
                        reconnect();
                    }, 5000);
                }
                //监听连接关闭
                ws.onclose = disConnect;




                //断线重连
                function reconnect() {
                    msg.innerHTML += '<div style="text-align:center" onclick="clearStorage()">连接已经断开</div>';
                    console.log('连接已经断开');
                    ws = new WebSocket(wsServer);
                    ws.onmessage = onmessage;
                    ws.onclose = disConnect;
                }

                //发送
                function send() {
                    var text = $('#text').val();
                    $('#text').val('').focus();
                    $('#send').attr('disabled', true).removeClass('H-theme-background-color1');
                    //向服务器发送数据
                    $.ajax({
                        url: '/send_message',
                        data: {token: localStorage.getItem('token'), client_id: localStorage.getItem('client_id'), content: text},
                        dataType: 'json',
                        type: 'post',
                        success: function (data) {
                            if (data.status == 1) {
                                msg.innerHTML += msgHtml(data.content, data.nickname, data.avatar, 1);
                                scrollToBottom();
                            } else {
                                msg.innerHTML += '<div style="text-align:center">' + '发送失败' + '</div>';
                                scrollToBottom();
                            }

                        },
                        error: function () {
                            msg.innerHTML += '<div style="text-align:center;color:red">' + '发送失败,请检查网络...' + '</div>';
                            scrollToBottom();
                        }
                    });
                }

                function msgHtml(msg, nickname, avatar, is_own) {
                    if (is_own) {
                        return ['<div class="H-chatbox-sender H-flexbox-horizontal H-padding-horizontal-both-10 H-box-sizing-border-box H-margin-vertical-top-10">',
                            '            <div class="H-chatbox-main H-flex-item H-flexbox-horizontal H-position-relative H-margin-horizontal-right-12">',
                            '                <div class="H-chatbox-status H-flex-item H-padding-horizontal-both-10 H-box-sizing-border-box H-text-align-right H-padding-vertical-top-12"></div>',
                            '                <div class="H-chatbox-content">',
                            '                    <div class="H-font-size-12 H-theme-font-color-444 H-padding-2 H-text-align-right">',
                            nickname,
                            '                   </div>',
                            '                    <div class="H-position-relative">',
                            '                        <div class="H-chatbox-content-text H-font-size-16 H-padding-10 H-theme-background-color1 H-theme-font-color-white H-border-radius-12">',
                            msg,
                            '                         </div>',
                            '                        <div class="H-chatbox-bugle H-theme-border-color1 H-position-absolute H-z-index-100 H-bugle-right"></div>',
                            '                    </div>',
                            '                </div>',
                            '            </div>',
                            '            <div class="H-chatbox-img H-position-relative"><img src="',
                            avatar,
                            '" class="H-display-block H-border-radius-circle" alt="" title="" /></div>',
                            '        </div>'].join("");
                    } else {
                        return ['<div class="H-chatbox-receiver H-flexbox-horizontal H-padding-horizontal-both-10 H-box-sizing-border-box H-margin-vertical-top-10">',
                            '            <div class="H-chatbox-img H-position-relative"><img src="',
                            avatar,
                            '" class="H-display-block H-border-radius-circle" alt="" title="" /></div>',
                            '            <div class="H-chatbox-main H-flex-item H-flexbox-horizontal H-position-relative H-margin-horizontal-left-12">',
                            '                <div class="H-chatbox-content">',
                            '                    <div class="H-font-size-12 H-theme-font-color-444 H-padding-2">',
                            nickname,
                            '</div>',
                            '                    <div class="H-position-relative">',
                            '                        <div class="H-chatbox-content-text H-font-size-16 H-padding-10 H-theme-background-color-black H-theme-font-color-white H-border-radius-12">',
                            msg,
                            '</div>',
                            '                        <div class="H-chatbox-bugle H-theme-border-color-black H-position-absolute H-z-index-100 H-bugle-left"></div>',
                            '                    </div>',
                            '                </div>',
                            '                <div class="H-chatbox-status H-flex-item H-padding-horizontal-both-10 H-box-sizing-border-box H-text-align-left H-padding-vertical-top-12"></div>',
                            '            </div>',
                            '        </div>'].join("");
                    }
                }

        </script>
    </body>
</html>

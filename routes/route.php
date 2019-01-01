<?php

/**
 * Forbidden Router
 **/

$router->get('/', 'App\Http\Controllers\AppController@forbidden');
$router->post('/', 'App\Http\Controllers\AppController@forbidden');

$router->get('test', 'App\Http\Controllers\TestController@test');

$router->group(['namespace' => '\Rap2hpoutre\LaravelLogViewer'], function () use ($router) {
    $router->get('logs', 'LogViewerController@index');
});


/**
 * Version 1.0
 **/

$router->group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
    //获取最新app版本号
    $router->post('app/updateInfo', 'AppController@getVersion');
    $router->post('apple/product', 'AppController@getAppleProduct');
    $router->post('clientLog', 'LogController@clientLog');
    $router->post('getVerify', ['uses' => 'AuthController@getVerify', 'middleware' => 'mobile']);// 取验证码接口
    $router->post('checkVerify', ['uses' => 'AppController@checkVerify', 'middleware' => ['mobile', 'verify']]);// 检查验证码接口
    $router->group(['prefix' => 'login'], function () use ($router) {
        $router->post('mobile', ['uses' => 'AuthController@mobile', 'middleware' => ['mobile', 'verify']]);// 手机登录接口
        $router->post('wx', 'AuthController@wx');// 微信登录接口
        $router->post('qq', 'AuthController@qq');// QQ登录接口
    });
    $router->group(['middleware' => 'auth:api'], function () use ($router) {
        $router->group(['prefix' => 'user', 'middleware' => 'userId'], function () use ($router) {
            $router->post('getCashVerify', 'AuthController@getCashVerify');// 取验证码接口
            $router->post('takeCash', ['uses' => 'UserController@takeCash', 'middleware' => 'cashVerify']);// 用户提现
            $router->group(['middleware' => 'data'], function () use ($router) {
                $router->post('bindXgHx', 'UserChatController@bindXgHx');// 绑定信鸽环信接口（停用）
                $router->post('identity', 'UserAuthController@identity');// 实名认证接口
                $router->post('authorize', 'UserAuthController@_authorize');// 导师认证接口
                $router->post('setBasic', 'UserUpdateController@setBasic');// 更新基本信息接口
            });
            $router->post('cancelAuthorize', 'UserAuthController@cancelAuthorize');// 取消导师认证接口
            $router->group(['middleware' => 'paginate'], function () use ($router) {
                $router->post('fans', 'UserSocialController@fans');// 粉丝列表接口
                $router->post('follows', 'UserSocialController@follows');// 关注列表接口
                $router->post('friends', 'UserSocialController@friends');// 好友列表接口
                $router->post('checkApply', 'UserSocialController@checkApply');// 查看好友申请接口（停用）
                $router->post('getBlacklist', 'UserSocialController@getBlacklist');// 查看黑名单列表接口
                $router->post('showTicket', 'TicketController@showTicket');// 优惠券列表接口
                $router->post('showContract', 'WalletController@showContract');// 查看交易记录接口
            });
            $router->group(['middleware' => 'otherId'], function () use ($router) {
                $router->post('addApply', 'UserSocialController@addApply');// 添加好友接口（停用）
                $router->post('optFollow', 'UserSocialController@optFollow');// 添加关注接口
                $router->post('passApply', 'UserSocialController@passApply');// 通过好友申请接口（停用）
                $router->post('setBlacklist', 'UserSocialController@setBlacklist');// 更新黑名单接口
                $router->post('reward', 'WalletController@reward');// 虚拟币打赏接口
                $router->post('chatInfo', 'UserChatController@chatInfo');// 聊天对象信息接口
            });
            $router->post('showWallet', 'WalletController@showWallet');// 查看钱包接口
            $router->post('recharge', 'WalletController@recharge');// 充值钱包接口
            $router->post('bond', 'WalletController@bond');// 交保证金
            $router->post('exchange', 'WalletController@exchange');// 用户兑换虚拟币接口
            $router->post('reExchange', 'WalletController@reExchange');// 虚拟币提现余额接口
            $router->post('setMobile', ['uses' => 'UserUpdateController@setMobile', 'middleware' => ['mobile', 'verify']]);// 更新手机号接口
            $router->post('setNickname', 'UserUpdateController@setNickname');// 更新昵称接口
            $router->post('showBasic', 'UserController@showBasic');// 基本信息接口
            $router->post('showProfile', 'UserController@profile');// 用户个人主页接口
            $router->post('postFile', 'UserController@postFile');// 用户上传接口
            $router->post('getFile', 'UserController@getFile');// 用户查看文件
            $router->post('delFile', 'UserController@delFile');// 用户删除文件

            $router->post('bask', 'Bask\BaskController@index');// 打赏

            $router->post('newTicket', 'TicketController@newTicket');// 兑换优惠券接口
            $router->post('gameTicket', 'TicketController@getGameTicket');// 使用优惠券接口
            $router->post('useTicket', 'TicketController@useTicket');// 使用优惠券接口

            $router->post('checkIn', 'UserController@checkIn');// 每日签到接口
            $router->post('getXgHx', 'UserChatController@getXgHx');// 查看信鸽环信接口
            $router->post('logout', 'AuthController@logout');// 用户登出接口
            $router->post('refreshToken', 'AuthController@refreshToken');// 刷新令牌接口
            $router->post('complaint', 'AppController@complaint');// 投诉导师接口
            $router->post('feedback', 'AppController@feedback');// 反馈建议接口
            $router->post('report', 'AppController@report');// 用户举报接口

            $router->post('notice', 'UserController@notice');// 用户通知接口（停用）
            $router->post('checkAuth', 'UserAuthController@checkAuth');// 查看导师游戏认证状态接口
            $router->post('checkIdentity', 'UserAuthController@checkIdentity');// 查看实名认证状态接口
            $router->post('userAuthInfo', 'UserAuthController@userAuthInfo');// 查看实名认证状态接口
            $router->post('authProgressInfo', 'UserAuthController@authProgressInfo');// 查看认证进度接口
            $router->post('directIdentity', 'UserAuthController@directIdentity');// 苹果直接认证

            $router->post('showOrder', 'OrderController@showOrder');// 订单列表接口
            $router->post('yuewanOrder', 'OrderController@yuewanOrder');// 订单列表接口
            $router->post('orderInfo', 'OrderController@orderInfo');// 订单详情接口
            $router->post('comment', 'OrderController@comment');// 订单评分接口
            $router->post('pay', 'OrderController@pay');// 订单支付接口
            $router->post('confirm', 'OrderController@confirm');// 订单确认接口
            $router->post('personalOrder', 'OrderController@personalOrder');// 专属订单选择
            $router->post('getCommentList', 'OrderController@getMasterCommentList');//获取导师订单评论列表
            $router->post('orderServicePrice', 'OrderController@orderServicePrice');// 查询订单服务费

            $router->post('masterHome', 'UserController@masterHome');// 导师个人主页接口
            $router->post('normalSkill', 'UserController@normalSkill');// 导师标准技能接口

            $router->post('pushTickets', 'TicketController@pushTickets');// 发放优惠券接口（停用）

            $router->post('gameOrder', 'OrderController@createAccompanyOrder');// 生成陪玩游戏订单接口

            $router->post('createPersonalOrder', 'PersonalOrderController@createPersonalOrder');// 生成专属订单接口
            $router->post('queryPersonalOrder', 'PersonalOrderController@queryPersonalOrder');// 查询导师订单信息

            $router->post('gameManLevelInfo', 'GameController@getManGameLevelInfo');// 暴鸡游戏段位付费信息接口
            $router->post('gameWomanInfo', 'GameController@getWomanGameLevelInfo');// 暴娘游戏段位付费信息接口

            $router->post('queryGrabbedMaster', 'GrabbedOrder@queryGrabbedMaster');//查询抢单导师
            $router->post('selectGrabbedMaster', 'GrabbedOrder@selectGrabbedMaster');//确认抢单导师

            $router->post('cancelOrder', 'OrderController@cancelOrderByUser');//玩家取消订单
            $router->post('queryOrderStatus', 'OrderController@queryOrderStatus');//玩家查询订单

            $router->post('queryGameStatus', 'GameController@queryGameStatus');//查询订单游戏状态
            $router->post('userGameReady', 'GameController@userGameReady');//玩家已准备

            $router->post('queryRechargeStatus', 'WalletController@queryRechargeStatus');//查询充值订单状态
            $router->post('queryBondStatus', 'WalletController@queryBondStatus');//查询充值订单状态
            //约玩
            $router->post('selectDateGame', 'PlayController@selectGame');//游戏选择
            $router->post('queryPlayGameMoney', 'PlayController@queryPlayGameMoney');//游戏价格查询
            $router->post('queryOrderInfo', 'PlayController@queryOrderInfo');//查询约玩订单状态
            $router->post('createAppointmentOrder', 'PlayController@createAppointmentOrder');// 生成约玩游戏订单接口
            $router->post('cancelAppointmentOrder', 'PlayController@cancelAppointmentOrder');//查询约玩订单状态
            $router->post('queryMatchResult', 'PlayController@queryMatchResult');//查询匹配结果
            $router->post('selectMatchUser', 'PlayController@selectMatchUser');//选择匹配人
        });

        $router->group(['prefix' => 'master', 'middleware' => 'userId'], function () use ($router) {
            $router->post('masterInfo', 'MasterController@masterInfo');// 导师简介接口 小哥哥 小姐姐
            $router->post('masterGameReady', 'GameController@masterGameReady');//导师已准备
            $router->post('endGame', 'GameController@endGame');//游戏结束
            $router->post('checkMasterGameStauts', 'MasterController@checkMasterGameStauts');//获取导师接单状态
            $router->post('setMasterStatus', 'MasterController@setMasterStatus');//导师设置接单状态
            $router->post('getMasterRange', 'MasterController@getMasterRange');//导师设置接单设置页
            $router->post('setMasterGameRange', 'MasterController@setMasterGameRange');//导师设置接单范围
            $router->post('queryGameList', 'GameController@queryGameList');//可接单游戏列表
            $router->post('acceptOrder', 'GrabbedOrder@grab');//导师接单抢单
            $router->post('queryGrabbedMasterSelectResult', 'GrabbedOrder@queryGrabbedMasterSelectResult');//导师接单抢单结果查询
            $router->post('cancelOrder', 'OrderController@cancelOrderByMaster');//导师取消订单

            $router->post('acceptPersonalOrder', 'PersonalOrderController@acceptPersonalOrder');// 接受专属订单接口
        });

        $router->post('checkGoingOrder', 'OrderController@checkGoingOrder');// 检查正在进行订单
        $router->post('game', 'GameController@gameList');// 游戏列表接口
        $router->post('public', 'AppController@public');// 公共信息接口
        $router->post('publish', 'AppController@publish');// 公告接口
        $router->post('share', 'ShareController@index');// 公告接口


    });

    $router->group(['prefix' => 'payment'], function () use ($router) {
        $router->post('wechatOrderNotify', 'PaymentController@wechatOrder');// 微信支付订单回调
        $router->post('alipayOrderNotify', 'PaymentController@alipayOrder');// 支付宝支付订单回调

        $router->post('wechatRechargeNotify', 'PaymentController@wechatRechargeOrder');// 微信充值订单回调
        $router->post('alipayRechargeNotify', 'PaymentController@alipayRechargeOrder');// 支付宝充值订单回调

        $router->post('wechatBondNotify', 'PaymentController@wechatBondOrder');// 微信保证金订单回调
        $router->post('alipayBondNotify', 'PaymentController@alipayBondOrder');// 支付宝保证金订单回调

        $router->post('wechatBaskNotify', 'Resound\PaymentController@baskWechatOrder');// 微信打赏订单回调
        $router->post('alipayBaskNotify', 'Resound\PaymentController@baskAlipayOrder');// 支付宝打赏订单回调
        $router->post('applyPayNotify', 'Resound\PaymentController@applyPay');// 支付宝打赏订单回调
    });

    $router->group(['prefix' => 'xg'], function () use ($router) {
        //$router->get('test', 'TestController@cos');
    });

    $router->group(['prefix' => 'easemob'], function () use ($router) {
        $router->get('send', 'TestController@send');
    });

    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->post('easemob', 'UserController@easemob');
        $router->get('avatar/{hx_id}', 'UserController@avatar');
    });


    //小程序相关
    $router->group(['prefix' => 'applet', 'namespace' => 'Applet'], function () use ($router) {
        $router->post('login/code', 'AuthController@codeLogin'); //通过js_code获取openid登陆
        $router->post('login/decrypt', 'AuthController@decryptLogin'); //通过解码获取手机号登陆
        $router->post('register', 'AuthController@register'); //注册流程
    });
});

$router->group(['prefix' => 'v2'], function () use ($router) {
    $router->group(['namespace' => 'App\Http\AppointmentControllers'], function () use ($router) {
        $router->post('user/setMapPosition', 'UserController@setMapPosition');
        $router->post('user/tagList', 'UserController@tagList');
        $router->post('user/setTags', 'UserController@setTags');
        $router->post('user/getAppointmentStatus', 'UserController@getAppointmentStatus');

        $router->post('appointment/gameList', 'GameController@GameList');
        $router->post('order/createOrder', 'OrderController@createOrder');
        $router->post('order/acceptOrder', 'OrderController@acceptOrder');
        $router->post('order/cancelOrder', 'OrderController@cancelOrder');
        $router->post('order/grabOrder', 'OrderController@grabOrder');
        $router->post('order/orderList', 'OrderController@orderList');
        $router->post('order/cancelGrab', 'OrderController@cancelGrab');
        $router->post('order/endGame', 'OrderController@endGame');

        $router->post('map/getUserList', 'MapController@getUserList');
        $router->post('map/getUserInfo', 'MapController@getUserInfo');
        $router->post('map/getHotCity', 'MapController@getHotCity');

        $router->post('album/upload', 'Album@upload');
        $router->post('album/getList', 'Album@getList');
    });

    $router->group(['middleware' => 'auth:api'], function () use ($router) {
        // 动态相关
        $router->group(['prefix' => 'dynamic', 'namespace' => 'App\Http\Controllers'], function () use ($router) {
            $router->post('list', 'DynamicController@list');                            //动态列表
            $router->post('detail', 'DynamicController@detail');                //动态详情
            $router->post('publish', 'DynamicController@publish');                        //发布动态
            /* $router->post('comments/publish', 'DynamicController@commentsPublish');    //发布评论
             $router->post('comments/list', 'DynamicController@commentsList'); //获取动态评论列表*/

            $router->post('reports', 'DynamicController@reports');        //投诉
            $router->post('praise', 'DynamicController@praise');        //点赞
            $router->post('del', 'DynamicController@del');                //删除动态
            $router->post('remind', 'DynamicController@remind');          //获取动态互动提醒
            $router->post('removeRemind', 'DynamicController@removeRemind');          //删除动态互动提醒

            $router->group(['prefix' => 'comments'], function () use ($router) {
                $router->post('publish', 'DynamicController@commentsPublish');    //发布评论
                $router->post('list', 'DynamicController@commentsList'); //获取动态评论列表
                $router->post('del', 'DynamicController@commentsDel'); //删除评论
            });
        });
    });
});


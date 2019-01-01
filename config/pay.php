<?php

return [
    'alipay' => [
        // 支付宝分配的 APPID
        'app_id' => '2018072460793298',

        // 支付宝异步通知地址
        'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/alipayRechargeNotify',
        'notify_url_online' => 'http://api.wanzhuanhuyu.cn/v1/payment/alipayRechargeNotify',

        // 订单通知
        'order_notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/alipayOrderNotify',
        'order_notify_url_online' => 'http://api.wanzhuanhuyu.cn/v1/payment/alipayOrderNotify',

        // 支付成功后同步通知地址
        'return_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/alipayRechargeReturn',
        'return_url_online' => 'http://api.wanzhuanhuyu.cn/v1/payment/alipayRechargeReturn',

        //打赏支付回调
        'bask_notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/alipayBaskNotify',
        'bask_notify_url_online' => 'http://api.wanzhuanhuyu.cn/v1/payment/alipayBaskNotify',

        // 阿里公共密钥，验证签名时使用
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArLVC1o2B+wsvvuqUjEOOw990+B9sxBImsFIJmiHddRQ0zIMRaf84btmaYKuwRGVD41J5/4GTDm0EcMN+2eWgCWheO6Wx5Va7YNKQU3SFhwv09J4mq83ELEf4OkODL41ztcjnDxeeEbEYpmY6KMVYVzM7XYAeYwdleXhgvXzGEk86hAtMeKz22nR58mZ8KdEfZxPHr6V3PzmhDTvzSA4WQIv9rCCgIACIUjlhfugoatjaTz6VLqEonhk5ahLkOJOiH1ExzE6ohrzXpxXAhyGvvhMNwEWXdHFQQqIMJ8ikZ376wigE0Bo4NhhmiTuHj1xUkJOyP6uSQ0HL4jhlFAavwwIDAQAB',

        // 自己的私钥，签名时使用
        'private_key' => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCGI3ENDd2dBQnAmMPvCXUc1iqlJ+MBnqE1kZTeSsh20rrFSir/9dHNfYGXw9v/5jJhKqzYKyy0+AEf7UXFgEkiMc/oTG6bDvc9XC+Gf/zcKj8y6lmUinUei/FhFWL897p6/Hm7Kqoe1pvcKkAosQigauvo1xtFOKX/0QZ1GYZ2puGPHqPNunCwfIgLhKJzdMF7JpE0k95gjSdBJtvwKA+XyZ1nVyhn7CN/OIQanalHGVzJdKgFKdxRy12Nd5KDA3TaJqHPuydDZ1+dDi9g0qWtVagMpmo3MRdxoUyNuNyFBAACzEOC8TKhTiCAEtLTVrz183UawP8+xhcywCXvkOa3AgMBAAECggEAeUGSkXVmTund/f9apzvKZS3o0PE0kSM/oXgfta6udlNaSLwaENW7OWHqAzInLrV99z4njyXAPTsvcRgBCV9FPScARYLKPVsS/qHVyGTsRG3gQXt9TPy4kMt6gQNfP0QFi+WteRdBP61KqYR8CQy3uqPM6+d/nFxwGsduae6QEbLL0/71ecbio/MoYrfBXbo9NO7jBvr74QykI1+CRBvOoSxd8Ic6eMzdeZdA/Lqng2kTM0mmp9qqiueDL7ClgXYmR6nqv0GO8Q4dxLBWneYffJ6yZT8oAxr8jA70qVjot0yeS3fhuMwpu3BySPCOXglPGTtkg6iTRsnAgR2LCF4KyQKBgQDF621mttWcs6rmMfhd6OWSG5IVFO30H1Fw5CQqCiTc8saFDfd2lWStKwDaOuKhOtg5RvCKWYalZZWgCM95p+WzFvipr00ynl0Iz4lYLSveDfzyv2t/TfSXyNx1oVULIPkQSzwBOawXxM4AMXjBNw8Cn2DrhzapRVPnPKxBkd3AkwKBgQCtgH9In7Jb8QFwvFlcB3TM2GMSgF+cJ1ACFnSq8Wpw05cVD6dV5uklUDwGhS6Dmm6PdK9CRMq9HD51xujmrGxJcs3+cqSgXa80vnsQlgmQklfg48TsFdbweOWKBs6jKooM1k7YEHAuN7czblHs3oMsgYFuUW0EUdgrnnhDj+crzQKBgQCC2u8QFB4TZu69F5jAbjUqbrQc4CObXgF4hD4s1VweAR2j5uIQMyHHJEGCB6DDPKas5+wtbJeLTyioEGssYgeKasupVM/TgcS9CIokFGmGfPUagdjO6HmoyRKJa0tQ+lHxpexcWwcfB+2zTwIprP9tmnZ4AoPvUnjwz7qL96cHpQKBgAIx1upXQs1Q7iC78TFt0rdooVAxmYQDJ0rvd8hVx2/n7UhR6171zcswCaJXevAlOvLLmg/viNT9j3iz7GxGkBQZxKtMTfLNXJMBOdNK4pIWl8/7cZk+6XofPWASl/iOaDKjX1nyU0jyYXngEl85rVUZykZX91YPpvleRs0r+9OdAoGAGGKAJJQ1njdfpwLEsHKjegzvdRbfdjKywo04ZCnLIDiBclLZ3RnPrR2PY5dKA/3l/m+7yPVgtAb6PUuC/ersJYVOeXz4gl//hLO7IprgA2uCqAKlJd1OHtVv3JAu7bzE+aVe01XTE90R/PLO9dp+Ee6PyZ8D8f717l/im1mZpOo=',

        // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
        'log' => [
            'file' => storage_path('logs/alipay.log')
        ],

        // optional，设置此参数，将进入沙箱模式
        // 'mode' => 'dev'
    ],

    'wechat' => [
        // 公众号 APPID
        'app_id' => '',

        // 小程序 APPID
        'miniapp_id' => '',

        // APP 引用的 appid
        'appid' => 'wx7ecb502c618247e1',

        // 微信支付分配的微信商户号
        'mch_id' => '1510181301',

        // 微信支付异步通知地址
        'notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/wechatRechargeNotify',
        'notify_url_online' => 'http://api.wanzhuanhuyu.cn/v1/payment/wechatRechargeNotify',

        // 订单通知
        'order_notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/wechatOrderNotify',
        'order_notify_url_online' => 'http://api.wanzhuanhuyu.cn/v1/payment/wechatOrderNotify',

        //打赏支付回调
        'bask_notify_url' => 'http://api-dev.wanzhuanhuyu.cn/v1/payment/wechatBaskNotify',
        'bask_notify_url_online' => 'http://api.wanzhuanhuyu.cn/v1/payment/wechatBaskNotify',

        // 微信支付签名秘钥
        'key' => '213fjsf343JJfd93elp2o89eE23aaVf3',

        // 客户端证书路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题.pem 格式。
        'cert_client' => '',

        // 客户端秘钥路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题.pem 格式。
        'cert_key' => '',

        // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
        'log' => [
            'file' => storage_path('logs/wechat.log')
        ],

        // optional
        // 'dev' 时为沙箱模式
        // 'hk' 时为东南亚节点
        // 'mode' => 'dev'
    ],
];

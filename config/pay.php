<?php
use Yansongda\Pay\Pay;
// dd(storage_path('cert/appPublicCert.crt'));
return [
    // 'alipay' => [
    //     'app_id'         => '9021000136666772',
    //     'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtu3rr4C1ZxgPPL17Kfe7fUUV428CpR9Qk0XSo6+0Mxm/KQVA0XvNnY39n36x67qAO6Pdw2bkuW9QTmn6EoxUGmE3LYdIN4PhVtoJh5FcagA5ZHysYePyxDlWSUPhmzRs9Uu7JzlE6nEeJ8niMbRwK1VrZhBz0c0cp6y3+P2SSh9D2YHt3aEs0fU+gmeus7cVIZhgfA4tG5PWMyiWLFAf4JyYU24U0KeWQByvLrV7vTRxSW8UgrfAixj0Jn9pNQQGBB6KBWBRoP0K5jy354Yfs7sT6yxd6ffgGpcru2t6BNSiRu1SmlKk62vZlGqr1kcsFoEuvdgPc240hSS/AuzrvwIDAQAB',
    //     'private_key'    => 'MIIEowIBAAKCAQEAtu3rr4C1ZxgPPL17Kfe7fUUV428CpR9Qk0XSo6+0Mxm/KQVA0XvNnY39n36x67qAO6Pdw2bkuW9QTmn6EoxUGmE3LYdIN4PhVtoJh5FcagA5ZHysYePyxDlWSUPhmzRs9Uu7JzlE6nEeJ8niMbRwK1VrZhBz0c0cp6y3+P2SSh9D2YHt3aEs0fU+gmeus7cVIZhgfA4tG5PWMyiWLFAf4JyYU24U0KeWQByvLrV7vTRxSW8UgrfAixj0Jn9pNQQGBB6KBWBRoP0K5jy354Yfs7sT6yxd6ffgGpcru2t6BNSiRu1SmlKk62vZlGqr1kcsFoEuvdgPc240hSS/AuzrvwIDAQABAoIBAA0c/Ro1pctgJCd8hcm6YoxWWX1WReBEYGhORw1I2SgYnmV4ZO8fatvyg83dLS/yzKJ52rnZNGg+nIkie7S8roK9mVEAM6MXJx4svyYiu3c10OdtMIZL6uk6Gv9hiFix52WmKiJjrpcHyNudU0Ow5aGVm/9TCpptk7JJZV7J/qSlff22gV85KYRMEeWfDBlklUAGJjmfSxjyKvxu5CeTZ0jTC/wv1exDnAN45ZrPJW1JrCtaQWvQ0udfZjxVVR1HfMO75j7HAH7PZsl4S3ypGRCfj7+RX30ikh2GMZ9Hymys9KRgoz4BsIv85FoWnGBOXyKf8pJQ8yFPcf5yxzjZK+ECgYEA6mEYQDX7G7e8TCgBs+j9o3uIbo7ideQamXqUHAcLeQhBpoDDEv+MWoCBmGGeL8o5U7MF42ga1i9duN10CO7ARzBXUE/ql8yr2+O2ybpesemuOSu0OtqZTLt7u9vdHtvApqg298+QUUpb7ZuINrJpmeG4ixX2ArYZAea7zhEEsccCgYEAx83T/HbLoB3zS9g4xGK3xi+hiDnPzd883VQa0hGs5rJNYv0SbPD0a6RPgWbu9dRMApztPIsR0ITNIEWwg8T6gbsAxPOe9f961kAl+T2UGXOzr79MmGgT9qZBhLezUTe3Kryc35tNCANJOmLfRZLgwxXGEUch8Za4vJizZOw39kkCgYEAyrUpDTfiswlI4LDlMB29aGxKBhyr/fxHiSA8ArWRFZ+vb/sDPGYqlId6Djm3X2vOAvbdi92ZAE+9Bkr5tVQQlkFX8sz7f0h0Bty+VWBL0CHkHewPl6tCVNv92u8AIlU7HKh1ygQJtFTUhv8yYuQRcxaGzjwzJgsqO7u8CDAt2DcCgYAUtofFYmNVpAU21aF86mb2yrVKNAQQ6ZWbtD5bjK2J9O4E1wtz877C8fO0DIG1Rl7i3nXRkvEz1rCVKeRUpLT7gfFgeYj/I48OavQE1Jdn1BDKpWBBZHi9VxrvsUOLpeT9LzRsEeN4cy7COMHAqqtWTgmMfP1N2DwqgQIH8l0S0QKBgHaRXULPdDCQNcNLpgW+teGJcEP7jv0FRQA+9+iMxDOB+ZALN1X15TI+u3VYOMM6veTVi3WpSk7s3UVNJPOSjOIfXRGrhCL0t/jT6jhWEXvbRKZKo57+D8JyiuAu0iMJDSwmfRw2BVH9AcL49q66a1CP47lqme7FYmPta+VEXw6F',
    //     'log'            => [
    //         'file' => storage_path('logs/alipay.log'),
    //     ],
    // ],

    'alipay' => [
        'default' => [
            // 必填-支付宝分配的 app_id
            'app_id' => env('ALIPAY_APP_ID'),
            // 必填-应用私钥 字符串或路径
            'app_secret_cert' => env('APP_SECRET_CERT'),
            // 必填-应用公钥证书 路径
            'app_public_cert_path' => storage_path('cert/appPublicCert.crt'),
            // 必填-支付宝公钥证书 路径
            'alipay_public_cert_path' => storage_path('cert/alipayPublicCert.crt'),
            // 必填-支付宝根证书 路径
            'alipay_root_cert_path' => storage_path('cert/alipayRootCert.crt'),
            // 'return_url' => route('payment.alipay.return'),
            // 'notify_url' => route('payment.alipay.notify'),
            'return_url'=>env('APP_URL').'/payment/alipay/return',
            'notify_url'=>env('APP_URL').'/payment/alipay/notify',

            // 选填-第三方应用授权token
            'app_auth_token' => '',
            // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
            'service_provider_id' => '',
            // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
            'mode' => Pay::MODE_NORMAL,
        ],       
    ], 
    'logger' => [ // optional
        'enable' => false,
        'file' => './logs/alipay.log',
        'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
        'type' => 'single', // optional, 可选 daily.
        'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
    ],
    'http' => [ // optional
        'timeout' => 5.0,
        'connect_timeout' => 5.0,
        // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
    ],  

    // 'wechat' => [
    //     'app_id'      => '',
    //     'mch_id'      => '',
    //     'key'         => '',
    //     'cert_client' => '',
    //     'cert_key'    => '',
    //     'log'         => [
    //         'file' => storage_path('logs/wechat_pay.log'),
    //     ],
    // ],

    'wechat' => [
        'default' => [
            // 必填-商户号，服务商模式下为服务商商户号
            // 可在 https://pay.weixin.qq.com/ 账户中心->商户信息 查看
            'mch_id' => '',
            // 选填-v2商户私钥
            'mch_secret_key_v2' => '',
            // 必填-v3 商户秘钥
            // 即 API v3 密钥(32字节，形如md5值)，可在 账户中心->API安全 中设置
            'mch_secret_key' => '',
            // 必填-商户私钥 字符串或路径
            // 即 API证书 PRIVATE KEY，可在 账户中心->API安全->申请API证书 里获得
            // 文件名形如：apiclient_key.pem
            'mch_secret_cert' => '',
            // 必填-商户公钥证书路径
            // 即 API证书 CERTIFICATE，可在 账户中心->API安全->申请API证书 里获得
            // 文件名形如：apiclient_cert.pem
            'mch_public_cert_path' => '',
            // 必填-微信回调url
            // 不能有参数，如?号，空格等，否则会无法正确回调
            'notify_url' => 'https://yansongda.cn/wechat/notify',
            // 选填-公众号 的 app_id
            // 可在 mp.weixin.qq.com 设置与开发->基本配置->开发者ID(AppID) 查看
            'mp_app_id' => '2016082000291234',
            // 选填-小程序 的 app_id
            'mini_app_id' => '',
            // 选填-app 的 app_id
            'app_id' => '',
            // 选填-服务商模式下，子公众号 的 app_id
            'sub_mp_app_id' => '',
            // 选填-服务商模式下，子 app 的 app_id
            'sub_app_id' => '',
            // 选填-服务商模式下，子小程序 的 app_id
            'sub_mini_app_id' => '',
            // 选填-服务商模式下，子商户id
            'sub_mch_id' => '',
            // 选填-微信平台公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
            'wechat_public_cert_path' => [
                '45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__.'/Cert/wechatPublicKey.crt',
            ],
            // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
            'mode' => Pay::MODE_NORMAL,
        ]
    ],
];
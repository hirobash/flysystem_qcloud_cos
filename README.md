# Flysystem Qcloud COS

This is for drupal.

Provides a Qcloud COS plugin for [Flysystem](https://www.drupal.org/project/flysystem)

## Dependencies

`composer require qcloud/cos-sdk-v5:2.6 -vvv`

If you need sts, please install

`composer require qcloud_sts/qcloud-sts-sdk:v3.0`

## CONFIGURATION

Example configuration:

```php

$schemes = [
  'cos' => [
    'driver' => 'qcloud_cos',
    'name' => 'Qcloud COS',
    'description' => 'A Qcloud COS plugin for Flysystem',
    'cache' => FALSE,
    'config' => [
      'secret_id' => 'secret_id',
      'secret_key' => 'secret_key',
      'token'   => '',
      'endpoint' => 'cos.ap-shanghai.myqcloud.com',
      'region' => 'ap-shanghai', //园区
      'schema' => 'https', //协议头部，默认为http
      'domain' => '', //domain可以填写用户自定义域名，或者桶的全球加速域名
      'proxy' => '', //代理服务器
      'retry' => 5, //重试次数
      'userAgent' => '', //UA
      'allow_redirects' => false, //是否follow302
      
      'bucket' => 'bucket-1xxxxxx',
      'appid' => '1xxxxxx',
      'visibility' => 'private',//public-read
      'use_https' => TRUE,
      'expire' => 3600,

      'ip' => '', //ip
      'port' => '', //端口
      'timeout' => 3600,
      'connect_timeout' => 60,
    ],
  ],
];

$settings['flysystem'] = $schemes;

```

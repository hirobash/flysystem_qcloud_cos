<?php

namespace Drupal\flysystem_qcloud_cos\Flysystem;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flysystem\Plugin\FlysystemPluginInterface;
use Drupal\flysystem\Plugin\FlysystemUrlTrait;
use Drupal\flysystem\Plugin\ImageStyleGenerationTrait;
use Drupal\flysystem_qcloud_cos\Flysystem\Adapter\QcloudCosAdapter;
use League\Flysystem\Config;

use QCloud\COSSTS\Sts;
use Qcloud\Cos\Client;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal plugin for the "Qcloud COS" Flysystem adapter.
 *
 * @Adapter(id = "qcloud_cos")
 */
class QcloudCos implements FlysystemPluginInterface, ContainerFactoryPluginInterface {

  use ImageStyleGenerationTrait;
  use FlysystemUrlTrait {
    getExternalUrl as getDownloadUrl;
  }

  /**
   * The client.
   *
   * @var \Qcloud\Cos\Client
   */
  private $client;

  /**
   * Plugin config.
   *
   * @var \League\Flysystem\Config
   */
  protected $config;

  /**
   * The bucket name.
   *
   * @var string
   */
  private $bucket;
  private $appid;

  /**
   * The prefix.
   *
   * @var string
   */
  private $prefix;

  /**
   * The endpoint.
   *
   * @var string
   */
  private $endpoint;

  /**
   * The url expire time.
   *
   * @var string
   */
  private $expire;

  /**
   * QcloudCos constructor.
   *
   * @param Qcloud\Cos\Client $client
   *   The Cos Client.
   * @param \League\Flysystem\Config $config
   *   The configuration.
   */
  public function __construct(Client $client, Config $config) {

    $this->client = $client;
    $this->config = $config;

    $this->bucket = $config->get('bucket', '');
    $this->appid = $config->get('appid', '');
    $this->endpoint = $config->get('endpoint', '');
    $this->prefix = $config->get('prefix', '');
    $this->expire = $config->get('expire', 3600);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // TODO: timeout: 3600, connectTimeout: 10.
    $secret_id = $configuration['secret_id'];
    $secret_key = $configuration['secret_key'];
    $token = $configuration['token'];
    $region = $configuration['region'];
    $schema = $configuration['schema'];
    $endpoint = $configuration['endpoint'];
    $timeout = $configuration['timeout'];
    $connect_timeout = $configuration['connect_timeout'];
    $ip = $configuration['ip'];
    $port = $configuration['port'];
    $domain = $configuration['domain'];
    $proxy = $configuration['proxy'];
    $retry = $configuration['retry'];
    $userAgent = $configuration['userAgent'];
    $allow_redirects = $configuration['allow_redirects'];

    

    // 判断是否需要sts（临时密钥方案）
    $sts = $configuration['sts'];
    $role_credential = $configuration['role_credential'];
    if($sts)
    {
      if($role_credential) {
        $credentials = self::getRoleCredential($configuration);
      } else {
        $credentials = self::getTempKey($configuration);
      }
      $secret_id = $credentials['tmpSecretId'];
      $secret_key = $credentials['tmpSecretKey'];
      $token = $credentials['sessionToken'];
    }
    // $credentials = self::getRoleCredential($configuration);
    // $secret_id = $credentials['tmpSecretId'];
    // $secret_key = $credentials['tmpSecretKey'];
    // $token = $credentials['sessionToken'];
    
    unset($configuration['secret_id'], $configuration['secret_key']);
    
    $config = new Config($configuration);

    $client = new \Qcloud\Cos\Client(
      array(
          'region' => $region, //园区
          'schema' => $schema, //协议头部，默认为http
          'timeout' => $timeout, //超时时间
          'connect_timeout' => $connect_timeout, //连接超时时间
          // 'endpoint' => $endpoint,
          'proxy' => $proxy, //代理服务器
          'retry' => $retry, //重试次数
          'userAgent' => $userAgent, //UA
          'allow_redirects' => $allow_redirects, //是否follow302
          'credentials'=> array(
              'secretId'  => $secret_id ,
              'secretKey' => $secret_key,
              'token'     => $token,
              // 'anonymous' => false, //匿名模式
          )
      )
    );

    return new static($client, $config);

  }


  /**
   * Reture Qcloud Cos sts
   * @return array
   * @throws \Exception
   */
  private static function getTempKey(array $configuration, $allow_prefix = array('*'), $allow_actions = array('*')) {

    // [
    //   'name/cos:HeadObject',
    //   'name/cos:PutObject',
    //   'name/cos:DoesObjectExist',
    //   'name/cos:GetObjectUrlWithoutSign',
    //   'name/cos:GetObjectUrl',
    //   'name/cos:CopyObject',
    //   'name/cos:GetObject',
    //   'name/cos:DeleteObject',
    //   'name/cos:PostObject',
    //   'name/cos:PutObjectACL',
    //   'name/cos:GetObjectACL',
    //   'name/cos:GetBucket',
    //   'name/cos:InitiateMultipartUpload',
    //   'name/cos:ListMultipartUploads',
    //   'name/cos:ListParts',
    //   'name/cos:UploadPart',
    //   'name/cos:CompleteMultipartUpload'
    // ]

    $duration_seconds = 7200;

    $secret_id = $configuration['secret_id'];
    $secret_key = $configuration['secret_key'];
    
    $bucket = $configuration['bucket'];
    $region = $configuration['region'];


    $sts = new Sts();
    
    $config = array(
      'endpoint' => "internal.tencentcloudapi.com", //接入点，内网填写"internal.tencentcloudapi.com"，外网填写"tencentcloudapi.com"
      'proxy' => '',
      'secretId' => $secret_id, // 固定密钥,若为明文密钥，请直接以'xxx'形式填入，不要填写到getenv()函数中
      'secretKey' => $secret_key, // 固定密钥,若为明文密钥，请直接以'xxx'形式填入，不要填写到getenv()函数中
      'bucket' => $bucket, // 换成你的 bucket
      'region' => $region, // 换成 bucket 所在园区
      'durationSeconds' => $duration_seconds, // 密钥有效期
      'allowPrefix' => $allow_prefix, // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
      // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
      'allowActions' => $allow_actions,
      // 临时密钥生效条件，关于condition的详细设置规则和COS支持的condition类型可以参考 https://cloud.tencent.com/document/product/436/71306
      // "condition" => array(
      //     "ip_equal" => array(
      //         "qcs:ip" => array(
      //             "10.217.182.3/24",
      //             "111.21.33.72/24",
      //         )
      //     )
      // )
    );
    $cache_key = 'qcloud_cos_tempkeys';
    if($credentials = \Drupal::cache()->get($cache_key))
    {
      return $credentials;
    }
    
    // 申请扮演角色
    $tempKeys = $sts->getTempKeys($config);
    if($tempKeys) {
      $credentials = $tempKeys['credentials'];
      \Drupal::cache()->set($cache_key, $credentials, $duration_seconds+7000);
      return $credentials;
    }
    return false;
  }

  private static function getRoleCredential(array $configuration, $allow_prefix = array('*'), $allow_actions = array ('*')) {
    $duration_seconds = 7200;
    $secret_id = $configuration['secret_id'];
    $secret_key = $configuration['secret_key'];

    $role_arn = $configuration['roleArn'];
    $bucket = $configuration['bucket'];
    $region = $configuration['region'];

    $sts = new Sts();

    $roleConfig = array(
      'roleArn' => $role_arn, //角色的资源描述，可在 [访问管理](https://console.cloud.tencent.com/cam/role) 点击角色名获取
      'endpoint' => 'internal.tencentcloudapi.com', // 接入点，内网填写"internal.tencentcloudapi.com"，外网填写"tencentcloudapi.com"
      'secretId' => $secret_id, // 固定密钥,若为明文密钥，请直接以'xxx'形式填入，不要填写到getenv()函数中
      'secretKey' => $secret_key, // 固定密钥,若为明文密钥，请直接以'xxx'形式填入，不要填写到getenv()函数中
      'bucket' => $bucket, // 换成你的 bucket
      'region' => $region, // 换成 bucket 所在园区
      'durationSeconds' => $duration_seconds, // 密钥有效期
      'allowPrefix' => $allow_prefix, // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
      // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
      'allowActions' => $allow_actions,
      'condition' => [],
    );
    
    $cache_key = 'qcloud_cos_drupal';
    if($credentials = \Drupal::cache()->get($cache_key))
    {
      return $credentials;
    }
    
    // 申请扮演角色
    $tempRoleKeys = $sts->getRoleCredential($roleConfig);
    if($tempRoleKeys) {
      $credentials = $tempRoleKeys['credentials'];
      \Drupal::cache()->set($cache_key, $credentials, $duration_seconds+7000);
      return $credentials;
    }
    return false;
  }

  /**
   * Returns the QcloudCos Flysystem adapter.
   *
   * Plugins should not keep references to the adapter. If a plugin needs to
   * perform filesystem operations, it should either use a scheme:// or have the
   * \Drupal\flysystem\FlysystemFactory injected.
   *
   * @return \League\Flysystem\AdapterInterface
   *   The Flysytem adapter.
   *
   * @throws \Exception
   */
  public function getAdapter() {
    return new QcloudCosAdapter($this->client, $this->bucket, $this->config, $this->prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function ensure($force = FALSE) {

    return [];
    // try {
    //   if (!$result = $this->client->doesBucketExist($this->bucket)) {
    //     return [
    //       [
    //         'severity' => RfcLogLevel::ERROR,
    //         'message' => 'Bucket %bucket does not exists.',
    //         'context' => [
    //           '%bucket' => $this->bucket,
    //         ],
    //       ],
    //     ];
    //   }
    //   return [];

    // }
    // catch (\Exception $exception) {
    //   return [
    //     [
    //       'severity' => RfcLogLevel::ERROR,
    //       'message' => $exception->getMessage(),
    //       'context' => [
    //         '%bucket' => $this->bucket,
    //       ],
    //     ],
    //   ];
    // }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getExternalUrl($uri) {

    $target = $this->getTarget($uri);

    if (strpos($target, 'styles/') === 0 && !file_exists($uri)) {
      $this->generateImageStyle($target);
    }

    $url = '';
    if ($this->config->get('visibility') === 'public') {
      try {
        $url = $this->client->getObjectUrlWithoutSign($this->bucket, UrlHelper::encodePath($target));
      } catch (\Exception $e) {
          // 请求失败
          \Drupal::logger('flysystem')->error("Can not get external url ". $uri . $e->getMessage());
      }
      
    }
    else {

      try {
        $url = $this->client->getObjectUrl($this->bucket, UrlHelper::encodePath($target), $this->expire);
      } catch (\Exception $e) {
          // 请求失败
          \Drupal::logger('flysystem')->error("Can not get external url ". $uri . $e->getMessage());
      }

    }

    $useSSL = $this->config->get('schema', 'http');
    $schema = $useSSL == 'https' ? 'https://' : 'http://';

    $url_prefix = $schema . $this->bucket . '.' . $this->endpoint;

    if (strpos($url, $url_prefix) === 0) {
      $relative_path = substr($url, strlen($url_prefix));
      $domain = $this->config->get('domain', '');
      if (!empty($domain)) {
        return $schema . $domain . $relative_path;
      }
    }
    return $url;
  }

}

<?php

namespace Drupal\flysystem_qcloud_cos\Flysystem\Adapter;


use Qcloud\Cos\Client;

use League\Flysystem\Util;
use League\Flysystem\Config;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;

/**
 * Qcloud Cos Adapter class.
 */
class QcloudCosAdapter extends AbstractAdapter {

  use StreamedTrait;

  /**
   * Aliyun Oss Client.
   *
   * @var \Qcloud\Cos\Client
   */
  protected $client;

  /**
   * Bucket name.
   *
   * @var string
   */
  protected $bucket;

  /**
   * The config.
   *
   * @var \League\Flysystem\Config
   */
  protected $config = [];

  /**
   * The options mapping.
   *
   * @var array
   */
  protected static $mappingOptions = [
    'mimetype' => 'Content-Type',
    'size' => 'Content-Length',
  ];

  /**
   * QcloudCosAdapter constructor.
   *
   * @param \Qcloud\Cos\Client $client
   *   The COS client.
   * @param string $bucket
   *   The bucket name.
   * @param \League\Flysystem\Config $config
   *   The config.
   * @param string $prefix
   *   The prefix.
   */
  public function __construct(Client $client, $bucket, Config $config, $prefix = '') {
    
    $this->client = $client;
    $this->bucket = $bucket;
    $this->config = $config;

    $this->setPathPrefix($prefix);
  }

  /**
   * Get the Qcloud Cos Client bucket.
   *
   * @return string
   *   The buckut.
   */
  public function getBucket() {
    return $this->bucket;
  }

  /**
   * Get the Qcloud Cos Client instance.
   *
   * @return \Qcloud\Cos\Client
   *   the client.
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function write($path, $contents, Config $config) {
    
    $object = $this->applyPathPrefix($path);
    $options = $this->getOptionsFromConfig($config);

    $ContentType = Util::guessMimeType($path, $contents);
    $ContentLength = Util::contentSize($contents);

    $object_data = [
      'Bucket' => $this->bucket,
      'Key' => $object,
      'Body' => $contents,
      'ContentType' => $ContentType,
      'ContentLength' => $ContentLength,
    ];

    if($config->has('Acl')) {
      $object_data['ACL'] = $config->get('Acl');
    }


    $this->client->putObject($object_data);
      
    $type = 'file';
    $result = compact('type', 'path', 'contents');

    $result['mimetype'] = $ContentType;
    $result['size'] = $ContentLength;

    // \Drupal::logger('flysystem')->notice('test#9size='.$result['size'].'mimetype='.$result['mimetype']);

    return $result;

    // try {
    //   $this->client->putObject($object_data);
      
    //   $type = 'file';
    //   $result = compact('type', 'path', 'contents');

    //   $result['mimetype'] = $ContentType;
    //   $result['size'] = $ContentLength;

    //   // \Drupal::logger('flysystem')->notice('test#9size='.$result['size'].'mimetype='.$result['mimetype']);

    //   return $result;

    // } catch (\Exception $e) {
    //     // 请求失败
    //     \Drupal::logger('flysystem')->error("Failed write file ". $object .' '. $e->getMessage());
    // }
    
    return false;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function update($path, $contents, Config $config) {
    if (!$config->has('visibility') && !$config->has('Acl')) {
      $config->set('Acl', $this->getObjectAcl($path));
    }
    return $this->write($path, $contents, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($path, $newpath) {
    $this->copy($path, $newpath);
    $this->delete($path);

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function copy($path, $newpath) {
    $object = $this->applyPathPrefix($path);
    $newobject = $this->applyPathPrefix($newpath);

    try{
      
      $this->client->copyObject([
        'Bucket' => $this->bucket,
        'Key' => $newobject,
        'CopySource' => urlencode($object),
        'MetadataDirective' => 'Replaced',
      ]);

      return true;
    } catch (\Exception $e) {
        // 请求失败
        \Drupal::logger('flysystem')->error("Failed copy file ". $newobject . $e->getMessage());
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($path) {
    $object = $this->applyPathPrefix($path);

    try{
      $this->client->deleteObject([
        'Bucket' => $this->bucket,
        'Key' => $object
      ]);
      return true;
    } catch (\Exception $e) {
      // 请求失败
      \Drupal::logger('flysystem')->error("Failed delete file ". $object . $e->getMessage());
    }
    return false;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function deleteDir($dirname) {
    $nextMarker = '';
    $isTruncated = true;
    while ( $isTruncated ) {
        try {
            $result = $this->client->listObjects(
                ['Bucket' => $this->bucket,
                'Delimiter' => '',
                'EncodingType' => 'url',
                'Marker' => $nextMarker,
                'Prefix' => $dirname,
                'MaxKeys' => 1000]
            );    
            $isTruncated = $result['IsTruncated'];
            $nextMarker = $result['NextMarker'];
            foreach ( $result['Contents'] as $content ) {
                $cos_file_path = $content['Key'];
                $local_file_path = $content['Key'];
                try {
                  $this->client->deleteObject(array(
                    'Bucket' => $this->bucket,
                    'Key' => urldecode($cos_file_path),
                  ));
                  
                  \Drupal::logger('flysystem')->notice("Deleted file ". $cos_file_path);
                } catch ( \Exception $e ) {
                  \Drupal::logger('flysystem')->error("Failed to delete file  ". $cos_file_path .' '. $e->getMessage());
                }
            }
        } catch ( \Exception $e ) {
            // 请求失败
            \Drupal::logger('flysystem')->error("Failed to delete dir ". $dirname . $e->getMessage());
        }
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function createDir($dirname, Config $config) {

    //不需要创建目录
    return ['path' => $dirname, 'type' => 'dir'];

    $object = $this->applyPathPrefix($dirname);

    $options = $this->getOptionsFromConfig($config);

    

    try {
      $result = $this->client->putObject(array(
          'Bucket' => $this->bucket,
          'Key' => $object.'/',
          'Body' => "",
      ));
      return ['path' => $dirname, 'type' => 'dir'];

    } catch (\Exception $e) {
        // 请求失败
        \Drupal::logger('flysystem')->error("Failed create dir ". $dirname .' '. $e->getMessage());
        return false;
    }

  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function has($path) {

    $base_name = basename($path);
    if(strpos($base_name,'.') === false) {
      return true;
    }

    $object = $this->applyPathPrefix($path);

    //=======
    if ($this->client->doesObjectExist($this->bucket, $object)) {
      return TRUE;
    }
    return false;
    
    // try {
    //   if ($this->client->doesObjectExist($this->bucket, $object)) {
    //     return TRUE;
    //   }
      
    //   // \Drupal::logger('flysystem')->notice('test#1-1 '.$object);
    //   // return $this->doesDirectoryExist($object);
    //   return false;

    // } catch (\Exception $e) {
    //   // 请求失败
    //   \Drupal::logger('flysystem')->error("Failed to find ". $path. '. ' . $e->getMessage());
    // }

  }

  /**
   * {@inheritdoc}
   */
  public function read($path) {
    $object = $this->applyPathPrefix($path);
    try {
      
      $contents_object = $this->client->getObject($this->bucket, $object);
      $contents_arr = $contents_object->toArray();
      $contents = $contents_arr['Body'];

      return compact('contents', 'path');

    } catch (\Exception $e) {
      // 请求失败
      \Drupal::logger('flysystem')->error("Failed to read ". $path . $e->getMessage());
    }

    return [];
    
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function listContents($directory = '', $recursive = FALSE) {
    $directory = $this->applyPathSeparator($directory);
    $directory = $this->applyPathPrefix($directory);

    $delimiter = '';
    $nextMarker = '';
    $maxkeys = 1000;
    $options = [
      'Bucket'    => $this->bucket,
      'Delimiter' => $delimiter,
      'EncodingType' => 'url',
      'Prefix'    => $directory,
      'MaxKeys'  => $maxkeys,
      'Marker'    => $nextMarker,
    ];

    $list_files = [];

    $isTruncated = true;
    while ( $isTruncated ) {
        try {
            $result = $this->client->listObjects($options);    
            $isTruncated = $result['IsTruncated'];
            $nextMarker = $result['NextMarker'];

            $list_files[] = [
              'type'      => 'dir',
              'path'      => $result['prefix'],
              'timestamp' => time(),
            ];
            foreach ( $result['Contents'] as $content ) {
                $cos_file_path = $content['Key'];
                $local_file_path = $content['Key'];
                $list_files[] = [
                  'type'      => 'file',
                  'path'      => $cos_file_path,
                  'timestamp' => time(),
                ];
            }
        } catch ( \Exception $e ) {
          \Drupal::logger('flysystem')->error("Failed to list ". $result['prefix'] . $e->getMessage());
        }
    }
    return $list_files;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getMetadata($path) {
    $object = $this->applyPathPrefix($path);
    try {
      $result = $this->client->headObject([
        $this->bucket,
        $object
      ]);

      $result = $result->toArray();
      $data = [
        'type'      => 'file',
        'dirname'   => Util::dirname($path),
        'path'      => $path,
        'timestamp' => strtotime($result['LastModified']),
        'mimetype'  => $result['ContentType'],
        'size'      => $result['ContentLength'],
        'visibility' => $this->config->get('visibility', AdapterInterface::VISIBILITY_PRIVATE),
      ];

      \Drupal::logger('flysystem')->notice('test#7 size='.$result['ContentLength'].'mimetype='.$result['ContentType']);

      return $data;
    }
    catch (\Exception $exception) {
      return [
        'type' => 'dir',
        'path' => $path,
        'timestamp' => REQUEST_TIME,
        'size' => FALSE,
        'visibility' => $this->config->get('visibility', AdapterInterface::VISIBILITY_PRIVATE),
      ];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getSize($path) {
    return $this->getMetadata($path);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getMimetype($path) {
    return $this->getMetadata($path);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getTimestamp($path) {
    return $this->getMetadata($path);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function setVisibility($path, $visibility) {
    // No chance to set visibility per object,
    // not sure if it's an issue of flysystem module.
    $object = $this->applyPathPrefix($path);
    
    // visibility = default/private/public-read
    $visibility_value = $visibility == AdapterInterface::VISIBILITY_PUBLIC? 'public-read':AdapterInterface::VISIBILITY_PRIVATE;

    $this->client->putObjectAcl([
      'Bucket' => $this->bucket,
      'Key' => $object,
      'ACL' => $visibility_value
    ]);
    return [
      'object' => $object,
      'visibility' => $visibility,
    ];

    // try {
    //   $this->client->putObjectAcl([
    //     'Bucket' => $this->bucket,
    //     'Key' => $object,
    //     'ACL' => $visibility_value
    //   ]);
    //   return [
    //     'object' => $object,
    //     'visibility' => $visibility,
    //   ];
    // } catch (\Exception $e) {
    //   \Drupal::logger('flysystem')->error("Failed to set visibility[".$visibility_value."] ". $object .'. '. $e->getMessage());
    // }
    
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getVisibility($path) {
    $bucket = $this->bucket;
    $object = $this->applyPathPrefix($path);

    $result = $this->client->getObjectAcl([
      'Bucket' => $bucket,
      'Key' => $object
    ]);

    return $result->toArray();

    // try {
    //   $result = $this->client->getObjectAcl([
    //     'Bucket' => $bucket,
    //     'Key' => $object
    //   ]);
  
    //   return $result->toArray();

    // } catch (\Exception $e) {
    //   \Drupal::logger('flysystem')->error("Failed to get visibility ". $path . $e->getMessage());
    // }
    
  }

  /**
   * Get options from the config.
   *
   * @param \League\Flysystem\Config $config
   *   The config.
   *
   * @return array
   *   The options.
   */
  protected function getOptionsFromConfig(Config $config) {
    $options = [];
    foreach (static::$mappingOptions as $option => $option_value) {
      if (!$config->has($option)) {
        continue;
      }
      $options[$option_value] = $config->get($option);
    }

    return $options;
  }

  /**
   * Get the acl of object.
   *
   * @param string $path
   *   The path/object to check.
   *
   * @return string
   *   The visibility.
   *
   * @throws \Exception
   */
  protected function getObjectAcl($path) {
    $metadata = $this->getVisibility($path);
    if(!isset($metadata['ACL'])){
      return AdapterInterface::VISIBILITY_PUBLIC;
    }
    return $metadata['ACL'] ===  'public-read'? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
  }

  /**
   * Check directory exist of not.
   *
   * @param string $object
   *   The object to check.
   *
   * @return bool
   *   The result.
   *
   * @throws \Exception
   */
  protected function doesDirectoryExist($object) {
    $bucket = $this->bucket;
    $delimiter = '';
    $nextMarker = '';
    $maxkeys = 1000;

    if(strpos($object,'/') !== false)
    {
      $path = dirname($object);
    }else{
      $path = $object;
    }

    $prefix = rtrim($path, '/') . '/';

    $options = [
      'Bucket'    => $bucket,
      'Delimiter' => $delimiter,
      'Prefix'    => $prefix,
      'MaxKeys'   => $maxkeys,
      'Marker'    => $nextMarker,
    ];

    try {
      $objects = $this->client->ListObjects($options);
      return $objects;
    } catch (\Exception $e) {
      \Drupal::logger('flysystem')->error("Directory checked error ". $prefix .' - '. $object .'. '. $e->getMessage());
    }
    
  }

  /**
   * Add a path separator.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The path with separator.
   */
  protected function applyPathSeparator($path) {
    return rtrim($path, '\\/') . '/';
  }

}

<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileUriFormatter;

/**
 * Class QcloudCosFileUriFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosFileUriFormatter extends FileUriFormatter {
  use QcloudCosFieldFormatterTrait;

}

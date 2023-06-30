<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\UrlPlainFormatter;

/**
 * Class QcloudCosFileUrlPlainFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosFileUrlPlainFormatter extends UrlPlainFormatter {
  use QcloudCosFieldFormatterTrait;

}

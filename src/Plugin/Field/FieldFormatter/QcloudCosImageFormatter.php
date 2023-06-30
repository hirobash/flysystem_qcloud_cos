<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Class QcloudCosImageFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosImageFormatter extends ImageFormatter {
  use QcloudCosFieldFormatterTrait;

}

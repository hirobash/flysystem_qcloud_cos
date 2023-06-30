<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter;

/**
 * Class QcloudCosImageUrlFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosImageUrlFormatter extends ImageUrlFormatter {
  use QcloudCosFieldFormatterTrait;

}

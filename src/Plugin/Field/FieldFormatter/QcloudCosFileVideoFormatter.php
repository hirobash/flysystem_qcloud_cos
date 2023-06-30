<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileVideoFormatter;

/**
 * Class QcloudCosFileVideoFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosFileVideoFormatter extends FileVideoFormatter {
  use QcloudCosFieldFormatterTrait;

}

<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\TableFormatter;

/**
 * Class QcloudCosFileTableFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosFileTableFormatter extends TableFormatter {
  use QcloudCosFieldFormatterTrait;

}

<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Class QcloudCosFileDefaultFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosFileDefaultFormatter extends GenericFileFormatter {
  use QcloudCosFieldFormatterTrait;

}

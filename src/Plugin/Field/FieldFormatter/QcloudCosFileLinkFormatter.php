<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\DefaultFileFormatter;

/**
 * Class QcloudCosFileLinkFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosFileLinkFormatter extends DefaultFileFormatter {
  use QcloudCosFieldFormatterTrait;

}

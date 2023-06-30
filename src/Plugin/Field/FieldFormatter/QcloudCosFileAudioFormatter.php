<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\FileAudioFormatter;

/**
 * Class QcloudCosFileAudioFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosFileAudioFormatter extends FileAudioFormatter {
  use QcloudCosFieldFormatterTrait;

}

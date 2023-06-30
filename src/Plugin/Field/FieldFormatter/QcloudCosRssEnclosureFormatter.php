<?php

namespace Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\RSSEnclosureFormatter;

/**
 * Class QcloudCosRssEnclosureFormatter.
 *
 * @package Drupal\flysystem_qcloud_cos\Plugin\Field\FieldFormatter
 */
class QcloudCosRssEnclosureFormatter extends RSSEnclosureFormatter {
  use QcloudCosFieldFormatterTrait;

}

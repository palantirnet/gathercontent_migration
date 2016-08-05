<?php
/**
 * @file
 * Contains \Drupal\gathercontent_migration\Plugin\migrate\process\GatherContentExtractLinks.
 *
 * Copyright 2016 Palantir.net, Inc.
 */

namespace Drupal\gathercontent_migration\Plugin\migrate\process;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;


/**
 * @MigrateProcessPlugin(
 *   id = "gathercontent_extract_links"
 * )
 */
class GatherContentExtractLinks extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $links = array();

    if (preg_match_all('#<a [^>]*href="(.+?)"[^>]*>(.+?)</a>#', $value, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $m) {
        $links[] = [
          'uri' => $m[1],
          'title' => strip_tags($m[2]),
          'options' => [],
        ];
      }
    }

    return $links;
  }

}

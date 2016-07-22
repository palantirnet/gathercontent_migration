<?php
/**
 * @file
 * Contains \Drupal\gathercontent_migration\Plugin\migrate\process\Dump.
 */

namespace Drupal\gathercontent_migration\Plugin\migrate\process;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;


/**
 * @MigrateProcessPlugin(
 *   id = "dump"
 * )
 */
class Dump extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (isset($this->configuration['method']) && $this->configuration['method'] == 'row') {
      print_r($row->getSource());
    }
    else {
      print_r($value);
    }

    print "\n";
    return $value;
  }

}

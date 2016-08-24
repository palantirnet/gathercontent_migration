<?php
/**
 * @file
 * Contains \Drupal\gathercontent_migration\Plugin\migrate\process\GatherContentRetrieveFile.
 *
 * @see https://evolvingweb.ca/blog/bringing-files-along-for-ride-to-d8
 *
 * Copyright 2016 Palantir.net, Inc.
 */

namespace Drupal\gathercontent_migration\Plugin\migrate\process;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;


/**
 * @MigrateProcessPlugin(
 *   id = "gathercontent_retrieve_file"
 * )
 */
class GatherContentRetrieveFile extends ProcessPluginBase {

  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      // Skip this item if there's no URL.
      throw new MigrateSkipProcessException();
    }

    $scheme = isset($this->configuration['scheme']) ? $this->configuration['scheme'] : 'public://';

    $path_key = isset($this->configuration['path']) ? $this->configuration['path'] : 'gathercontent_migration';
    $path = $row->getSourceProperty($path_key);

    $filename_key = isset($this->configuration['filename']) ? $this->configuration['filename'] : '';
    $filename = $row->getSourceProperty($filename_key);

    $replace_arg = isset($this->configuration['replace']) ? $this->configuration['replace'] : 'IGNORE';
    $replace = ['FILE_EXISTS_ERROR' => FILE_EXISTS_ERROR, 'FILE_EXISTS_RENAME' => FILE_EXISTS_RENAME, 'FILE_EXISTS_REPLACE' => FILE_EXISTS_REPLACE, 'IGNORE' => 'IGNORE'][$replace_arg];

    // Save the file.
    $file = system_retrieve_file($value, "{$scheme}{$path}/{$filename}", TRUE, $replace);
    $message = 'Failed to retrieve file "'. $value . '""';

    if ($file) {
      return $file->id();
    }
    elseif ($replace === 'IGNORE') {
      throw new MigrateSkipProcessException($message);
    }
    elseif ($replace === 'FILE_EXISTS_ERROR') {
      throw new MigrateSkipRowException($message);
    }
    else {
      throw new MigrateException($message);
    }
  }

}

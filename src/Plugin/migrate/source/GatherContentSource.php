<?php

/**
 * @file
 * Contains \Drupal\gathercontent_migration\Plugin\migrate\source\GatherContent.
 */

namespace Drupal\gathercontent_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use GatherContent;

/**
 * Source plugin for GatherContent items.
 *
 * @MigrateSource(
 *   id = "gathercontent"
 * )
 */
class GatherContentSource extends SourcePluginBase {

  // Required configuration parameters.
  protected $email;
  protected $api_key;

  // Whether to load the full item content.
  protected $expand_items = TRUE;

  // Either project_id OR project_name + account_slug are required configuration
  // parameters. In the latter case,
  // GatherContentSource::initialializeConnection() will populate project_id.
  protected $project_id;
  protected $project_name;
  protected $account_slug;

  // Optional configuration parameters.
  protected $template_name;
  protected $template_id;

  /**
   * @var GatherContent\Model\Template
   * Loaded by GatherContentSource::initialializeConnection().
   */
  protected $template;

  /**
   * @var array|string
   * Array of key => value pairs that items in this migration must match.
   * If template_name or template_id is available, then template_id will be
   * automatically added by GatherContentSource::initialializeConnection().
   */
  protected $include_filters;
  protected $exclude_filters;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->skipCount = TRUE;
    $this->trackChanges = TRUE;

    $this->email = \Drupal::config('gathercontent_migration.gathercontent')->get('email');
    $this->api_key = \Drupal::config('gathercontent_migration.gathercontent')->get('api_key');

    $this->include_filters = isset($configuration['include_filters']) ? $configuration['include_filters'] : [];
    $this->exclude_filters = isset($configuration['exclude_filters']) ? $configuration['exclude_filters'] : [];

    $this->account_slug = isset($configuration['account_slug']) ? $configuration['account_slug'] : NULL;
    $this->project_name = isset($configuration['project_name']) ? $configuration['project_name'] : NULL;
    $this->project_id = isset($configuration['project_id']) ? $configuration['project_id'] : NULL;
    $this->template_name = isset($configuration['template_name']) ? $configuration['template_name'] : NULL;
    $this->template_id = isset($configuration['template_id']) ? $configuration['template_id'] : NULL;

    $this->expand_items = isset($configuration['expand_items']) ? $configuration['expand_items'] : TRUE;

    if (empty($this->email) || empty($this->api_key)) {
      throw new \Exception('Missing GatherContent API connection in gathercontent_migration module configuration; email and api_key are required.');
    }

    if (!($this->project_id || ($this->account_slug && $this->project_name))) {
      throw new \Exception('Missing GatherContent project config; either project_id or account_slug and project_name are required.');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function __toString() {
    return 'GatherContent';
  }

  /**
   * @param string $account_slug
   * @param string $project_name
   * @return GatherContent\Model\Project
   * @throws \Exception
   */
  protected function retrieveProject($account_slug, $project_name) {
    $account = $this->retrieveAccount($account_slug);

    $filter = function ($project) use ($project_name) { return $project->name == $project_name; };
    $project = current(array_filter($account->projects(), $filter));

    if ($project) {
      return $project;
    }

    throw new \Exception('Failed to load GatherContent project.');
  }

  /**
   * @param string $account_slug
   * @return GatherContent\Model\Account
   * @throws \Exception
   */
  protected function retrieveAccount($account_slug) {
    $accounts = new GatherContent\Model\AccountCollection();
    $account = $accounts->findBySlug($account_slug);

    if ($account) {
      return $account;
    }

    throw new \Exception('Failed to load GatherContent account.');
  }

  /**
   * @param $template_id
   * @return \GatherContent\Model\Template
   */
  protected function retrieveTemplate($template_id) {
    return GatherContent\Model\Template::retrieveTemplate($template_id);
  }

  /**
   * @param $project_id
   * @param $template_name
   * @return mixed
   */
  protected function retrieveTemplateByName($project_id, $template_name) {
    $templates = new GatherContent\Model\TemplateCollection();
    return $templates->findByName($project_id, $template_name);
  }

  /**
   * @param $project_id
   * @return array
   */
  protected function retrieveItems($project_id) {
    $items = new GatherContent\Model\ItemCollection();
    return $items->forProjectId($project_id);
  }

  /**
   * @param $item_id
   * @return \GatherContent\Model\Item
   */
  protected function retrieveItem($item_id) {
    print "retrieving item $item_id\n";
    return GatherContent\Model\Item::retrieveItem($item_id);
  }

  /**
   * @param $item_id
   * @return array
   */
  protected function retrieveItemFiles($item_id) {
    print "retrieving files for item $item_id\n";
    $files = new GatherContent\Model\FileCollection();
    return $files->forItemId($item_id);
  }

  /**
   * @return \Closure
   */
  protected function getItemsFilter() {
    $this->initializeConnection();

    $include_filters = $this->include_filters;
    $exclude_filters = $this->exclude_filters;

    return function ($item) use ($include_filters, $exclude_filters) {
      foreach ($include_filters as $k => $v) {
        $v = is_array($v) ? $v : [$v];
        if (!in_array($item->$k, $v)) {
          return FALSE;
        }
      }

      foreach ($exclude_filters as $k => $v) {
        $v = is_array($v) ? $v : [$v];
        if (in_array($item->$k, $v)) {
          return FALSE;
        }
      }

      return TRUE;
    };
  }

  /**
   * Make sure we have a project_id and template_id.
   */
  protected function initializeConnection() {
    GatherContent\Configuration::configure($this->email, $this->api_key);

    if (!$this->project_id) {
      $project = $this->retrieveProject($this->account_slug, $this->project_name);
      $this->project_id = $project->id;
    }

    if (!$this->template) {
      if ($this->template_id) {
        $this->template = $this->retrieveTemplate($this->template_id);
      }
      elseif ($this->template_name) {
        $this->template = $this->retrieveTemplateByName($this->project_id, $this->template_name);
        $this->template_id = $this->template->id;
      }
    }

    if ($this->template_id) {
      $this->include_filters['template_id'] = $this->template_id;
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function initializeIterator() {
    $this->initializeConnection();

    // Retrieve the items from GatherContent.
    $items = $this->retrieveItems($this->project_id);
    // Remove items based on template_id or other filters.
    $items = array_filter($items, $this->getItemsFilter());
    // Clean array keys.
    $items = array_values($items);
    // All data should be arrays.
    $items = array_map(function ($item) { return (array) $item; }, $items);

    return new \ArrayIterator($items);
  }

  /**
   * Load full item content and files from GatherContent.
   *
   * @param \Drupal\migrate\Row $row
   */
  protected function expandRow(Row $row) {
    $item_id = $row->getSourceProperty('id');

    $item = $this->retrieveItem($item_id);
    $files = $this->retrieveItemFiles($item_id);
    $fields = $item->getFields();

    // Flatten field values.
    $field_values = [];
    foreach ($fields as $field) {
      $value = $field->value;

      if ($field->type == 'files') {
        // Match files to the field in which they appear.
        $value = [];
        foreach ($files as $file) {
          if ($file->field == $field->name) {
            $value[] = (array) $file;
          }
        }
      }
      elseif (strpos($field->type, 'choice_') === 0) {
        // Flatten multiple choice values.
        $value = array_filter(array_map(function($opt) { return $opt['selected'] ? $opt['label'] : NULL; }, $field->options));
      }

      // Provide IDs as field names for stability.
      $field_values[$field->name] = $value;

      // ... but also provide labels as field names for ease of use.
      if ($field->label) {
        $field_values[$field->label] = $value;
      }
    }

    $row->setSourceProperty('fields', $field_values);
    $row->setSourceProperty('status', $item->status['data']['name']);
    $row->setSourceProperty('expanded', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $ret = parent::prepareRow($row);

    // Only fetch the full item information if we really really need to.
    if ($this->expand_items && $ret === TRUE && ($row->changed() || $row->needsUpdate()) && !$row->getSourceProperty('expanded')) {
      $this->expandRow($row);
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $this->initializeConnection();

    $fields = $this->configuration['fields'];

    $fields['id'] = 'Item ID';
    $fields['project_id'] = 'Project ID';
    $fields['parent_id'] = 'Parent item ID';
    $fields['template_id'] = 'Template ID';
    $fields['position'] = 'Position/order in the item list';
    $fields['name'] = 'Item name';
    $fields['notes'] = 'Notes';
    $fields['type'] = 'Generally just "item"';
    $fields['overdue'] = 'Overdue; true or false';
    $fields['created_at/date'] = 'Created date, as YYYY-MM-DD HH:MM:SS.000000';
    $fields['created_at/timezone_type'] = 'Timezone type; generally "3"';
    $fields['created_at/timezone'] = 'Timezone code; generally "UTC"';
    $fields['updated_at/date'] = 'Update date';
    $fields['updated_at/timezone_type'] = 'Timezone type';
    $fields['updated_at/timezone'] = 'Timezone code';
    $fields['status/data/id'] = 'Current status ID';
    $fields['status/data/is_default'] = 'Whether the status is the default status; true or false';
    $fields['status/data/position'] = 'Position of the status in the status list';
    $fields['status/data/color'] = 'Status color';
    $fields['status/data/name'] = 'Status name';
    $fields['status/data/description'] = 'Status description';
    $fields['status/data/can_edit'] = 'Whether the current account can edit items with this status; true or false';
    $fields['due_dates/data/0/status_id'] = 'ID of a status with a due date for this item';
    $fields['due_dates/data/0/overdue'] = 'Is this item overdue for this status; true or false';
    $fields['due_dates/data/0/due_date/date'] = 'Due date';
    $fields['due_dates/data/0/due_date/timezone_type'] = 'Timezone type';
    $fields['due_dates/data/0/due_date/timezone'] = 'Timezone code';

    if ($this->expand_items) {
      $fields['status'] = 'Name of the current item status';
      $fields['expanded'] = 'Whether the full item data has been loaded; generally true';
      $fields['fields'] = 'Array of field values';
    }

    if ($this->expand_items && $this->template) {
      $template_fields = $this->template->getFields();
      foreach ($template_fields as $f) {
        $fields["fields/{$f->name}"] = $f->label;

        if ($f->label) {
          $fields["fields/{$f->label}"] = $f->label;
        }
      }
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => ['type' => 'integer'],
    ];
  }

}

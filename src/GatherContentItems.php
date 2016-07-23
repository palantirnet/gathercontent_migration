<?php
/**
 * Created by PhpStorm.
 * User: white
 * Date: 7/21/16
 * Time: 7:40 PM
 */

namespace Drupal\gathercontent_migration;

use GatherContent;

class GatherContentItems implements \Iterator {

  /** @var string */
  protected $project_id = NULL;

  /** @var bool */
  protected $loaded = FALSE;

  /** @var array */
  protected $items = array();

  /** @var array */
  protected $expanded = array();

  /** @var int */
  protected $position = 0;

  /**
   * GatherContentItems constructor.
   *
   * @param string $project_id Looks like an int.
   */
  public function __construct($project_id) {
    $this->project_id = $project_id;
  }

  /**
   * Create an instance of this class from the account slug and project name.
   *
   * @param string $account_slug
   * @param string $project_name
   * @return \Drupal\gathercontent_migration\GatherContentItems
   * @throws \Exception
   */
  public static function factory($account_slug, $project_name) {
    $accounts = new GatherContent\Model\AccountCollection();
    /** @var GatherContent\Model\Account $account */
    $account = $accounts->findBySlug($account_slug);

    $project = NULL;
    if ($account) {
      $filter = function ($project) use ($project_name) { return $project->name == $project_name; };
      $project = current(array_filter($account->projects(), $filter));
    }
    else {
      throw new \Exception('Failed to load GatherContent account.');
    }

    if ($project) {
      return new self($email, $api_key, $account->id, $project->id);
    }
    else {
      throw new \Exception('Failed to load GatherContent project.');
    }
  }

  /**
   * Load the item list.
   */
  protected function load() {
    if (!$this->loaded) {
      $this->loaded = TRUE;

      $items = new GatherContent\Model\ItemCollection();
      $this->items = $items->forProjectId($this->project_id);
    }
  }

  /**
   * Load the full content for an item.
   *
   * @param $item
   * @return array
   */
  public function expand(GatherContent\Model\Item $item) {
    print "expanding $item->id $item->name\n";
    $item = GatherContent\Model\Item::retrieveItem($item->id);
    return $this->process($item);


    if (!isset($this->expanded[$item->id])) {
      $item = GatherContent\Model\Item::retrieveItem($item->id);
      $this->expanded[$item->id] = $this->process($item);
    }

    return $this->expanded[$item->id];
  }

  protected function process(GatherContent\Model\Item $item) {
    $flat = clone $item;

    // Flatten the status field.
    $flat->status = $item->status['data']['name'];

    // Flatten all field values.
    $flat->fields = [];
    foreach ($item->getFields() as $f) {
      if ($f->type == 'choice_checkbox') {
        $f->value = array_filter(array_map(function($opt) { return $opt['selected'] ? $opt['label'] : NULL; }, $f->options));
      }

      $flat->fields[$f->name] = $f->value;
      if ($f->label) {
        $flat->fields[$f->label] = $f->value;
      }
    }

    // Remove bulky original field content.
//    unset($flat->config, $flat->tabs);

    return (array) $flat;
  }

  public function currentItem() {
    $this->load();
    return $this->items[$this->position];
  }

  public function currentRow() {
    $item = $this->currentItem();
    return $this->expand($item);
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return $this->currentRow();
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->position++;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    $this->load();
    return $this->position < count($this->items);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->position = 0;
  }
}

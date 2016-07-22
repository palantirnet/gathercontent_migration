<?php
/**
 * Created by PhpStorm.
 * User: white
 * Date: 7/21/16
 * Time: 11:16 PM
 */

namespace Drupal\gathercontent_migration;


use Iterator;

class GatherContentItemsFiltered extends \FilterIterator {

  /** @var callable */
  protected $filter;

  /**
   * @inheritDoc
   */
  public function __construct(GatherContentItems $iterator, $filter) {
    parent::__construct($iterator);
    $this->filter = $filter;
  }

  /**
   * @inheritDoc
   */
  public function accept() {
    /** @var GatherContentItems $iterator */
    $iterator = $this->getInnerIterator();
    $item = $iterator->currentItem();

    if (FALSE === call_user_func($this->filter, $item)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function rewind() {
    $iterator = $this->getInnerIterator();
    $iterator->rewind();
  }

  /**
   * @inheritDoc
   */
  public function valid() {
    $iterator = $this->getInnerIterator();
    return $iterator->valid();
  }

  /**
   * @inheritDoc
   */
  public function key() {
    $iterator = $this->getInnerIterator();
    return $iterator->key();
  }

  /**
   * @inheritDoc
   */
  public function current() {
    $iterator = $this->getInnerIterator();
    return $iterator->current();
  }

  /**
   * @inheritDoc
   */
  public function next() {
    $iterator = $this->getInnerIterator();

    do {
      $iterator->next();
    } while (!$this->accept());
  }


}

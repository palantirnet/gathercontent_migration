<?php

/**
 * @file
 * Contains \Drupal\gathercontent_migration\Plugin\migrate\source\GatherContent.
 */

namespace Drupal\gathercontent_migration\Plugin\migrate\source;

use Drupal\gathercontent_migration\GatherContentItems;
use Drupal\gathercontent_migration\GatherContentItemsFiltered;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use GatherContent;
use Symfony\Component\Finder\Iterator\CustomFilterIterator;

/**
 * Source plugin for GatherContent items.
 *
 * @MigrateSource(
 *   id = "gathercontent"
 * )
 */
class GatherContentSource extends SourcePluginBase {

  protected $email;
  protected $api_key;
  protected $project_name;
  protected $project_id;
  protected $account_slug;
  protected $template_name;
  protected $template_id;
  /** @var GatherContent\Model\Template */
  protected $template;

  /** @var GatherContentItemsFlat */
  protected $items;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->skipCount = TRUE;

    $this->email = \Drupal::config('gathercontent_migration.gathercontent')->get('email');
    $this->api_key = \Drupal::config('gathercontent_migration.gathercontent')->get('api_key');

    $this->account_slug = isset($configuration['account_slug']) ? $configuration['account_slug'] : NULL;
    $this->project_name = isset($configuration['project_name']) ? $configuration['project_name'] : NULL;
    $this->project_id = isset($configuration['project_id']) ? $configuration['project_id'] : NULL;
    $this->template_name = isset($configuration['template_name']) ? $configuration['template_name'] : NULL;
    $this->template_id = isset($configuration['template_id']) ? $configuration['template_id'] : NULL;

    if (empty($this->email) || empty($this->api_key)) {
      throw new \Exception('Missing GatherContent API connection in gathercontent_migration module configuration; email and api_key are required.');
    }

    if (!($this->project_id || ($this->account_slug && $this->project_name))) {
      throw new \Exception('Missing GatherContent project config; either project_id or account_slug and project_name are required.');
    }
  }

  /**
   * @inheritDoc
   */
  public function __toString() {
    return 'GatherContent';
  }

  /**
   * @inheritDoc
   */
  protected function initializeIterator() {
    if ($this->template_name || $this->template_id) {
      $this->initializeTemplate();
    }

    GatherContent\Configuration::configure($this->email, $this->api_key);

    $iterator = FALSE;
    if ($this->project_id) {
      $iterator = new GatherContentItems($this->project_id);
    }
    elseif ($this->account_slug && $this->project_name) {
      $iterator = GatherContentItems::factory($this->account_slug, $this->project_name);
    }

    if ($iterator && $this->template_id) {
      $template_id = $this->template_id;
      $filter = function($item) use ($template_id) { return $item->template_id == $template_id; };
      $iterator = new GatherContentItemsFiltered($iterator, $filter);
    }

    return $iterator;
  }

  public function initializeTemplate() {
    if (!$this->template) {
      GatherContent\Configuration::configure($this->email, $this->api_key);

      if ($this->template_name && !$this->template_id) {
        $this->template = (new GatherContent\Model\TemplateCollection())->findByName($this->project_id, $this->template_name);
        $this->template_id = $this->template->id;
      }
      elseif ($this->template_id) {
        $this->template = (new GatherContent\Model\Template())->retrieveTemplate($this->template_id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $this->initializeTemplate();
    if ($this->template) {

      $template_fields = [];

      $fields = $this->template->getFields();
      foreach ($fields as $f) {
        $template_fields[$f->name] = $f->label;

        if ($f->label) {
          $template_fields[$f->label] = $f->label;
        }
      }

      return $template_fields;
    }
    else {
      return $this->configuration['fields'];
    }
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

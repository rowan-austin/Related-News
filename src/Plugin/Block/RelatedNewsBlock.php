<?php

namespace Drupal\related_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\related_news\Services\RelatedNewsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a related news block.
 *
 * @Block(
 *   id = "related_news_block",
 *   admin_label = @Translation("Related News Block"),
 *   category = @Translation("Custom"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node")),
 *   }
 * )
 */
class RelatedNewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The related news service.
   *
   * @var Drupal\related_news\Services\RelatedNewsService
   *   The service for getting related news.
   */
  protected RelatedNewsService $relatedNewsService;

  /**
   * The related news block constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block ID.
   * @param mixed $plugin_definition
   *   The block plugin definition.
   * @param Drupal\related_news\Services\RelatedNewsService $related_news_service
   *   The related news service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RelatedNewsService $related_news_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->relatedNewsService = $related_news_service;
  }

  /**
   * The related news block create function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The plugin container.
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block ID.
   * @param mixed $plugin_definition
   *   The block plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('related_news.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build['container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => 'related-news-container'],
    ];
    /** var \Drupal\related_nodes\Services\RelatedNewsService $related_news_service  */
    $build['container']['markup'] = $this->relatedNewsService->getRelatedNewsByNode($this->getContextValue('node'));
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['related_news_tag']);
  }

}

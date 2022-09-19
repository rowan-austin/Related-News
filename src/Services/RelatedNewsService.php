<?php

namespace Drupal\realted_news\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * A Related News Manager Class.
 *
 * Service providing related news functionality.
 *
 * @package Drupal\related_news\Services
 */
class RelatedNewsService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Construct the RelatedNewsService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Function for getting the related news node ids from taxonomy terms.
   *
   * @param array $news_type_tids
   *   An array of program tame taxonomy IDs.
   * @param array $news_location_tids
   *   An array of program type taxonomy IDs.
   * @param array $excluded_nids
   *   An array of excluded node IDs.
   * @param int $count
   *   The number of news teasers to display.
   *
   * @return array
   *   Returns an array of related news node ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getRelatedNewsByTerms(array $news_type_tids = [], array $news_location_tids = [], array $excluded_nids = [], int $count = 3): array {
    $related_news_nids = [];

    /** @var \Drupal\node\NodeStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    // Primary pref is for news with the 'news type' taxonomy in the list.
    if ($news_type_tids) {
      $nids = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'news_page')
        ->condition('field_news_type', (array) $news_type_tids, 'IN')
        ->condition('nid', $excluded_nids, 'NOT IN')
        ->sort('created', 'ASC')
        ->range(0, $count)
        ->execute();
      if (!empty($nids)) {
        $related_news_nids = $nids;
        $excluded_nids = array_merge($excluded_nids, $nids);
      }
    }

    // Secondary pref is for news with the 'news location' taxonomy in the list.
    if ((count($related_news_nids) < $count) && count($news_location_tids)) {
      $nids = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'news_page')
        ->condition('field_news_location', (array) $news_location_tids, 'IN')
        ->condition('nid', $excluded_nids, 'NOT IN')
        ->sort('created', 'ASC')
        ->range(0, $count - count($related_news_nids))
        ->execute();
      if (!empty($nids)) {
        $related_news_nids = array_merge((array) $related_news_nids, $nids);
        $excluded_nids = array_merge($excluded_nids, $nids);
      }
    }

    // Make up to $count by adding recent news.
    if (count($related_news_nids) < $count) {
      $nids = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'news_page')
        ->condition('nid', $excluded_nids, 'NOT IN')
        ->sort('created', 'ASC')
        ->range(0, $count - count($related_news_nids))
        ->execute();
      if (!empty($nids)) {
        $related_news_nids = array_merge((array) $related_news_nids, $nids);
      }
    }
    return $this->renderNodes($related_news_nids);
  }

  /**
   * Function for getting the related news node ids.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The current node.
   * @param int $count
   *   The number of news items to display.
   *
   * @return array
   *   Returns an array of related news node ids.
   */
  public function getRelatedNewsByNode(NodeInterface $node, int $count = 4): array {
    $news_type_tids = [];
    $news_location_tids = [];
    $excluded_nids = [$node->id()];
    if ($node->hasField('field_news_type')) {
      foreach ($node->get('field_news_type') as $reference) {
        $news_type_tids[] = $reference->target_id;
      }
    }

    if ($node->hasField('field_news_location')) {
      foreach ($node->get('field_news_location') as $reference) {
        $news_location_tids[] = $reference->target_id;
      }
    }
    return $this->getRelatedNewsByTerms($news_type_tids, $news_location_tids, $excluded_nids, $count);
  }

  /**
   * Function for rendering related news nodes.
   *
   * @param array $nids
   *   The node ids to be rendered.
   *
   * @return array
   *   Returns an array of built nodes to be display on the page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function renderNodes(array $nids): array {
    $build = [];
    foreach ($nids as $nid) {
      $entity = $this->entityTypeManager->getStorage('node')->load($nid);
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $pre_render = $view_builder->view($entity, 'teaser');
      $build[] = $pre_render;
    }

    if ($build) {
      $build['#cache']['tags'][] = 'related_news_tag';
    }

    return $build;
  }

}

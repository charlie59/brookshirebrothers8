<?php

namespace Drupal\bb\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class StoreLocatorForm.
 *
 * @package Drupal\bb\Form
 */
class StoreLocatorController extends ControllerBase {

  /**
   * @return array
   * @throws Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function locatorForm() {

    $build = [];
    $items = [];

    /* Preprocessing for Store Locator */
    $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('department');
    $departments = [];
    foreach ($terms as $term) {
      $departments[] = [
        'id' => $term->tid,
        'name' => $term->name,
      ];
    }
    $items['departments'] = $departments;

    try {
      $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('specification');
    } catch (Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException $e) {
    } catch (Drupal\Component\Plugin\Exception\PluginNotFoundException $e) {
    }
    $specifications = [];
    foreach ($terms as $term) {
      $specifications[] = [
        'id' => $term->tid,
        'name' => $term->name,
      ];
    }
    $items['specifications'] = $specifications;

    $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('locations');
    $locations = [];
    foreach ($terms as $term) {
      $locations[] = [
        'id' => $term->tid,
        'name' => $term->name,
      ];
    }
    $items['locations'] = $locations;

    // Render the 'store_locator' theme.
    $build['store_locator'] = [
      '#theme' => 'store_locator',
      '#items' => $items,
    ];

    return $build;
  }

}

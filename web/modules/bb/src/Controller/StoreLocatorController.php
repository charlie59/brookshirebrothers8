<?php

namespace Drupal\bb\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class StoreLocatorForm.
 *
 * @package Drupal\bb\Form
 */
class StoreLocatorController extends ControllerBase {

  /**
   * @return string[]
   */
  public function locatorForm() {

    $build = [];

    // Render the 'store_locator' theme.
    $build['store_locator'] = array(
      '#theme' => 'store_locator',
    );

    return $build;
  }

}

<?php

namespace Drupal\bb\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\node\Entity\Node;

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

  /**
   * Uses Google API
   * Works with Zip, State, and "City, State"
   *
   * @param $zip
   * @return mixed
   */
  private function getLatLong($zip){
    $url = "https://maps.googleapis.com/maps/api/geocode/json?&key=AIzaSyAQNRPaZd4ibswz8dB7gpOZyajfvtkRaAI&address=" . $zip;
    $result_string = file_get_contents($url);
    $result = json_decode($result_string, true);
    return $result['results'][0]['geometry']['location'];
  }

  /**
   * @param $lat1
   * @param $lon1
   * @param $lat2
   * @param $lon2
   * @return float|int
   */
  private function distance($lat1, $lon1, $lat2, $lon2) {
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
      return 0;
    }
    else {
      $theta = $lon1 - $lon2;
      $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
      $dist = acos($dist);
      $dist = rad2deg($dist);
      return $dist * 60 * 1.1515;
    }
  }

  /**
   * Results template.
   */
  public function locatorResults() {

    /* Check "Zip", which could be "City, State" or Zip. */
    $zipCode = trim($_GET['zipCode']);
    if (empty($zipCode)) {
      return $this->redirect('bb.store_locator');
    }

    $build = [];
    $terms = [];
    $items = [];
    $stores = [];

    /* Get all Matching Store Locations */
    $query = Drupal::entityQuery('node');
    $query->condition('type', 'store_location');

    /* Main Locations taxonomy, field is named field_department */
    try {
      $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('department');
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    $mainLocations = [];
    if (isset($_GET['Brookshire_Brothers'])) {
      $mainLocations[] = 1;
    }
    if (isset($_GET['Brookshire_Brothers_Express'])) {
      $mainLocations[] = 2;
    }
    if (count($mainLocations) > 0 && count($mainLocations) != count($terms)) {
      $query->condition('field_department', $mainLocations, "IN");
    }

    /* Departments taxonomy, field is named field_specification */
    try {
      $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('specification');
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    $departments = [];
    if (isset($_GET['Pharmacy'])) {
      $departments[] = 3;
    }
    if (isset($_GET['Redbox'])) {
      $departments[] = 592;
    }
    if (isset($_GET['Drive-thru_Pharmacy'])) {
      $departments[] = 4;
    }
    if (isset($_GET['Flu_Shot'])) {
      $departments[] = 5;
    }
    if (isset($_GET['Fuel'])) {
      $departments[] = 6;
    }
    if (isset($_GET['Beverage_Depot_(21_and_over_to_purchase)'])) {
      $departments[] = 7;
    }
    if (isset($_GET['Bakery'])) {
      $departments[] = 8;
    }
    if (isset($_GET['Deli'])) {
      $departments[] = 9;
    }
    if (isset($_GET['Floral'])) {
      $departments[] = 10;
    }
    if (isset($_GET['Bissell_Rental'])) {
      $departments[] = 588;
    }
    if (count($departments) > 0 && count($departments) != count($terms)) {
      $query->condition('field_specification', $departments, "IN");
    }

    /* (Secondary) Locations taxonomy, field is named field_department */
    try {
      $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('locations');
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    $locations = [];
    if (isset($_GET['Cormie’s_Grocery'])) {
      $locations[] = 12;
    }
    if (isset($_GET['David’s'])) {
      $locations[] = 14;
    }
    if (isset($_GET['David’s_Express'])) {
      $locations[] = 15;
    }
    if (isset($_GET['Pecan_Foods'])) {
      $locations[] = 16;
    }
    if (isset($_GET['Polk_Pick-It-Up'])) {
      $locations[] = 11;
    }
    if (isset($_GET['Tobacco_Barn'])) {
      $locations[] = 13;
    }
    if (count($locations) > 0 && count($locations) != count($terms)) {
      $query->condition('field_locations', $locations, "IN");
    }

    $nids = $query->execute();

    // get LatLong from Zip
    $latLong = $this->getLatLong($zipCode);

    foreach ($nids as $nid) {
      $node = Node::Load($nid);
      $distance = $this->distance($latLong['lat'], $latLong['lng'], $node->get('field_latitude')->value, $node->get('field_longitude')->value);
      if ($distance <= (int) $_GET['filterDistance']) {
        $stores[] = [
          'title' => $node->getTitle(),
          'distance' => round($distance),
        ];
      }
    }
    $items['stores'] = $stores;

    // Render the 'store_locator_results' theme.
    $build['store_locator_results'] = [
      '#theme' => 'store_locator_results',
      '#items' => $items,
    ];
    return $build;
  }

}

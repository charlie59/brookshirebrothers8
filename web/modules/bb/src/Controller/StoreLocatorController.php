<?php

namespace Drupal\bb\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\node\Entity\Node;
use Exception;
use SforcePartnerClient;

/**
 * Class StoreLocatorForm.
 *
 * @package Drupal\bb\Form
 */
class StoreLocatorController extends ControllerBase {

  /**
   * Var Setup.
   *
   */
  protected $soapDir;
  protected $soapUser;
  protected $soapPass;
  protected $mySforceConnection;
  protected $ids;
  protected $fieldList;
  protected $sfStores;


  /**
   * StoreLocatorController constructor.
   */
  public function __construct() {
    $this->messenger = Drupal::messenger();
    $this->soapDir = __DIR__ . "/../../../../libraries/salesforce/soapclient";
    $this->soapUser = "drupalintegration@brookshirebros.com";
    $this->soapPass = "Br00kshire2017uZRlliWUmOl8Mkm9y8AGX7PD";
    $this->ids = [];
    $this->fieldList = "Store_Number__c,Name,BillingStreet,BillingCity,BillingState,BillingPostalCode,Phone,Store_Director_Formula__c,Store_Hours__c,Beverage_Depot_Location__c,Has_Bakery__c,Has_Deli__c,Has_Weekly_Ad__c,Has_Floral__c,Redbox__c,Store_Location__Latitude__s,Store_Location__Longitude__s,BB_Pharmacy__c,Pharmacy_Number__c,Pharmacist_Text__c,Pharmacy_State_Board_Number__c,Pharmacy_Phone__c,Pharmacy_Fax__c,Pharmacy_Hours__c,Has_Pharmacy_Drive_Thru__c,Offers_Flu_Shot__c,Has_Fuel__c,Fuel_Brand__c,BB_Tobacco_Barn__c,Tobacco_Barn_Number__c,Tobacco_Barn_Manager_Text__c,Tobacco_Barn_Hours__c,Has_Subway__c,Subway_Phone__c,Subway_Director__c,Has_Washateria__c,Washateria_Phone__c,Washateria_Director__c,Has_Car_Wash__c,Car_Wash_Phone__c,Car_Wash_Director__c,Has_Mr_Payroll__c,Mr_Payroll_Phone__c,BBros_Text_Signup__c,TBarn_Text_signup__c,WeeklyAd__c,Bissell_Location__c";
  }

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
  private function getLatLong($zip) {
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
    } else {
      $theta = $lon1 - $lon2;
      $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
      $dist = acos($dist);
      $dist = rad2deg($dist);
      return $dist * 60 * 1.1515;
    }
  }

  /**
   * Results template.
   */
  public function locatorResults() {

    /* Check "zipCode", which could be "City, State" or Zip. */
    if (!isset($_GET['zipCode']) || empty($_GET['zipCode'])) {
      $this->messenger->addMessage('No zip code or city entered!', 'error');
      return $this->redirect('bb.store_locator');
    }
    $zipCode = trim($_GET['zipCode']);

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
    if (count($mainLocations) > 0 && count($mainLocations) < count($terms)) {
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
    if (count($departments) > 0 && count($departments) < count($terms)) {
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
    if (count($locations) > 0 && count($locations) < count($terms)) {
      $query->condition('field_locations', $locations, "IN");
    }

    $nids = $query->execute();

    // get LatLong from zipcode
    $latLong = $this->getLatLong($zipCode);
    $items['latLong'] = [
      'lat' => $latLong['lat'],
      'lng' => $latLong['lng'],
    ];

    foreach ($nids as $nid) {
      $node = Node::Load($nid);
      // Use stored lat and lng to get distance in miles from search zipCode
      $lat = $node->get('field_latitude')->value;
      $lng = $node->get('field_longitude')->value;
      $distance = $this->distance($latLong['lat'], $latLong['lng'], $lat, $lng);
      if ($distance <= (int)$_GET['filterDistance']) {
        $stores[] = [
          'nid' => $nid,
          'title' => $node->getTitle(),
          'lat' => $lat,
          'lng' => $lng,
        ];
      }
    }
    $items['stores'] = $stores;
    $items['zipCode'] = $zipCode;
    $items['distance'] = (int)$_GET['filterDistance'];
    $items['total'] = count($stores);

    // Render the 'store_locator_results' theme.
    $build['store_locator_results'] = [
      '#theme' => 'store_locator_results',
      '#items' => $items,
    ];
    return $build;
  }

  /**
   * Retrieve ids of Salesforce Account records.
   */
  private function getIds() {
    $query = "Select Id from Account Where Type = 'BB Store'";
    $response = $this->mySforceConnection->query($query);
    foreach ($response->records as $object) {
      $this->ids[] = $object->Id[0];
    }
  }

  /**
   * Retrieve field values from Salesforce.
   */
  private function getSfStores() {
    $this->sfStores = $this->mySforceConnection->retrieve($this->fieldList, 'Account', $this->ids);;
  }

  /**
   * Connect to Salesforce and update Store nodes.
   * TODO - there's probably a more Drupal way of doing this.
   */
  function updateStores() {

    require_once ($this->soapDir.'/SforcePartnerClient.php');
    require_once ($this->soapDir.'/SforceHeaderOptions.php');
    require_once ($this->soapDir.'/SforceBaseClient.php');

    try {
      $this->mySforceConnection = new SforcePartnerClient();
      $this->mySforceConnection->createConnection($this->soapDir.'/partner.wsdl.xml');
      $this->mySforceConnection->login($this->soapUser, $this->soapPass);
    } catch (Exception $e) {
      echo $this->mySforceConnection->getLastRequest();
      echo $e->faultstring;
    }

    $this->getIds();
    $this->getSfstores();

    if (count($this->sfStores) > 0) {
      foreach ($this->sfStores as $object) {
        $store_number = $object->fields->Store_Number__c;
        if (!empty($store_number)) {
          $query = Drupal::entityQuery('node')
            ->condition('type', 'store_location')
            ->condition('field_number_store', $store_number);
          $node = $query->execute();
        }
      }
    }

    return [
      '#markup' => 'Update completed',
    ];

  }

}

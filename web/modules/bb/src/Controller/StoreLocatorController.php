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
  protected $locations;
  protected $sfStores;
  protected $entityTypeManager;
  protected $nodeType;


  /**
   * StoreLocatorController constructor.
   */
  public function __construct() {
    $this->messenger = Drupal::messenger();
    $this->entityTypeManager = Drupal::entityTypeManager();
    $this->soapDir = __DIR__ . "/../../../../libraries/salesforce/soapclient";
    $this->soapUser = "drupalintegration@brookshirebros.com";
    $this->soapPass = "Br00kshire2017uZRlliWUmOl8Mkm9y8AGX7PD";
    $this->ids = [];
    $this->nodeType = 'store_location';
    $this->fieldList = [
      'Store_Number__c' => 'field_number_store',
      'Name' => 'field_store_name',
      'BillingStreet' => 'field_address',
      'BillingCity' => 'field_city',
      'BillingState' => 'field_state',
      'BillingPostalCode' => 'field_zip_code',
      'Phone' => 'field_store_phone',
      'Store_Director_Formula__c' => 'field_store_manager',
      'Store_Hours__c' => 'field_store_hours',
      'Beverage_Depot_Location__c' => ['field_department' => 7],
      'Has_Bakery__c' => ['field_department' => 8],
      'Has_Deli__c' => ['field_department' => 9],
      'Has_Weekly_Ad__c' => 'field_weekly_ad',
      'Has_Floral__c' => ['field_department' => 10],
      'Has_Subway__c' => ['field_department' => 677],
      'Redbox__c' => ['field_department' => 592],
      'Has_Pharmacy_Drive_Thru__c' => ['field_department' => 4],
      'Offers_Flu_Shot__c' => ['field_department' => 5],
      'Has_Fuel__c' => ['field_department' => 6],
      'BB_Pharmacy__c' => ['field_department' => 3],
      'Bissell_Location__c' => ['field_department' => 588],
      'BB_Tobacco_Barn__c' => ['field_department' => 678],
      'Has_Washateria__c' => ['field_department' => 679],
      'Has_Car_Wash__c' => ['field_department' => 680],
      'Has_Mr_Payroll__c' => ['field_department' => 681],
      'Store_Location__Latitude__s' => 'field_latitude',
      'Store_Location__Longitude__s' => 'field_longitude',
      'GooglePlusDataString__c' => 'field_google_plus_code',
      'Pharmacy_Number__c' => 'field_pharmacy_number',
      'Pharmacist_Text__c' => 'field_pharmacist',
      'Pharmacy_State_Board_Number__c' => 'field_state_board_number',
      'Pharmacy_Phone__c' => 'field_pharmacy_phone',
      'Pharmacy_Fax__c' => 'field_pharmacy_fax',
      'Pharmacy_Hours__c' => 'field_pharmacy_hours',
      'Fuel_Brand__c' => 'field_fuel_brand',
      'Tobacco_Barn_Number__c' => 'field_barn_number',
      'Tobacco_Barn_Manager_Text__c' => 'field_barn_manager',
      'Tobacco_Barn_Hours__c' => 'field_barn_hours',
      'Subway_Phone__c' => 'field_subway_phone',
      'Subway_Director__c' => 'field_subway_director',
      'Washateria_Phone__c' => 'field_washateria_phone',
      'Washateria_Director__c' => 'field_washateria_director',
      'Car_Wash_Phone__c' => 'field_car_wash_phone',
      'Car_Wash_Director__c' => 'field_car_wash_director',
      'Mr_Payroll_Phone__c' => 'field_mr_payroll_phone',
      'BBros_Text_Signup__c' => 'field_bbros_text_signup__c',
      'TBarn_Text_signup__c' => 'field_tbarn_text_signup__c',
      'WeeklyAd__c' => 'field_weekly_ad_link',
    ];
    $this->locations = [
      ['B&B Express' => 672],
      ['B&B Stand Alone Pharmacy' => 675],
      ['Brookshire Brothers' => 1],
      ['Brookshire Brothers Express' => 2],
      ['Cormie’s Grocery' => 12],
      ['David’s' => 14],
      ['David’s Express' => 15],
      ['Pecan Foods' => 16],
      ['Petro Barn - stand alone' => 673],
      ['Polk Pick It Up' => 11],
      ['Subway' => 676],
      ['Texas Star' => 674],
      ['Tobacco Barn by Brookshire Brothers' => 13],
    ];
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
    /* Locations */
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('department');
    $locations = [];
    foreach ($terms as $term) {
      $locations[] = [
        'id' => $term->tid,
        'name' => $term->name,
      ];
    }
    $items['locations'] = $locations;

    /* Departments */
    try {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('specification');
    } catch (Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException $e) {
    } catch (Drupal\Component\Plugin\Exception\PluginNotFoundException $e) {
    }
    $departments = [];
    foreach ($terms as $term) {
      $departments[] = [
        'id' => $term->tid,
        'name' => $term->name,
      ];
    }
    $items['departments'] = $departments;

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
    $query->condition('type', $this->nodeType);

    /* Locations taxonomy, machine_name department */
    try {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('department');
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    $locations = [];
    foreach ($terms as $term) {
      $fixed = str_replace(' ', '_', $term->name);
      if (isset($fixed)) {
        $locations[] = $term->tid;
      }
    }
    if (count($locations) > 0 && count($locations) < count($terms)) {
      $query->condition('field_location', $locations, "IN");
    }

    /* Departments taxonomy, field is named field_department */
    try {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('specification');
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }
    $departments = [];
    foreach ($terms as $term) {
      $fixed = str_replace(' ', '_', $term->name);
      if (isset($fixed)) {
        $departments[] = $term->tid;
      }
    }
    if (count($departments) > 0 && count($departments) < count($terms)) {
      $query->condition('field_department', $departments, "IN");
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
      $url = $node->toUrl()->toString();
      if ($distance <= (int)$_GET['filterDistance']) {
        $stores[] = [
          'nid' => $nid,
          'link' => $url,
          'title' => $node->get('field_display_title')->value,
          'lat' => $lat,
          'lng' => $lng,
          'google_plus_code' => $node->get('field_google_plus_code')->value,
          'address' => $node->get('field_address')->value,
          'city' => $node->get('field_city')->value,
          'state' => $node->get('field_state')->value,
          'zip' => $node->get('field_zip_code')->value,
          'adLink' => $node->get('field_weekly_ad_link')->value,
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
    $fields = '';
    foreach ($this->fieldList as $k => $v) {
      $fields .= $k . ',';
    }
    $string = rtrim($fields, ',');
    $this->sfStores = $this->mySforceConnection->retrieve($string, 'Account', $this->ids);;
  }

  /**
   * Connect to Salesforce and update Store nodes.
   * TODO - there's probably a more Drupal way of doing this.
   *
   * @return string[]
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  function updateStores() {

    require_once($this->soapDir . '/SforcePartnerClient.php');
    require_once($this->soapDir . '/SforceHeaderOptions.php');
    require_once($this->soapDir . '/SforceBaseClient.php');

    $counter = 0;

    try {
      $this->mySforceConnection = new SforcePartnerClient();
      $this->mySforceConnection->createConnection($this->soapDir . '/partner.wsdl.xml');
      $this->mySforceConnection->login($this->soapUser, $this->soapPass);
    } catch (Exception $e) {
      echo $this->mySforceConnection->getLastRequest();
      echo $e->faultstring;
    }

    $this->getIds();
    $this->getSfstores();

    if (count($this->sfStores) > 0) {
      foreach ($this->sfStores as $object) {
        $sfValues = $object->fields;
        // print_r($sfValues);
        $store_number = $sfValues->Store_Number__c;
        // Something very wrong if no Store Number from Salesforce.
        if (!empty($store_number)) {
          $node = NULL;
          $query = Drupal::entityQuery('node')
            ->condition('type', $this->nodeType)
            ->condition('field_number_store', $store_number);
          $result = $query->execute();
          // Set up values for upsert
          $values = ['title' => $sfValues->Name . ' Store ' . $store_number];
          // Departments taxonomy
          $values['field_department'] = [];
          // Main locations taxonomy
          $values['field_location'] = [];
          foreach ($this->fieldList as $sfField => $fieldName) {
            switch ($sfField) {
              case 'Name':
                $values['field_store_name'] = $sfValues->Name;
                $values['field_display_title'] = $sfValues->Name;
                foreach ($this->locations as $location) {
                  $tmp = key($location);
                  if ($sfValues->Name == $tmp) {
                    $values['field_location'][] = ['target_id' => $location[$tmp]];
                    continue;
                  }
                }
                break;

              default:
                if (is_array($fieldName)) {
                  // Salesforce returns true or false for checkboxes.
                  $val = $sfValues->$sfField == 'true' ? 1 : 0;
                  if ($val === 1) {
                    // Set taxonomy value
                    $values['field_department'][] = ['target_id' => $fieldName['field_department']];
                  }
                } else {
                  switch ($sfValues->$sfField) {
                    case 'true';
                      $val = 1;
                      break;
                    case 'false':
                      $val = 0;
                      break;
                    default:
                      $val = $sfValues->$sfField;
                  }
                  $values[$fieldName] = $val;
                }
            }
          }
          // print_r($values);
          // Query returns empty array if no match for Store Number in existing node values.
          if (count($result) > 0) {
            try {
              $node = $this->entityTypeManager->getStorage('node')->load(reset($result));
              foreach ($values as $field => $value) {
                $node->$field = $value;
              }
            } catch (InvalidPluginDefinitionException $e) {
            } catch (PluginNotFoundException $e) {
            }
          } else {
            try {
              $storage = $this->entityTypeManager->getStorage('node');
              $values['type'] = $this->nodeType;
              $node = $storage->create($values);
            } catch (InvalidPluginDefinitionException $e) {
            } catch (PluginNotFoundException $e) {
            }
          }
          $node->save();
          $counter++;
        }
      }
    }

    return [
      '#markup' => "<p>Updated $counter stores.</p>",
    ];

  }

}

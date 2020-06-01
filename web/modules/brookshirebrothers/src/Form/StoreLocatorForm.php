<?php

namespace Drupal\brookshirebrothers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StoreLocatorForm.
 *
 * @package Drupal\brookshirebrothers\Form
 */
class StoreLocatorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brookshirebrothers_store_locator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

  /**
   * Submit handler.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}

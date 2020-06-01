<?php

use Drupal\Core\Form\FormStateInterface;

function brookshirebrothers_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {

  $form['brookshirebrothers_settings'] = [
    '#type' => 'fieldset',
    '#title' => t('Brookshire Brothers settings'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  ];

}

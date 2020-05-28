<?php

function brookshirebrothers_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id = NULL) {

  $form['brookshirebrothers_settings'] = [
    '#type' => 'fieldset',
    '#title' => t('Brookshire Brothers settings'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
  ];

  $form['brookshirebrothers_settings']['google_maps_api_key'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Google Maps API Key'),
    '#default_value' => theme_get_setting('google_maps_api_key'),
    '#description'   => t("Used to determine user location."),
  );
}

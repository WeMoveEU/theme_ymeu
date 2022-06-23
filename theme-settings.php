<?php

function theme_ymeu_form_system_theme_settings_alter(&$form, $form_state, $form_id = NULL) {

  $form['youmove'] = array(
    '#type' => 'fieldset',
    '#title' => t('Campaign'),
  );

  $form['youmove']['campaign_webform_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Campaign page ID'),
    '#default_value' => theme_get_setting('campaign_webform_id'),
  );

  // Set custom validate function
    // - based on system_theme_settings_validate()
    // - with 1 exception: svg logo is excepted 
    $form['#validate'] = array();
    $form['#validate'][] = 'theme_ymeu_system_theme_settings_validate';
}

function theme_ymeu_system_theme_settings_validate($form, &$form_state) {
  // Handle file uploads.
  $validators = array('file_validate_extensions' => array('ico png gif jpg jpeg apng svg'));
  //$validators = array('file_validate_is_image' => array());

  // Check for a new uploaded logo.
  $file = file_save_upload('logo_upload', $validators);
  if (isset($file)) {
    // File upload was attempted.
    if ($file) {
      // Put the temporary file in form_values so we can save it on submit.
      $form_state['values']['logo_upload'] = $file;
    }
    else {
      // File upload failed.
      form_set_error('logo_upload', t('The logo could not be uploaded.'));
    }
  }

  $validators = array('file_validate_extensions' => array('ico png gif jpg jpeg apng svg'));

  // Check for a new uploaded favicon.
  $file = file_save_upload('favicon_upload', $validators);
  if (isset($file)) {
    // File upload was attempted.
    if ($file) {
      // Put the temporary file in form_values so we can save it on submit.
      $form_state['values']['favicon_upload'] = $file;
    }
    else {
      // File upload failed.
      form_set_error('favicon_upload', t('The favicon could not be uploaded.'));
    }
  }

  // If the user provided a path for a logo or favicon file, make sure a file
  // exists at that path.
  if (!empty($form_state['values']['logo_path'])) {
    $path = _system_theme_settings_validate_path($form_state['values']['logo_path']);
    if (!$path) {
      form_set_error('logo_path', t('The custom logo path is invalid.'));
    }
  }
  if (!empty($form_state['values']['favicon_path'])) {
    $path = _system_theme_settings_validate_path($form_state['values']['favicon_path']);
    if (!$path) {
      form_set_error('favicon_path', t('The custom favicon path is invalid.'));
    }
  }
}
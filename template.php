<?php

function  theme_ymeu_preprocess_html(&$vars) {
  // CAMPAIGN Page (webform)
  //
  if(_is_campaign_page()) {
    $vars['classes_array'][] = 'you-campaign';

    drupal_add_js(array('webformCampaignId' => trim(theme_get_setting('campaign_webform_id'))), 'setting');
  }

  if($node_submission = _check_campaign_submission_page()) {
    if(_is_webform_submission_ready_to_publish($node_submission['node'],$node_submission['submission'])) {
        $vars['classes_array'][] = 'campaign-published';
    }
  }

  $vars['html_attributes_array'] = array();
  $vars['body_attributes_array'] = array();

  // HTML element attributes
  //
  $vars['html_attributes_array']['lang'] = $vars['language']->language;
  $vars['html_attributes_array']['dir']  = $vars['language']->dir;

  // BODY element attributes
  //
  $vars['body_attributes_array']['class'] = $vars['classes_array'];
  $vars['body_attributes_array'] += $vars['attributes_array'];
  $vars['attributes_array'] = '';
}

function theme_ymeu_process_html(&$vars) {
  // Flatten out html_attributes and body_attributes
  //
  $vars['html_attributes'] = drupal_attributes($vars['html_attributes_array']);
  $vars['body_attributes'] = drupal_attributes($vars['body_attributes_array']);
}

function theme_ymeu_preprocess_page(array &$variables) {
  if (!empty($variables['page']['sidebar_first']) && !empty($variables['page']['sidebar_second'])) {
    $variables['content_column_class'] = ' class="col-12 col-md-6"';
  }
  elseif (!empty($variables['page']['sidebar_first']) || !empty($variables['page']['sidebar_second'])) {
    $variables['content_column_class'] = ' class="col-12 col-md-9"';
  }
  else {
    $variables['content_column_class'] = ' class="col-12 col-md-12"';
  }

  /*** USER PAGE ***/
  if (user_is_logged_in() && arg(0) == 'user' && is_numeric(arg(1)) ) {
    $vars['title'] = $vars['user']->name;
    unset($vars['tabs']['#secondary']);
  }
}

function theme_ymeu_preprocess_node(array &$variables) {}

function theme_ymeu_html_head_alter(&$head_elements) {
  $head_elements['system_meta_content_type']['#attributes'] = array(
    'charset' => 'utf-8',
  );
}

// BLOCK
//
function theme_ymeu_preprocess_block(&$vars) {

  // Language block
  if($vars['block']->module == 'locale' && $vars['block']->delta == 'language') {
    global $language;

    $curr_content = $vars['content'];
    $pos_ul_start = strpos($curr_content,'<ul');
    $pos_ul_end = strpos($curr_content,'>',$pos_ul_start);

    if (is_int($pos_ul_start) && is_int($pos_ul_end) && ($pos_ul_start < $pos_ul_end)) {
      $new_content = '<ul id="language" class="dropdown-menu" aria-labelledby="dropdownlanguage">' . substr($curr_content,$pos_ul_end+1);
      $curr_content = $new_content;
    }

    $vars['content'] =
      '<div class="dropdown">' .
      '<button class="btn btn-default dropdown-toggle" type="button" id="dropdownlanguage" data-bs-toggle="dropdown" aria-expanded="false">' .
          t($language->name) .
      '</button>'. $curr_content .'</div>';
  }
}

// PRIMARY TABS
//
function theme_ymeu_menu_local_task($variables) {
	$menu_item_options = db_query("SELECT options FROM {menu_links} WHERE link_path = :link_path", array(':link_path' => $variables['element']['#link']['path']))->fetchField();
	if ($menu_item_options) {
		$menu_item_options = unserialize($menu_item_options);
		$variables['element']['#link']['localized_options'] = $menu_item_options;
	}
	return theme_menu_local_task($variables);
}

// MAIN MENU
//
function theme_ymeu_menu_tree__menu_main_menu_youmove($variables) {
  $top_menu = menu_tree_all_data('menu-main-menu-youmove', null, 1);
  $is_top_level = false;

  foreach ($top_menu as $top_menu_item) {
    if(array_key_exists('link', $top_menu_item)) {
      if(array_key_exists($top_menu_item['link']['mlid'],$variables['#tree'])) {
        $is_top_level = true;
      }
    }
  }

  return $is_top_level ? '<ul class="navbar-nav">' . $variables['tree'] . '</ul>' : '<ul class="dropdown-menu">' . $variables['tree'] . '</ul>';
}

function theme_ymeu_menu_link__menu_main_menu_youmove($variables) {
  $element = $variables['element'];

  // Delete useless classes
  $element['#attributes']['class'] = array_diff($element['#attributes']['class'],['expanded', 'leaf']);
  if(array_key_exists('class', $element['#localized_options']['attributes'])) {
    $element['#localized_options']['attributes']['class'] = 
      array_diff(
        $element['#localized_options']['attributes']['class'],
        ['dropdown-toggle']
      );
  }

  $element['#attributes']['class'][] = 'nav-item';
  $element['#localized_options']['attributes']['class'][] = 'nav-link';
  $mlid = $element["#original_link"]["mlid"];
  

  if(count($element['#below']) > 0 ) {
    // $element['#href'] = '';
    $element['#attributes']['class'][] = 'dropdown';

    $element['#localized_options']['attributes']['class'][] = 'dropdown-toggle';
    $element['#localized_options']['attributes']['role'] = "button";
    $element['#localized_options']['attributes']['data-toggle'] = "dropdown";
    $element['#localized_options']['attributes']['aria-haspopup'] = "true";
    $element['#localized_options']['attributes']['aria-expanded'] = "false";
    $element['#localized_options']['attributes']['id'] = "menu-item-".$mlid;
    $element['#localized_options']['attributes']['title'] = $element['#title'];
    $element['#localized_options']['html'] = true;
  }

  $sub_menu = '';
  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);

  return '<li ' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

// FORM
//
function theme_ymeu_form_alter(&$form, &$form_state, $form_id) {
  $campaign_webform_id = trim(theme_get_setting('campaign_webform_id'));

  if($form_id === "webform_client_form_{$campaign_webform_id}") {
    $form['#after_build'][] = '_theme_ymeu_webform_dragndrop_text_override';

    if(isset($form['actions']['next'])) {
      $form['actions']['next']['#value'] = t('Next step').' →';
    }

    if(isset($form['actions']['previous'])) {
      $form['actions']['previous']['#value'] = '← '.t('Previous Step');
    }
  }
}

// WEBFORM
//
function theme_ymeu_webform_element(&$variables) {
  $element = $variables['element'];
  $output = '<div ' . drupal_attributes($element['#wrapper_attributes']) . '>' . "\n";
  $prefix = isset($element['#field_prefix']) ? '<span class="field-prefix">' . webform_filter_xss($element['#field_prefix']) . '</span> ' : '';
  $suffix = isset($element['#field_suffix']) ? ' <span class="field-suffix">' . webform_filter_xss($element['#field_suffix']) . '</span>' : '';

  // Generate description for above or below the field.
  $above = !empty($element['#webform_component']['extra']['#webform_component']['extra']['description_above']);
  $description = array(
    FALSE => '',
    TRUE => !empty($element['#description']) ? ' <div class="description">' . $element['#description'] . "</div>\n" : '',
  );

  // If #children does not contain an element with a matching @id, do not
  // include @for in the label.
  if (isset($variables['element']['#id']) && strpos($element['#children'], ' id="' . $variables['element']['#id'] . '"') === FALSE) {
    $variables['element']['#id'] = NULL;
  }

  switch ($element['#title_display']) {
    case 'inline':
      $output .= $description[$above];
      $description[$above] = '';
      // FALL THRU.
    case 'before':
    case 'invisible':
      $output .= ' ' . theme('form_element_label', $variables);
      $output .= ' ' . $description[$above] . $prefix . $element['#children'] . $suffix . "\n";
      break;

    case 'after':
      $output .= ' ' . $description[$above] . $prefix . $element['#children'] . $suffix;
      $output .= ' ' . theme('form_element_label', $variables) . "\n";
      break;

    case 'none':
    case 'attribute':
      // Output no label and no required marker, only the children.
      $output .= ' ' . $description[$above] . $prefix . $element['#children'] . $suffix . "\n";
      break;
  }

  $output .= $description[!$above];
  $output .= "</div>\n";

  return $output;
}

function theme_ymeu_preprocess_webform_element(&$variables) {
  $element = $variables['element'];
  
  if($element['#id'] === 'edit-submitted-add-an-image-upload') {
     $variables['element']['#description'] = theme('file_upload_help', array('description' => $element['#description'], 'upload_validators' => $element['#upload_validators']));
  } 

  if($element['#type'] === 'password') {
    $variables['element']['#attributes']['placeholder'] = 'aaaa';
    $variables['element']['#webform_component']['extra']['placeholder'] = 'aaaa';
  }
}

function theme_ymeu_file_upload_help(array $variables) {
  $build = array();
  if (!empty($variables['description'])) {
    $build['description'] = array(
      '#markup' => $variables['description'] . '<br>',
    );
  }

  $descriptions = array();
  $upload_validators = $variables['upload_validators'];
  if (isset($upload_validators['file_validate_size'])) {
    $descriptions[] = t('Files must be less than !size.', array('!size' => '<strong>' . format_size($upload_validators['file_validate_size'][0]) . '</strong>'));
  }
  if (isset($upload_validators['file_validate_extensions'])) {
    $descriptions[] = t('Allowed file types: !extensions.', array('!extensions' => '<strong>' . check_plain($upload_validators['file_validate_extensions'][0]) . '</strong>'));
  }
  if (isset($upload_validators['file_validate_image_resolution'])) {
    $max = $upload_validators['file_validate_image_resolution'][0];
    $min = $upload_validators['file_validate_image_resolution'][1];
    if ($min && $max && $min == $max) {
      $descriptions[] = t('Images must be exactly !size pixels.', array('!size' => '<strong>' . $max . '</strong>'));
    }
    elseif ($min && $max) {
      $descriptions[] = t('Images must be between !min and !max pixels.', array('!min' => '<strong>' . $min . '</strong>', '!max' => '<strong>' . $max . '</strong>'));
    }
    elseif ($min) {
      $descriptions[] = t('Images must be larger than !min pixels.', array('!min' => '<strong>' . $min . '</strong>'));
    }
    elseif ($max) {
      $descriptions[] = t('Images must be smaller than !max pixels.', array('!max' => '<strong>' . $max . '</strong>'));
    }
  }

  if ($descriptions) {
    $id = drupal_html_id('upload-instructions');
    $img_requirements_str = t("Image requirements");
    $build['instructions'] = array(
      '#markup' => "<a data-bs-toggle=\"popover\" data-bs-html=\"true\" data-bs-placement=\"bottom\" title=\"{$img_requirements_str}\">{$img_requirements_str}</a>"
    );

    $build['requirements'] = array(
      '#theme_wrappers' => array('container__file_upload_requirements'),
      '#attributes' => array(
        'id' => $id,
        'class' => array('element-invisible', 'help-block'),
      ),
    );

    $build['requirements']['validators'] = array(
      '#theme' => 'item_list__file_upload_requirements',
      '#items' => $descriptions,
    );
  }

  return drupal_render($build);
}

function _theme_ymeu_webform_dragndrop_text_override($form, $form_state) {
  foreach($form['#node']->webform['components'] as $component) {
      if ($component['type'] == 'dragndrop') {
        $upload_text = variable_get('webform_dragndrop_upload_text', 'or drag and drop a file here');
        $upload_text_translated = t($upload_text);
        $remove_text_translated = t('Remove');

        $js_setting = array(
          'removeText' => $remove_text_translated,
          'dndText' => $upload_text_translated,
        );

        drupal_add_js($js_setting, 'setting');
        break;
      }
    }
    return $form;
}


function _is_campaign_page() {
  return (
      arg(0) == 'node' && is_numeric(arg(1)) && (int)arg(1) == (int)trim(theme_get_setting('campaign_webform_id'))
  );
}

function _check_campaign_submission_page() {
  if(_is_campaign_page() && arg(2) == 'submission' && is_numeric(arg(3))) {
      $nid = (int)arg(1);
      $sid = (int)arg(3);

      $node = node_load($nid);
      $submission = webform_get_submission($nid, $sid);

      return array('node' => $node, 'submission' => $submission);
  }

  return NULL;
}

function _is_webform_submission_ready_to_publish($node,$submission) {
  if(module_exists('drupout') && module_exists('webform_youmove')) {
      $settings = variable_get('webform_youmove__ymw_settings');
      $is_published_field_ID = $settings['published_ID'];
      $is_published_field_positive_value = $settings['published_positive_value'];
      $submission_data = drupout_params($node,$submission);

      return ($submission_data[$is_published_field_ID] == $is_published_field_positive_value);
  }
  return false;
}
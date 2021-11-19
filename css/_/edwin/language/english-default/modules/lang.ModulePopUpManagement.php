<?php
  /**
   * Lang: DE
   *
   * $LastChangedDate: 2017-10-06 10:02:09 +0200 (Fr, 06 Okt 2017) $
   * $LastChangedBy: jua $
   *
   * @package EDWIN Backend
   * @author Anton Jungwirth
   * @copyright (c) 2015 Q2E GmbH
   */

if (!isset($_LANG2["pu"])) $_LANG2["pu"] = array();

$_LANG = array_merge($_LANG,array(

  "m_mode_name_mod_popup" => "Administer pop-ups",
  'mod_pupopup_new_label'   => 'Create pop-up',
  'mod_pupopup_edit_label'  => 'Edit pop-up',

  'pu_moduleleft_newitem_label' => '+ New pop-up',
  'pu_site_label'               => '<b>Active web filter</b>:<br /><span class="fontsize11">Pop-ups to the Website <b>\'%s\'</b> are displayed...</span>',

  'pu_function_new_label'   => 'CREATE POP-UP',
  'pu_function_new_label2'  => '',
  'pu_function_edit_label'  => 'CHANGE POP-UP',
  'pu_function_edit_label2' => '',
  'pu_function_list_label'  => 'List of pop-ups',
  'pu_function_list_label2' => 'Created pop-ups',

  // Messages
  'pu_message_create_success' => 'Pop-up successfully created.',
  'pu_message_delete_success' => 'Pop-up successfully deleted.',
  'pu_message_no_items'       => 'There are no pop-ups available.',
  'pu_message_update_success' => 'Pop-up successfully edited.',
  'pu_message_activation_enabled'  => 'Pop-up successfully activated.',
  'pu_message_activation_disabled' => 'Pop-up successfully deactivated.',
  'pu_message_delete_image_success' => 'Image successfully deleted.',

  // Form
  'pu_image1_label'          => 'Image',
  'pu_image2_label'          => 'Image',
  'pu_image3_label'          => 'Image',
  'pu_text1_label'           => 'Text',
  'pu_text2_label'           => 'Text',
  'pu_text3_label'           => 'Text',
  'pu_title1_label'          => 'Title',
  'pu_title2_label'          => 'Title',
  'pu_title3_label'          => 'Title',
  'pu_url_label'             => 'External link',
  'pu_link_id_label'         => 'Internal link',
  'pu_link_scope_none_label'    => '',
  'pu_link_scope_local_label'   => 'The custom link refers to the current website.',
  'pu_link_scope_global_label'  => 'The link refers to the website \'%s\'.',

  // Option fields
  'pu_field_show_up_seconds_label'  => 'Seconds until the pop-up gets displayed',
  'pu_field_hidden_seconds_label'   => 'Time to reopen the pop-up',
  'pu_field_hidden_seconds_options' => array(
    0 => 0,
    86400 => "24h",
    604800 => "1 week",
    1209600 => "2 weeks",
    2592000 => "1 month",
    31104000 => "1 year",
  ),

"end",""));

$_LANG2["pu"] = array_merge($_LANG2["pu"], array(

  // List
  'pu_delete_label'          => 'Delete pop-up',
  'pu_delete_question_label' => 'Really delete pop-up?',
  'pu_edit_label'            => 'Edit pop-up',
  'pu_title_label'           => 'Title',

  // Form
  'pu_delete_image_label' => 'Delete image',
  'pu_delete_image_question_label' => 'Really delete image?',
  'pu_extlink_text'          => '(\'http://...\' - used only, if internal link was not specified)',
  'pu_link_label' => 'Target page',

  'pu_show_change_display_behaviour' => 'Pop-up Anzeige verwalten',

  "end",""));
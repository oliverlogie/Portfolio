<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

if (!isset($_LANG2['en'])) $_LANG2['en'] = array();

$_LANG = array_merge($_LANG, array(

  'modtop_ModuleEmployeeLocation' => 'Locations',

  // Main
  'en_function_edit_label'  => 'EDIT&nbsp;LOCATION',
  'en_function_edit_label2' => '',
  'en_function_new_label'   => 'CREATE&nbsp;LOCATION',
  'en_function_new_label2'  => '',
  'en_list_label'           => 'Location list',
  'en_list_label2'          => 'Manage locations of employees',
  'en_moduleleft_newitem_label' => '+ New location',

  // List
  'en_attribute_down_label' => 'Move location down',
  'en_attribute_move_label' => 'Move location',
  'en_attribute_up_label'   => 'Move location up',
  'en_box_label'            => 'Location',
  'en_content_label'        => 'Edit location',
  'en_delete_label'         => 'Delete location',
  'en_deleteitem_question_label' => 'Really delete location?',
  'en_message_no_attribute' => 'No locations available',
  'en_site_label'           => '<b>Active web filter</b>:<br /><span class="fontsize11">locations to the Website <b>\'%s\'</b> are displayed...</span>',

  // Messages
  'en_message_deleteitem_success' => 'Location successfully deleted',
  'en_message_edititem_success'   => 'Location successfully edited',
  'en_message_move_success'       => 'Location successfully moved',
  'en_message_newitem_success'    => 'Location successfully created',
  'en_message_no_attribute_types' => 'Module is not installed correctly', // Solution: Create attribute group in database

  // Form fields
  'en_text_label'  => 'Address',
  'en_title_label' => 'Title',

  'end',''));

$_LANG2['en'] = array_merge($_LANG2['en'], array(

  'end',''));
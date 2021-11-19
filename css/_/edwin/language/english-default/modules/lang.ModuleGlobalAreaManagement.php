<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2013 Q2E GmbH
 */

if (!isset($_LANG2['ga'])) $_LANG2['ga'] = array();

$_LANG = array_merge($_LANG,array(

  'm_mode_name_mod_globalareamgmt' => 'Manage global areas',
  'ga_site_label' => '<b>Active web filter</b>:<br /><span class="fontsize11">Areas to the Website <b>\'%s\'</b> are displayed...</span>',

  'ga_link_scope_none_label' => '',
  'ga_link_scope_local_label' => 'This link refers to the current webpage.',
  'ga_link_scope_global_label' => 'The link refers to the webpage \'%s\'.',
  'ga_area_label' => array(0 => array(0 => 'Area %s')), // SId => array(Position => ''); %s -> position of area
  'ga_area_box_link_scope_none_label' => '',
  'ga_area_box_link_scope_local_label' => 'This link refers to the current webpage.',
  'ga_area_box_link_scope_global_label' => 'The link refers to the webpage \'%s\'.',
  'ga_boxes_link' => '<a href=\'%s\' class=\'sn\'>%s</a>',
  'ga_special_page_link' => '<a href=\'%s\' class=\'sn\'>%s</a>',
  'ga_special_page_link_selected' => '%s',

  'ga_message_areas_not_available' => 'F&uuml;r diese Seite sind keine globalen Bereiche verf&uuml;gbar.',
  'ga_message_invalid_links' => '%d invalid link(s) due to deleted pages available',
  'ga_message_update_success' => 'Area has been saved.',
  'ga_message_deleteimage_success' => 'Image of the area has been deleted.',
  'ga_message_area_update_success' => 'Area has been updated.',
  'ga_message_area_move_success' => 'Area has been moved.',
  'ga_message_area_delete_success' => 'Area has been deleted.',
  'ga_message_area_deleteimage_success' => 'Image of the area has been deleted.',
  'ga_message_area_box_update_success' => 'Box has been saved.',
  'ga_message_area_box_move_success' => 'Box has been moved.',
  'ga_message_area_box_delete_success' => 'Box has been deleted.',
  'ga_message_area_box_deleteimage_success' => 'Image of the box has been deleted.',
  "ga_message_invalid_extlink" => "Could not save data. The external link you provided is invalid. Please provide a valid link or empty link field and try again.",
  'ga_area_message_activation_enabled'      => 'Area successfully activated!',
  'ga_area_message_activation_disabled'     => 'Area successfully deactivated!',
  'ga_area_box_message_timing_has_no_effect' => 'Activate the box item to use timing.',
  'ga_area_box_message_activation_enabled'  => 'Box successfully activated!',
  'ga_area_box_message_activation_disabled' => 'Box successfully deactivated!',

  'ga_image_title_label' => 'Image subtitle',
  'ga_image1_title_label' => '', // optional
  'ga_image2_title_label' => '', // optional
  'ga_image3_title_label' => '', // optional

  "ga_area_button_save_label" => "Save area %s",
  "ga_area_box_button_save_label" => "Save box %s",

  'end',''));

$_LANG2['ga'] = array_merge($_LANG2['ga'], array(

  'ga_list_label'  => 'Global areas',
  'ga_list_label2' => 'Available areas with boxes',

  // areas
  'ga_area_delete_label' => 'Delete area',
  'ga_area_delete_question_label' => 'Do you really want to delete the area?',
  'ga_area_showhide_label' => 'Show/hide area',
  'ga_area_link_label' => 'Link for the area (Where is the destination of the area?)',
  'ga_area_title_label' => 'Area title',
  'ga_area_text_label' => 'Area text',
  'ga_area_image_label' => 'Area-image',
  'ga_area_delete_image_label' => 'Delete image?',
  'ga_area_delete_image_question_label' => 'Do you really want to delete this image?',

  // boxes
  'ga_area_boxes_label' => 'Boxes of the area',
  'ga_area_box_label' => 'box',
  'ga_area_box_delete_label' => 'Delete box',
  'ga_area_box_delete_question_label' => 'Do you really want to delete the box??',
  'ga_area_box_showhide_label' => 'Show/hide box',
  'ga_area_box_move_up_label' => 'Move box upwards',
  'ga_area_box_move_down_label' => 'Move box donward',
  'ga_area_box_move_label' => 'Move box',
  'ga_area_box_position_locked_label' => 'box can not be moved',
  //'ga_area_box_general_label' => 'General area of the box',
  'ga_area_box_link_label' => 'Link for this box (Where is the destination of this box?)',
  'ga_area_box_title_label' => 'Box title',
  'ga_area_box_text_label' => 'Box alternative-text',
  'ga_area_box_image_label' => 'Box-Image',
  'ga_area_box_delete_image_label' => 'Delete image',
  'ga_area_box_delete_image_question_label' => 'Do you really want to delete this image?',
  'ga_area_box_noimage_label' => 'Not display image',

  // timing
  'ga_area_box_time_title_label' => 'Timing',
  'ga_area_box_time_edit_label' => 'Edit timing',
  'ga_area_box_date_label' => 'Date and time',
  'ga_area_box_date_label_from' => 'from',
  'ga_area_box_date_label_until' => 'to',
  'ga_area_box_datetime_delete_label' => 'Delete date',
  'ga_area_box_time_from_choose_label' => 'Choose a start time (H:i)',
  'ga_area_box_time_until_choose_label' => 'Choose a end time (H:i)',

  'end',''));
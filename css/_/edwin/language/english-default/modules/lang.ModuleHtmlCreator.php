<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2018-01-09 09:54:14 +0100 (Di, 09 Jän 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

if (!isset($_LANG2['hc'])) $_LANG2['hc'] = array();

$_LANG = array_merge($_LANG,array(

  'mod_htmlcreator_new_label'   => 'Create newsletter drafts',
  'mod_htmlcreator_edit_label'  => 'Edit newsletter drafts',
  'm_mode_name_mod_htmlcreator' => 'Manage newsletter drafts',
  'hc_moduleleft_newitem_label' => '+ New newsletter draft',
  'hc_moduleleft_export_html_label' => 'Export as HTML<br>',
  'hc_moduleleft_export_zip_label'  => 'Export as ZIP<br>',
  'hc_moduleleft_show_html_label'   => 'Show in browser<br>',

  // Main
  'hc_function_new_label'         => 'CREATE&nbsp;NEWSLETTER DRAFT',
  'hc_function_edit_label'        => 'EDIT&nbsp;NEWSLETTER&nbsp;DRAFT',
  'hc_function_list_label'        => 'List of newsletter drafts',
  'hc_copy_label'                 => ' copy',
  'hc_list_title_label_undefined' => '[%s] untitled',
  'hc_site_label'                 => "<b>Active Web filter</b>:<br /><span class=\"fontsize11\">Newsletters of the Website <b>'%s'</b> are shown...</span>",

  // Messages
  'hc_message_copy_success'         => 'Newsletter draft successfully copied.',
  'hc_message_create_success'       => 'Newsletter draft successfully created.',
  'hc_message_delete_success'       => 'Newsletter draft successfully deleted.',
  'hc_message_no_items'             => 'No newsletter drafts available.',
  'hc_message_update_success'       => 'Newsletter draft successfully edited.',
  'hc_message_max_elements'         => 'The maximum number of boxes has been reached.',
  'hc_message_delete_image_success' => 'The image was successfully deleted.',
  // Message Boxes
  'hc_box_message_create_success'                      => 'Box successfully created.',
  'hc_box_message_delete_success'                      => 'Box successfully deleted.',
  'hc_box_message_update_success'                      => 'Box successfully edited.',
  'hc_box_message_move_success'                        => 'Box successfully moved.',
  'hc_box_message_failure_create_box_missing_template' => 'Could not create a new box. Please choose a box type.',

  // Form
  'hc_image1_label'          => 'Image',
  'hc_image2_label'          => 'Image',
  'hc_image3_label'          => 'Image',
  'hc_text1_label'           => 'Text',
  'hc_text2_label'           => 'Text',
  'hc_text3_label'           => 'Text',
  'hc_title1_label'          => 'Title',
  'hc_title2_label'          => 'Title',
  'hc_title3_label'          => 'Title',

  'end',''));

$_LANG2['hc'] = array_merge($_LANG2['hc'], array(

  // List
  'hc_copy_label'            => 'Copy newsletter draft',
  'hc_delete_label'          => 'Delete newsletter draft',
  'hc_delete_question_label' => 'Really delete newsletter draft?',
  'hc_edit_label'            => 'Edit newsletter draft',
  'hc_html_creator_label'    => 'Newsletter draft',
  'hc_title_label'           => 'Title',
  'hc_template_label'        => 'Draft type',

  // Form
  'hc_delete_image_label' => 'Delete image',
  'hc_delete_image_question_label' => 'Really delete image?',
  'hc_box_available_info_label' => '(Save the newsletter draft to manage boxes)',

  // Boxes
  'hc_button_new_element_label' => 'New box',
  'hc_boxes_label' => 'Boxes',
  'hc_box_label' => 'Box',
  'hc_box_showhide_label' => 'Show/Hide box',
  'hc_box_title_label' => 'Title',
  'hc_box_text_label' => 'Box-Text',
  'hc_box_image_label' => 'Box-Image',
  'hc_box_delete_image_label' => 'Delete image',
  'hc_box_delete_image_question_label' => 'Really delete image?',
  'hc_box_move_up_label' => 'Move box upwards',
  'hc_box_move_down_label' => 'Move box downwards',
  'hc_box_move_label' => 'Move box',
  'hc_box_delete_label' => 'Delete box',
  'hc_box_delete_question_label' => 'Really delete box?',
  'hc_box_actions_label' => 'Box actions',
  'hc_box_submit_label' => 'save',

  // HTML/Newsletter templates
  'hc_template_title' => '',

  'end',''));

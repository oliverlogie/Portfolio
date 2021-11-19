<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['sb'])) $_LANG2['sb'] = array();

$_LANG = array_merge($_LANG,array(

  "mod_sidebox_new_label"       => "Create&nbsp;sidebox",
  "mod_sidebox_edit_label"      => "Edit&nbsp;sidebox",
  "m_mode_name_mod_sidebox"     => "Create/administer sidebox",
  "sb_moduleleft_newitem_label" => "+ New sidebox",
  "sb_extlink_link"             => " <a href=\"%s\" target=\"_blank\"><small>%s</small></a>",
  "sb_intlink_link"             => " (<a href=\"%s\" ><small>%s</small></a>)",
  "sb_link_scope_none_label"    => "",
  "sb_link_scope_local_label"   => "The link refers to the current website.",
  "sb_link_scope_global_label"  => "Der Link refers to the website '%s'.",

  // main
  "sb_function_label" => "Administer sideboxes",
  "sb_function_new_label" => "CREATE&nbsp;SIDEBOX",
  "sb_function_new_label2" => "Enter data of the new sidebox",
  "sb_function_edit_label" => "EDIT&nbsp;SIDEBOX",
  "sb_function_edit_label2" => "",
  "sb_function_list_label" => "List of sideboxes",
  "sb_function_list_label2" => "Created sideboxes",

  // list
  "sb_site_label" => "<b>Active web filter </b>:<br /><span class=\"fontsize11\">sideboxes of the website <b>'%s'</b> are displayed...</span>",
  // messages
  "sb_message_no_sidebox" => "No sideboxes defined",
  "sb_message_create_success" => "Sidebox has been created",
  "sb_message_update_success" => "Sidebox has been edited",
  "sb_message_move_success" => "Sidebox has been moved",
  "sb_message_delete_success" => "Sidebox has been deleted",
  "sb_message_insufficient_input" => "At least specify a title!",
  "sb_message_invalid_url_protocol" => "Invalid protocol for external link! Possible protocols: %s",

  "end",""));

$_LANG2['sb'] = array_merge($_LANG2['sb'], array(

  // list
  "sb_box_label" => "Sidebox",
  "sb_delete_label" => "Delete sidebox",
  "sb_delete_question_label" => "Do you really want to delete this sidebox?",
  "sb_move_up_label" => "Move this sidebox upwards",
  "sb_move_down_label" => "Move this sidebox downwards",
  "sb_move_label" => "Move this sidebox",
  "sb_content_label" => "Edit sidebox",
  "sb_list_assignment_label" => "Box display settings",
  "sb_list_link_label" => "Box link",
  "sb_list_title1_label" => "Title",

  // form
  "sb_title1_label" => "Title of the box",
  "sb_title2_label" => "META-information of the box (only visible to search engines!)",
  "sb_title3_label" => "Title",
  "sb_text1_label" => "Text in the box",
  "sb_text2_label" => "Text",
  "sb_text3_label" => "Text",
  "sb_image1_label" => "Image of the box - small",
  "sb_image2_label" => "Image of the box - entire surface",
  "sb_image3_label" => "Image",
  "sb_intlink_label" => "Internal link",
  "sb_extlink_label" => "External link",
  "sb_extlink_text"  => "(\"http://...\" - used only, if internal link was not specified)",
  "sb_properties_label" => "Box display settings",
  "sb_norandom_label" => "Display box randomly. <small>(The sidebox is displayed randomly on pages it has not been assigned to explicitly.)</small>",
  "sb_image_alt_label" => "Image of the sidebox",
  "sb_links_label" => "Link for the box (Where is the destination of the box?)",

  'sb_show_change_display_behaviour' => 'Administer sidebox display settings',

  "end",""));


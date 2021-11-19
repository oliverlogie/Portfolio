<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2011-05-24 13:51:00 +0200 (Di, 24 Mai 2011) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2["dt"])) $_LANG2["dt"] = array();

$_LANG = array_merge($_LANG, array(

  "m_mode_name_mod_downloadticker" => "Downloadticker - administer top-downloads",

// main
  "dt_function_label" => "Administer top-downloads",
  "dt_function_list_label" => "List of top-downloads",
  "dt_function_list_label2" => "Created top-downloads",

// list
  "dt_site_label" => "<b>Active Web filter</b>:<br /><span class=\"fontsize11\">Central downloads to the Website <b>'%s'</b> are displayed...</span>",
// messages
  "dt_message_no_topdownload" => "No top-downloads defined.",
  "dt_message_newitem_success" => "Top download has been created..",
  "dt_message_edititem_success" => "Top download has been saved.",
  "dt_message_deleteitem_success" => "Top download has been deleted.",
  "dt_message_move_success" => "Top download has been moved",
  "dt_message_insufficient_input" => "Please specify a download and a link.",
  "dt_message_invalid_links" => "%d invalid link (s) due to deleted pages and downloads available",
  "dt_button_new_label" => "Create",
  "dt_button_edit_label" => "Edit",

  "end"));

$_LANG2["dt"] = array_merge($_LANG2["dt"], array(

  "dt_create_label" => "Create new top-download",
  "dt_edit_label" => "Edit top-download",
  "dt_existing_label" => "Existing top-downloads",
  "dt_download_label" => "Download",
  "dt_link_label" => "Link to target page.",
  "dt_move_up_label" => "Move the top-download upwards",
  "dt_move_down_label" => "Move the top-download downwards",
  "dt_move_label" => "Move the top-download",
  "dt_delete_label" => "Delete top-download",
  "dt_delete_question_label" => "Do you really want to delete this top-download?",
  "dt_button_create_label" => "Create",
  "dt_button_edit_label" => "Edit",
  "dt_button_cancel_label" => "Cancel",
  "dt_message_maximum_reached" => "You can not create further top downloads since the maximum number of <br /> top downloads has been reached.",
  "dt_download_title_label" => "Specific title/text of the download",
  "dt_link_title_label" => "Specific text for the link to the target page.",

  "end",""));

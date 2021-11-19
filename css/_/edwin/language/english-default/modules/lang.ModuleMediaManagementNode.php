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

if (!isset($_LANG2["mn"])) $_LANG2["mn"] = array();

$_LANG = array_merge($_LANG, array(

  "modtop_ModuleMediaManagementNode" => "Decentral download",
  "m_mode_name_mod_mediamanagementnode" => "Media database - administer decentral downloads",

// main
  "mn_function_label" => "Administer decentral downloads",
  "mn_function_list_label" => "List of decentral downloads",
  "mn_function_list_label2" => "Existing decentral downloads",

// list
  "mn_site_label" => "Decentral downloads to website <b>'%s'</b> are displayed...",

  "mn_filter_active_label" => "%s contains <b title=\"%s\">'%s'</b>...",
  "mn_filter_inactive_label" => "No active data filter",
  "mn_filter_type_title" => "Title",
  "mn_filter_type_filename" => "File name",
  "mn_filter_type_identifier" => "Path",

// form

// messages
  "mn_message_no_decentralfile" => "No decentral downloads defined.",
  "mn_message_deleteitem_success" => "Decentral download has been deleted.",
  "mn_message_convertitem_success" => "Decentral download has been converted in central download.",

  "mn_filtermessage_empty" => "For the specified filter no decentral downloads were found.",

  "end"));

  $_LANG2["mn"] = array_merge($_LANG2["mn"], array(

  // list
  "mn_list_title_label" => "Title",
  "mn_list_filename_label" => "File name",
  "mn_list_date_label" => "Date",
  "mn_delete_label" => "Delete decentral download",
  "mn_deleteitem_question_label" => "Do you really want to delete this decentral download?",
  "mn_convert_label" => "Convert decentral download in central download",
  "mn_contentitem_link_info" => "Download administration of the content page",
  "mn_convertitem_question_label" => "Do you really want to convert decentral download in central download?",

  "mn_filter_label" => "Apply filter",
  "mn_filter_reset_label" => "Reset filter",
  "mn_show_change_filter_label" => "Edit filter",
  "mn_filter_text1" => "To",
  "mn_filter_text2" => "Contains",
  "mn_button_filter_label" => "Filter",

  "end",""));

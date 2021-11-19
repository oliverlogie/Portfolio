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

if (!isset($_LANG2["ml"])) $_LANG2["ml"] = array();

$_LANG = array_merge($_LANG, array(

  "modtop_ModuleMediaManagementAll" => "All downloads",
  "m_mode_name_mod_mediamanagementAll" => "Media database - browse all downloads",

// main
  "ml_function_label" => "Browse all downloads",
  "ml_function_list_label" => "List of all downloads",
  "ml_function_list_label2" => "All downloads (central, decentral and download area-files)",

  "ml_download_details" => "To content page of the download",
  "ml_ci_title_label" => "Content page title",

// filter
  "ml_site_label" => "Downloads to website <b>'%s'</b> are displayed...",
  "ml_filter_active_label" => "%s contains <b>'%s'</b>...",
  "ml_filter_inactive_label" => "All data is displayed",
  "ml_filter_active_content" => "%s contains <b>'%s'</b>...",
  "ml_filter_type_title" => "Title",
  "ml_filter_type_filename" => "File name",
  "ml_filter_type_identifier" => "Path",

// sort
  "ml_sort_link_info" => "up and down",

// icons
  "ml_centralfile_local_label" => "The file is from the current web page.",
  "ml_show_usage_label" => "Show use of the download",

// messages
  "ml_message_no_files" => "No downloads defined.",
  "ml_message_deleteitem_success" => "Download has been deleted.",
  "ml_message_convertitem_success" => "Download has been converted in central download.",
  "ml_filtermessage_empty" => "For the given filter no downloads were found",

  "ml_convert_label" => "Convert download in central download",

  "end"));

  $_LANG2["ml"] = array_merge($_LANG2["ml"], array(

  // list
  "ml_list_title_label" => "Title",
  "ml_list_filename_label" => "File name",
  "ml_list_date_label" => "Date",
  "ml_delete_label" => "Delete download",
  "ml_deleteitem_question_label" => "Do you really want to delete this download?",
  "ml_contentitem_link_info" => "Download administration of the content page",
  "ml_convertitem_question_label" => "Do you really want to convert the download in a central download?",

  "ml_filter_label" => "Filter",
  "ml_filter_reset_label" => "Reset filter",
  "ml_show_change_filter_label" => "Change filter",
  "ml_filter_text1" => "To",
  "ml_filter_text2" => "Contains",
  "ml_button_filter_label" => "Filter",

  "end",""));

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

if (!isset($_LANG2["dl"])) $_LANG2["dl"] = array();

$_LANG = array_merge($_LANG,array(

  "dl_button_save_label" => "Save main area",
  "dl_image_label" => "Main image",

  "dl_area_file_scope_local_label" => "The file derives from the current web page.",
  "dl_area_file_scope_global_label" => "The file derives from the web page '%s'.",

  "dl_message_invalid_links" => "%d invalid link(s) exist(s), due to deleted pages and downloads.",
  "dl_message_multiple_link_failure" => "Link for this download already exists!",

  "dl_message_area_create_success" => "Area has been created.",
  "dl_message_area_update_success" => "Area has been saved.",
  "dl_message_area_move_success" => "Area has been moved.",
  "dl_message_area_delete_success" => "Area has been deleted.",
  "dl_message_area_deleteimage_success" => "Image of the area has been deleted.",
  "dl_message_area_insufficient_input" => "Specify the title for the area!",
  "dl_message_area_max_elements" => "The maximum number of areas has been reached.",

  'dl_message_area_file_convert_success' => "Download successfully converted into central download.",
  "dl_message_area_file_create_success" => "Download has been created.",
  "dl_message_area_file_update_success" => "Download has been saved.",
  "dl_message_area_file_move_success" => "Download has been moved.",
  "dl_message_area_file_delete_success" => "Download has been deleted.",
  "dl_message_area_file_insufficient_input" => "Specify file and title for download!",

  "dl_area_message_activation_enabled"  => "Area successfully activated!",
  "dl_area_message_activation_disabled" => "Area successfully deactivated!",

  'dl_area_file_status_normal_label' => 'normal',
  'dl_area_file_status_new_label' => 'new',
  'dl_area_file_status_updated_label' => 'updated',

  "end",""));

$_LANG2["dl"] = array_merge($_LANG2["dl"], array(

  "dl_showhide_label" => "Show/hide general layout area",
  "dl_layoutarea1_label" => "Main area",
  "dl_button_submit_label" => "Save main area",
  "dl_button_new_element_label" => "New area",

  // Areas
  "dl_areas_title" => "Areas",
  "dl_area_label" => "Area",
  "dl_area_showhide_label" => "Show/hide area",
  "dl_area_general_label" => "General area of the area",
  "dl_area_title_label" => "Title of the area",
  "dl_area_text_label" => "Text of the area",
  "dl_area_image_label" => "Image of the area",
  "dl_area_delete_image_label" => "Delete image",
  "dl_area_delete_image_question_label" => "Do you really want to delete the image?",
  "dl_area_image_title_label" => "Image subtitle",
  "dl_area_move_up_label" => "Move area upwards",
  "dl_area_move_down_label" => "Move area downwards",
  "dl_area_move_label" => "Move area",
  "dl_area_delete_label" => "Delete area",
  "dl_area_delete_question_label" => "Do you really want to delete the area?",
  "dl_button_area_submit_label" => "save general area of the area",

  // Files
  "dl_area_files_label" => "Downloads",
  'dl_area_file_file_convert_label' => 'Converting the download in central download',
  "dl_area_file_create_label" => "Create new download",
  "dl_area_file_edit_label" => "Edit download",
  "dl_area_files_existing_label" => "Existing downloads",
  "dl_area_file_title_label" => "Title",
  "dl_area_file_file_label" => "File",
  "dl_area_file_filename_label" => "File name",
  "dl_area_file_date_label" => "Date",
  "dl_area_file_kind_existingupload" => "Existing file",
  "dl_area_file_kind_newupload" => "Upload new file",
  "dl_area_file_kind_centralfile" => "Search for the existing central file",
  "dl_area_file_move_up_label" => "Move download upwards",
  "dl_area_file_move_down_label" => "Move download downwards",
  "dl_area_file_move_label" => "Move download",
  "dl_area_file_delete_label" => "Delete download",
  "dl_area_file_delete_question_label" => "Do you really want to delete this download?",
  'dl_area_file_convert_question_label' => 'Do you really want to convert this download in a central download?',
  "dl_button_area_file_create_label" => "Create new download",
  "dl_button_area_file_edit_label" => "Edit download",
  "dl_button_area_file_cancel_label" => "Cancel",
  "dl_message_area_file_maximum_reached" => "You can't create further downloads, since the maximum number <br /> of downloads was achieved.",

  "end",""));

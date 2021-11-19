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

if (!isset($_LANG2["mm"])) $_LANG2["mm"] = array();

$_LANG = array_merge($_LANG, array(

  "mod_mediamanagement_main_new_label" => "Create central&nbsp;download",
  "mod_mediamanagement_main_edit_label" => "Edit central&nbsp;download",
  "m_mode_name_mod_mediamanagement" => "Media database - administer central downloads",
  "modtop_ModuleMediaManagement" => "Central downloads",
  "mm_moduleleft_newitem_label" => "+ New central<br />download",

  "mm_relation_scope_local_label" => "Link to the current Web page.",
  "mm_relation_scope_global_label" => "The link is to the website '%s'.",
  "mm_area_relation_scope_local_label" => "Link to the current Web page.",
  "mm_area_relation_scope_global_label" => "The link is to the website '%s'.",

// main
  "mm_function_label" => "Administer central downloads",
  "mm_function_new_label" => "CREATE CENTRAL&nbsp;DOWNLOAD&nbsp;",
  "mm_function_new_label2" => "Enter data of the new central download",
  "mm_function_edit_label" => "EDIT CENTRAL&nbsp;DOWNLOAD&nbsp;",
  "mm_function_edit_label2" => "",
  "mm_function_list_label" => "List of central downloads",
  "mm_function_list_label2" => "Created central downloads",

// form
  "mm_box_new_label" => "Download",
  "mm_box_edit_label" => "Existing download",

// list
  "mm_site_label" => "<b>Active web filter</b>:<br /><span class=\"fontsize11\">central downloads of the website <b>'%s'</b> are displayed...</span>",

// messages
  "mm_message_newitem_success" => "Central download has been created.",
  "mm_message_edititem_success" => "Central download has been saved.",
  "mm_message_deleteitem_success" => "Central download has been deleted.",
  "mm_message_newrelation_success" => "Link to page has been created.",
  "mm_message_deleterelation_success" => "Link to page has been deleted.",
  "mm_message_deletearearelation_success" => "Link to DL-section was deleted.",
  "mm_message_newrelation_no_page" => "Please specify the site!",
  "mm_message_insufficient_input" => "Please specify the title and the file!",
  "mm_message_success_issuu_document_delete" => "The document was successfully deleted.",
  "mm_message_failure_issuu_document_delete" => "Could not delete the document. Please try again or contact the administrator.",

  "mm_CFTitle_label"    => "Title",
  "mm_CFFile_label"     => "Filename",
  "mm_CFCreated_label"  => "Created at",
  "mm_CFModified_label" => "Changed at",

  "end"));

  $_LANG2["mm"] = array_merge($_LANG2["mm"], array(

  // list
  "mm_content_label" => "Edit central download",
  "mm_delete_label" => "Delete central download",
  "mm_list_relations_count_label" => "Number of links to content pages.",
  "mm_deleteitem_question_label" => "Do you really want to delete this central download?",

  // form
  "mm_title_label" => "Title",
  "mm_filename_label" => "File",
  "mm_created_label" => "Creation date",
  "mm_modified_label" => "Modification date",
  "mm_size_label" => "Size",
  "mm_file_label" => "Upload new file",
  "mm_show_always_label" => "The file can only be downloaded by visitors, if it is linked within a content item or module.",
  "mm_protected_label" => "Protect download. Only users providing their email might download this file.",

  "mm_newrelation_label" => "Add link to page",
  "mm_newrelation_title_label" => "Individual file name",
  "mm_newrelation_page_label" => "Page",
  "mm_newrelation_area_label" => "Download area",
  "mm_newrelation_noarea" => "Directly to the page",
  "mm_newrelation_submit_label" => "Add link to page",

  "mm_relations_label" => "Existing links to content pages",
  "mm_no_relations" => "No link to pages created.",
  "mm_relation_contentitem_title_label" => "Content page",
  "mm_relation_title_label" => "Title of the download",
  "mm_relation_delete_label" => "Delete the link to the page",
  "mm_relation_delete_question_label" => "Do you really want to delete the link to the page?",
  "mm_relation_contentitem_link_label" => "Edit the link to the page",

  "mm_area_relations_label" => "Existing links to DL-areas",
  "mm_no_area_relations" => "No links to DL-areas created.",
  "mm_area_relation_contentitem_title_label" => "Content page",
  "mm_area_relation_area_title_label" => "Download area",
  "mm_area_relation_title_label" => "Title of the download",
  "mm_area_relation_delete_label" => "Delete link to the download-area",
  "mm_area_relation_delete_question_label" => "Do you really want to delete the link to the download-area?",
  "mm_area_relation_contentitem_link_label" => "Edit link to the download-area",

  "mm_words_filelink_relations_label" => "Existing links to content pages with download links in text areas",
  "mm_no_words_filelink_relations" => "No link in running text created.",
  "mm_words_filelink_relation_contentitem_title_label" => "Content page with download link in text area",
  "mm_words_filelink_relation_title_label" => "Title of the download",
  "mm_words_filelink_relation_contentitem_link_label" => "Edit the link to the page",

  "mm_upload_on_issuu_headline" => "Document",
  "mm_upload_on_issuu_upload_description" => "The central download can be used as document cataloque, if you create a document by clicking on <i>Create now</i>.",
  "mm_upload_on_issuu_upload_btn_label" => "Create now",
  "mm_upload_on_issuu_reload_btn_label" => "Check",
  'mm_issuu_document_convert_error'  => 'An error occured during the document conversion.',
  'mm_issuu_document_convert'        => 'Document converting... please be paitient and wait several minutes. Click on <i>reload</i> in case the message does not change or upload a new central download file.',
  'mm_issuu_document_current'        => 'Link to document',
  'mm_issuu_document_delete_label'   => 'Delete this document.',
  'mm_issuu_document_delete_question_label' => 'Really delete this document?',
  'mm_issuu_document_info'           => 'Document is limited to 500 pages and 100 MB. Allowed file extension: .PDF, .ODT, .DOC, .WPD, .SXW, .SXI, .RTF, .ODP, .PPT',

  "end",""));

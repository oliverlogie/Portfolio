<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['ms'])) $_LANG2['ms'] = array();

$_LANG = array_merge($_LANG,array(

  'mod_multimedia box_new_label'       => 'Create&nbsp;multimedia box',
  'mod_multimedia box_edit_label'      => 'Edit&nbsp;multimedia box',
  'modtop_ModuleMultimediaLibrary'   => 'Multimedia boxes',
  'm_mode_name_mod_medialibrary'     => 'Create/administer multimedia box',
  'ms_moduleleft_newitem_label' => '+ New multimedia box',
  'ms_extlink_link'             => ' <a href=\'%s\' target=\'_blank\'>%s</a>',
  'ms_intlink_link'             => ' (<small><a href=\'%s\'>%s</a></small>)',
  'ms_link_scope_none_label'    => '',
  'ms_link_scope_local_label'   => 'The link refers to the current website.',
  'ms_link_scope_global_label'  => 'Der Link refers to the website \'%s\'.',

  // main
  'ms_function_label' => 'Administer multimedia boxes',
  'ms_function_new_label' => 'CREATE&nbsp;multimedia box',
  'ms_function_new_label2' => 'Enter data of the new multimedia box',
  'ms_function_edit_label' => 'EDIT&nbsp;multimedia box',
  'ms_function_edit_label2' => '',
  'ms_function_list_label' => 'List of multimedia boxes',
  'ms_function_list_label2' => 'Created multimedia boxes',

  // list
  'ms_site_label' => 'Multimedia boxes of the website <b>\'%s\'</b> are displayed...',
  // filter
  "ms_filter_active_label"   => "<b>Active data filter</b>: <span class=\"fontsize11\">%s contains <b>'%s'</b>...</span>",
  "ms_filter_inactive_label" => "<i>No active data filter</i>",
  "ms_filter_active_content" => "<span class=\"fontsize11\">%s contains <b>'%s'</b>.. </span>",
  "ms_filter_type_title"     => "Title",
  "ms_filtermessage_empty"   => "No multimedia boxes found for the provided filterwords.",

  // messages
  'ms_message_no_multimedia box' => 'No multimedia boxes for selected category defined',
  'ms_message_create_success' => 'Multimedia box has been created',
  "ms_message_create_failure" => "Could not create new Multimedia box. Please specify title, text, image, document or video.",
  'ms_message_update_success' => 'Multimedia box has been edited',
  "ms_message_update_failure" => "Could not update Multimedia box. Please specify title, text, image, document or video.",
  'ms_message_move_success' => 'Multimedia box has been moved',
  'ms_message_delete_item_success' => 'Multimedia box has been deleted',
  'ms_message_delete_image_success' => 'Image has been deleted',
  'ms_message_insufficient_input' => 'At least specify a title!',
  'ms_message_invalid_url_protocol' => 'Invalid protocol for external link! Possible protocols: %s',
  'ms_message_no_medialibrary_categories' => 'No categories for this site defined',
  'ms_message_invalid_video_data' => 'The entered video data couldn\'t be processed',
  'ms_message_no_video_data' => 'Video type choosen, but no video link has been entered!',
  'ms_message_no_video_type' => 'Video link entered, but no video type has been choosen!',
  'ms_message_document_delete_success' => 'Catalogue successfully deleted.',
  'ms_message_document_delete_error'    => 'Could not delete the catalogue.',

  // video types (do NOT use HTML entities, because this is mainly used for JavaScript)
  'ms_video_type_none_label' => 'No video type',
  'ms_video_type_none_data_label' => 'Paste the link here.',
  'ms_video_type_none_data_descr' => ' ',
  'ms_video_type_youtube_label' => 'YouTube Video',
  'ms_video_type_youtube_data_label' => 'Paste the YouTube video link here.',
  'ms_video_type_youtube_data_descr' => 'The URL to your video can be found on YouTube.com in the right area of the video, the URL field. Copy this URL into the text box below and press save then ...',
  'ms_video_type_vimeo_label' => 'Vimeo Video',
  'ms_video_type_vimeo_data_label' => 'Paste the vimeo video link here',
  'ms_video_type_vimeo_data_descr' => 'The URL to your video can be found on vimeo.com in the address line (URL) of your browser. Copy this URL into the text box below and press save then ...',
  'ms_video_type_myspace_label' => 'MySpace Video',
  'ms_video_type_myspace_data_label' => 'Paste the MySpace video link here.',
  'ms_video_type_myspace_data_descr' => 'The URL to your video, can be found on http://www.myspace.com/video/ in the address line (URL) of your browser. Copy this URL into the text box below and press save then ...',
  'ms_video_type_myvideo_label' => 'MyVideo Video',
  'ms_video_type_myvideo_data_label' => 'Paste the MyVideo video link here',
  'ms_video_type_myvideo_data_descr' => 'The URL to your video, can be found on myvideo.at in the address line (URL) of your browser. Copy this URL into the text box below and press save then ...',

  'end',''));

$_LANG2['ms'] = array_merge($_LANG2['ms'], array(

  // list
  'ms_box_label' => 'Multimedia box',
  'ms_delete_label' => 'Delete multimedia box',
  'ms_delete_question_label' => 'Do you really want to delete this multimedia box?',
  'ms_move_up_label' => 'Move this multimedia box upwards',
  'ms_move_down_label' => 'Move this multimedia box downwards',
  'ms_move_label' => 'Move this multimedia box',
  'ms_content_label' => 'Edit multimedia box',
  'ms_choose_category_label' => 'Choose category',
  'ms_category_show_all' => 'Show all',

  // form
  'ms_link_label' => 'Link',
  'ms_links_label' => 'Link for the box (Where is the destination of the box?)',
  'ms_intlink_label' => 'Internal link',
  'ms_extlink_label' => 'External link',
  'ms_extlink_text'  => '(\'http://...\' - used only, if internal link was not specified)',
  "ms_properties_label" => "Box display settings",
  "ms_norandom_label" => "Display box randomly. <small>(The sidebox is displayed randomly on pages it has not been assigned to explicitly.)</small>",
  'ms_image_alt_label' => 'Image of the multimedia box',
  'ms_select_categories_label' => 'Category assignment for this multimedia box',

  // video area
  'ms_title_label' => 'Title',
  'ms_text_label' => 'Text',
  'ms_image_label' => 'Image',
  'ms_video_label' => 'Video',
  'ms_title1_label' => 'Title',
  'ms_title2_label' => 'Title',
  'ms_title3_label' => 'Title',
  'ms_text1_label' => 'Text',
  'ms_text2_label' => 'Text',
  'ms_text3_label' => 'Text',
  'ms_image1_label' => 'Image',
  'ms_image2_label' => 'Image',
  'ms_image3_label' => 'Image',
  'ms_image4_label' => 'Image',
  'ms_image5_label' => 'Image',
  'ms_image6_label' => 'Image',
  'ms_video_usage_descr' => 'Enter the platform, from which you want to integrate a video on your website.',
  'ms_video_id_label' => 'Filtered video ID',
  'ms_video_link_label' => 'Here\'s the original video',
  'ms_video_duration_label' => 'Video duration',
  'ms_delete_video_label' => 'Delete video',
  'ms_delete_video_question_label' => 'Do you really want to delete this video?',
  'ms_boximage_data_label' => 'Box-image',
  'ms_layoutarea1_label' => 'Video-layout area 1',
  'ms_layoutarea1_description' => '(area 1 - Block with title, video or image and text )',
  'ms_layoutarea2_label' => 'Video-Layout area 2',
  'ms_layoutarea2_description' => '(area 2 - Block with title, video or image and text )',
  'ms_layoutarea3_label' => 'Video-Layout area 3',
  'ms_layoutarea3_description' => '(area 3 - Block with title, video or image and text )',
  'ms_video1_label' => 'Video',
  'ms_video2_label' => 'Video',
  'ms_video3_label' => 'Video',
  'ms_showhide_label' => 'Show/hide area',
  'ms_area_showhide_label' => 'Show/hide area',
  'ms_area_actions_label' => 'Actions layout area',
  'ms_button_area_save_label' => 'Save',
  'ms_image1_title_label' => 'Video-/Image subtitle',
  'ms_image2_title_label' => 'Image subtitle',
  'ms_image3_title_label' => 'Image subtitle',
  'ms_delete_image_label'          => 'Delete image',
  'ms_delete_image_question_label' => 'Do you really want to delete this image?',

  // ISSUU document upload
  'ms_issuu_document_convert_error'  => 'Error during converting the document.',
  'ms_issuu_document_convert'        => 'Document converting...',
  'ms_issuu_document_current'        => 'Current online catalogue',
  'ms_issuu_document_delete_label'   => 'Delete this catalogue',
  'ms_issuu_document_delete_question_label' => 'Really delete this catalogue?',
  'ms_issuu_document_info'           => 'Document is limited to 500 pages and 100 MB. Allowed file extension: .PDF, .ODT, .DOC, .WPD, .SXW, .SXI, .RTF, .ODP, .PPT',
  'ms_issuu_document_desc'          => 'Upload document and convert it as an online catalogue',
  'ms_issuu_document_label'         => 'Online catalogue',

  // filter
  "ms_filter_label"             => "Apply filter",
  "ms_filter_reset_label"       => "Reset filter",
  "ms_show_change_filter_label" => "Change filter",
  "ms_filter_text1"             => "To",
  "ms_filter_text2"             => "Contains",
  "ms_button_filter_label"      => "Filter",

  'end',''));


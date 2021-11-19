<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2018-03-27 17:03:14 +0200 (Di, 27 Mär 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['si'])) $_LANG2['si'] = array();

$_LANG = array_merge($_LANG, array(

  "m_mode_name_mod_siteindex" => "Edit welcome page",
  "modtop_ModuleSiteindex" => "Standard",

  "si_button_submit_label" => "Save",

  "si_link_scope_none_label" => "",
  "si_link_scope_local_label" => "This link refers to the current webpage.",
  "si_link_scope_global_label" => "The link refers to the webpage '%s'.",
  "si_area_button_save_label" => "Save welcome page-area %s",
  "si_area_label" => array(0 => "Welcome page-area"),
  "si_area_box_button_save_label" => "Save welcome page-box %s",
  "si_area_box_link_scope_none_label" => "",
  "si_area_box_link_scope_local_label" => "This link refers to the current webpage.",
  "si_area_box_link_scope_global_label" => "The link refers to the webpage '%s'.",

  "si_boxes_link" => "<a href=\"%s\" class=\"sn\">%s</a>",
  "si_special_page_link" => "<a href=\"%s\" class=\"sn\">%s</a>",
  "si_special_page_link_selected" => "%s",

  "si_message_invalid_links" => "%d invalid link(s) due to deleted pages available",

  "si_message_update_success" => "Welcome page has been saved.",
  "si_message_deleteimage_success" => "Image of the welcome page has been deleted.",
  "si_message_invalid_extlink" => "Could not save data. The external link you provided is invalid. Please provide a valid link or empty link field and try again.",

  "si_message_area_update_success" => "Welcome page has been updated.",
  "si_message_area_move_success" => "Welcome page has been moved.",
  "si_message_area_delete_success" => "Welcome page has been deleted.",
  "si_message_area_deleteimage_success" => "Image of the welcome page has been deleted.",

  "si_message_area_box_update_success" => "Welcome page-box has been saved.",
  "si_message_area_box_move_success" => "Welcome page-box has been moved.",
  "si_message_area_box_delete_success" => "Welcome page-box has been deleted.",
  "si_message_area_box_deleteimage_success" => "Image of the welcome page-box has been deleted.",

  "si_area_message_activation_enabled"      => "Area successfully activated!",
  "si_area_message_activation_disabled"     => "Area successfully deactivated!",
  "si_area_box_message_activation_enabled"  => "Box successfully activated!",
  "si_area_box_message_activation_disabled" => "Box successfully deactivated!",

  "si_image_title_label" => "Image subtitle",
  "si_image1_title_label" => "", // optional
  "si_image2_title_label" => "", // optional
  "si_image3_title_label" => "", // optional

  "end",""));

$_LANG2['si'] = array_merge($_LANG2['si'], array(

  // main
  "si_siteindex_label" => "Welcome page",
  "si_siteindex_label2" => "Edit content of the welcome page",

  // common
  "si_link_label" => "Link for the box (Where is the destination of the box?)",
  "si_extlink_label" => "External link <small>(\"http://...\" - used only, if internal link was not specified)</small>",
  "si_title_label" => "Title",
  "si_text1_label" => "Text 1",
  "si_text2_label" => "Text 2",
  "si_text3_label" => "Text 3",
  "si_image1_label" => "Image 1",
  "si_image2_label" => "Image 2",
  "si_image3_label" => "Image 3",
  "si_delete_image_label" => "Delete image",
  "si_delete_image_question_label" => "Do you really want to delete this image?",

  "si_button_submit_label" => "Save",

  // areas
  "si_area_delete_label" => "Delete welcome page-area",
  "si_area_delete_question_label" => "Do you really want to delete the welcome page-area?",
  "si_area_showhide_label" => "Show/hide welcome page-area",

  "si_area_link_label" => "Link for the area (Where is the destination of the area?)",
  "si_area_extlink_label" => "External link <small>(\"http://...\" - used only, if internal link was not specified)</small>",
  "si_area_title_label" => "Area title",
  "si_area_text_label" => "Area text",
  "si_area_image_label" => "Area-image",
  "si_area_delete_image_label" => "Delete image?",
  "si_area_delete_image_question_label" => "Do you really want to delete this image?",
  "si_area_move_up_label" => "Move up welcome page-box",
  "si_area_move_down_label" => "Move down welcome page-box",
  "si_area_move_label" => "Move welcome page-box",

  // boxes
  "si_area_boxes_label" => "Boxes of the welcome page-area",
  "si_area_box_label" => "Welcome page box",
  "si_area_box_delete_label" => "Delete welcome page box",
  "si_area_box_delete_question_label" => "Do you really want to delete the welcome page box??",
  "si_area_box_showhide_label" => "Show/hide welcome page box",
  "si_area_box_move_up_label" => "Move welcome page box upwards",
  "si_area_box_move_down_label" => "Move welcome page box donward",
  "si_area_box_move_label" => "Move welcome page box",
  "si_area_box_position_locked_label" => "Welcome page box can not be moved",

  //"si_area_box_general_label" => "General area of the box",
  "si_area_box_link_label" => "Link for this box (Where is the destination of this box?)",
  "si_area_box_extlink_label" => "External link <small>(\"http://...\" - used only, if internal link was not specified)</small>",
  "si_area_box_title1_label" => "Box title",
  "si_area_box_title2_label" => "Box subheading",
  "si_area_box_title3_label" => "Box subheading",
  "si_area_box_text1_label" => "Box alternative-text",
  "si_area_box_text2_label" => "Box text",
  "si_area_box_text3_label" => "Box text",
  "si_area_box_image1_label" => "Box image",
  "si_area_box_image2_label" => "Box image",
  "si_area_box_image3_label" => "Box image",
  "si_area_box_delete_image_label" => "Delete image",
  "si_area_box_delete_image_question_label" => "Do you really want to delete this image?",
  "si_area_box_noimage_label" => "Not display image",

  "end", ""));


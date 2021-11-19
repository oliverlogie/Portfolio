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

if (!isset($_LANG2["cb"])) $_LANG2["cb"] = array();

$_LANG = array_merge($_LANG, array(

  "cb_button_save_label" => "save main area",
  "cb_box_button_save_label" => "save box %s",
  "cb_box_biglink_button_save_label" => "save teaser box %s",

  "cb_box_image_title_label" => "Image subtitle",

  "cb_message_invalid_links" => "%d invalid link(s) exist(s), due to deleted pages and downloads.",
  "cb_message_multiple_link_failure" => "Link already exists!",

  "cb_message_box_success" => "Box data has been updated.",
  "cb_message_box_insufficient_input" => "Link and additionally, either title, text or image, to specify the box!",
  "cb_message_box_autoimage_notpossible" => "The image of the box could not be retrieved from the linked page, as there was none available.",

  "cb_box_biglink_image_title_label" => "Image subtitle",

  "cb_message_box_biglink_delete_success" => "Teaser box deleted successfully.",
  "cb_message_box_biglink_create_success" => "Teaser box has been created.",
  "cb_message_box_biglink_success" => "Teaser data have been updated.",
  "cb_message_box_biglink_insufficient_input" => "Link and additionally, either title, text or image, to specify the teaser!",
  "cb_message_box_biglink_autoimage_notpossible" => "The image of the teaser box could not be retrieved from the linked page, as there was none available.",
  "cb_message_box_biglink_max_elements" => "The maximum number of teaser boxes has been reached.",

  "cb_message_box_smalllink_success" => "Teaser data have been updated.",
  "cb_message_box_smalllink_insufficient_input" => "Specify title and link for the teaser!",

  "cb_box_message_activation_enabled"  => "Box successfully activated!",
  "cb_box_message_activation_disabled" => "Box successfully deactivated!",

  "cb_box_link_broken_label" => "Link in the cluster area",
  "cb_box_biglink_broken_label" => "Link in teaser",
  "cb_box_smalllink_broken_label" => "Link in teaser",

  "end",""));

$_LANG2["cb"] = array_merge($_LANG2["cb"], array(

  "cb_layoutarea1_label" => "Main area",
  "cb_button_submit_label" => "Save main area",

  // Boxes
  "cb_box_label" => "Cluster area",
  "cb_box_title_label" => "Title of the cluster area",
  "cb_box_image_label" => "Image of the cluster area",
  "cb_box_delete_image_label" => "Delete image",
  "cb_box_delete_image_question_label" => "Do you really want to delete this image?",
  "cb_box_autoimage_label" => "Get image of the linked page",
  "cb_box_autoimage_action" => "Get the image automatically when saving",
  "cb_box_autoimage_description" => "The image of the box is automatically created out of the image of the linked page.",
  "cb_box_text_label" => "Text of the cluster area",
  "cb_box_link_label" => "Link to a content of the entire cluster area (multi-link)",
  "cb_box_showhide_label" => "Show/hide box",
  "cb_box_move_up_label" => "Move box upwards",
  "cb_box_move_down_label" => "Move box downwards",
  "cb_box_move_label" => "Move box",
  "cb_box_delete_label" => "Delete box",
  "cb_box_delete_question_label" => "Do you really want to delete this box?",
  "cb_button_box_autoimage_label" => "Get image now",

  // BigLinks
  "cb_box_biglinks_label" => "Teaser boxes of the cluster area",
  "cb_box_biglink_label" => "Teaser box",
  "cb_box_biglink_title_label" => "Title",
  "cb_box_biglink_image_label" => "Image",
  "cb_box_biglink_delete_image_label" => "Delete Image",
  "cb_box_biglink_delete_image_question_label" => "Do you really want to delete this image?",
  "cb_box_biglink_autoimage_label" => "Get the image of the linked page",
  "cb_box_biglink_autoimage_description" => "The image of the teaser box is automatically created of the image of the linked page.",
  "cb_box_biglink_autoimage_action" => "Get the image automatically when saving",
  "cb_box_biglink_text_label" => "Text",
  "cb_box_biglink_link_label" => "Link",
  "cb_box_biglink_showhide_label" => "Show/hide teaser box",
  "cb_box_biglink_move_up_label" => "Move teaser box upwards",
  "cb_box_biglink_move_down_label" => "move teaser box downwards",
  "cb_box_biglink_move_label" => "Move teaser box",
  "cb_box_biglink_delete_label" => "Delete teaser box",
  "cb_box_biglink_delete_question_label" => "Do you really want to delete this teaser box?",
  "cb_button_box_biglink_autoimage_label" => "Get the image",
  "cb_button_box_biglink_new_element_label" => "new teaser box",

  // SmallLinks
  "cb_box_smalllinks_label" => "Teaser links of the cluster area",
  "cb_box_smalllink_create_label" => "Create new teaser link",
  "cb_box_smalllink_edit_label" => "Modify teaser link",
  "cb_box_smalllinks_existing_label" => "Existing teaser links",
  "cb_box_smalllink_title_label" => "Title",
  "cb_box_smalllink_link_label" => "Link",
  "cb_box_smalllink_move_up_label" => "Move teaser link upwards",
  "cb_box_smalllink_move_down_label" => "Move teaser link downwards",
  "cb_box_smalllink_move_label" => "Move teaser link",
  "cb_box_smalllink_delete_label" => "Delete teaser link",
  "cb_box_smalllink_delete_question_label" => "Do you really want to delete this teaser link?",
  "cb_box_smalllink_actions_label" => "Teaser link Actions",
  "cb_button_box_smalllink_create_label" => "create teaser link",
  "cb_button_box_smalllink_edit_label" => "modify",
  "cb_button_box_smalllink_cancel_label" => "cancel",
  "cb_message_box_smalllink_maximum_reached" => "You can't create further teaser links, since the maximum number <br /> of teaser links was achieved.",

  "end",""));

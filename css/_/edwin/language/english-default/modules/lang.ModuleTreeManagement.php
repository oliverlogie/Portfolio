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

if (!isset($_LANG2["tm"])) $_LANG2["tm"] = array();

$_LANG = array_merge($_LANG, array(

  "tm_message_excluded"             => "Moving item failed! Content types not available on current navigation tree!",
  "tm_message_max_items"            => "Moving item failed! The maximum amount of items exceeded at least for one levels!",
  "tm_message_max_levels"           => "Moving item failed! The maximum amount of levels cannot be exceeded!",
  "tm_message_no_be"                => "Moving item failed! At least one blog level cannot be moved to target level!",
  "tm_message_no_ib"                => "Moving item failed! At least one teaser level cannot be moved to target level!",
  "tm_message_no_ip"                => "Moving item failed! At least one teaser level plus cannot be moved to target level!",
  "tm_message_no_lo"                => "Moving item failed! At least one navigation level cannot be moved to target level!",
  "tm_message_no_lp"                => "Moving item failed! At least one navigation level plus cannot be moved to target level!",
  "tm_message_move_item"            => "Moving item failed!",
  "tm_message_move_item_to_itself"  => "Cannot move item to itself!",
  "tm_message_move_last_active_item" => "Moving item failed, because it is the last active content box in this level. To move, activate another content box.",
  "tm_message_move_item_success"    => "Page moved successfully!",

  "end"));

  $_LANG2["tm"] = array_merge($_LANG2["tm"], array(

  "tm_title"                  => "Move item",
  "tm_label"                  => "Move item",
  "tm_label2"                 => "Insert item at the following position:",
  "tm_insert_label"           => "insert here",
  "tm_move_children_question" => "Move level content items only?",
  "tm_move_question"          => "Move item?",
  "tm_insert_item"            => "Insert here",
  "tm_help_title"             => "How to ...",
  "tm_help_text_1"            => "The item which has to be moved is highlighted like this:",
  "tm_help_text_2"            => "Click on an arrow or the adjacent text in order to open or close a branch of the navigation tree.",
  "tm_help_text_3"            => "The item can be inserted before or after another item or inside an empty navigation/teaser level.",
  "tm_help_text_4"            => "Click on the desired area in order to move the item to this position.",
  "tm_help_dummy_text"        => "Lorem ipsum",
  "tm_help_prelink_label"     => "before",
  "tm_help_insidelink_label"  => "inside",
  "tm_help_suclink_label"     => "after",

  "end",""));

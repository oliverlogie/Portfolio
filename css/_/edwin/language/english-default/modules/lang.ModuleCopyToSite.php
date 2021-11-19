<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: $
 * $LastChangedBy: $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2016 Q2E GmbH
 */

if (!isset($_LANG2['cs'])) $_LANG2['cs'] = array();

$_LANG = array_merge($_LANG, array(

  'm_mode_name_mod_copytosite' => 'Copy contents to other sites',

  "cs_site_label" => "<b>Active website</b>:<br /><span class=\"fontsize11\">Pages from <b>'%s'</b> can be copied...</span>",

  "cs_message_excluded"                        => "Moving item failed! Content types not available on current navigation tree!",
  "cs_message_max_items"                       => "Moving item failed! The maximum amount of items exceeded at least for one levels!",
  "cs_message_max_levels"                      => "Moving item failed! The maximum amount of levels cannot be exceeded!",
  "cs_message_no_be"                           => "Moving item failed! At least one blog level cannot be moved to target level!",
  "cs_message_no_ib"                           => "Moving item failed! At least one teaser level cannot be moved to target level!",
  "cs_message_no_ip"                           => "Moving item failed! At least one teaser level plus cannot be moved to target level!",
  "cs_message_no_lo"                           => "Moving item failed! At least one navigation level cannot be moved to target level!",
  "cs_message_no_lp"                           => "Moving item failed! At least one navigation level plus cannot be moved to target level!",
  "cs_message_copy_item_success"               => "Successfully copied the content.",
  "cs_message_copy_failure_page_not_found"     => "Could not find at least one of the two pages.",
  "cs_message_copy_failure_copy_not_available" => "Could not copy target page. This function is not available for the content type of this page.",
  "cs_message_copy_failure"                    => "Could not copy target page.",

  "end",""));

$_LANG2['cs'] = array_merge($_LANG2['cs'], array(

  "cs_function_label"          => "Copy pages to another website",
  "cs_function_label2"         => "Choose a page on this website and copy it to the menu of another website",
  "cs_btn_copy_and_edit_label" => "save/show content",
  "cs_btn_copy_label"          => "save",
  "cs_from_label"              => "Copy and move the following page",
  "cs_to_label"                => "Move to the target page on another website",

  "end",""));


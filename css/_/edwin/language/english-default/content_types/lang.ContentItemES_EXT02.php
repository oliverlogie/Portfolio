<?php
  /**
   * Lang: EN
   *
   * $LastChangedDate: 2015-06-12 10:55:15 +0200 (Fr, 12 Jun 2015) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Benjamin Ulmer
   * @copyright (c) 2011 Q2E GmbH
   */

if (!isset($_LANG2["es_ext02"])) $_LANG2["es_ext02"] = array();

$_LANG2["es_ext02"] = array_merge($_LANG2["es_ext02"],array(

  "es_ext_label" => "Additional content from an external source",
  "es_properties_label" => "Settings for YouTube data source",

  "es_property_labels" => array ( 1 => "Upload videos",
                                  2 => "User name",
                                  3 => "Channel",
                                  3 => "Playlist-ID",
                                  4 => "Search term",
                                  6 => "Maximum number",
                                  7 => "Sorting",
                                  8 => "Language" ),
  "es_property1_labels" => array ( 1 => "Of a user",
                                   2 => "Of a channel",
                                   3 => "Of a playlist",
                                   5 => "With a search term", ),
  "es_property7_labels" => array ( 1 => "Chronologically",
                                   2 => "Number of views",
                                   3 => "Evaluation",
                                   4 => "Relevance" ),
  // 0 = default, use site id for specific properties
  // use ISO 639-2 language codes only
  // @see http://www.loc.gov/standards/iso639-2/php/code_list.php
  "es_property8_labels" => array ( 0 => array( 0    => "any",
                                               "de" => "German",
                                               "en" => "English" )),

  "end",""));


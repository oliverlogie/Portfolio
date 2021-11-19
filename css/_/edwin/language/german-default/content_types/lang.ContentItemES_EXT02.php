<?php
  /**
   * Lang: DE
   *
   * $LastChangedDate: 2015-06-12 10:55:15 +0200 (Fr, 12 Jun 2015) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Anton Mayringer
   * @copyright (c) 2009 Q2E GmbH
   */

if (!isset($_LANG2["es_ext02"])) $_LANG2["es_ext02"] = array();

$_LANG2["es_ext02"] = array_merge($_LANG2["es_ext02"],array(

  "es_ext_label" => "Zusätzlicher Inhalt aus externer Datenquelle",
  "es_properties_label" => "Einstellungen zur YouTube Datenquelle",

  "es_property_labels" => array ( 1 => "Lade Videos",
                                  2 => "Benutzername",
                                  3 => "Kanal",
                                  4 => "Playlist-ID",
                                  5 => "Suchbegriff",
                                  6 => "Maximale Anzahl",
                                  7 => "Sortierung",
                                  8 => "Sprache" ),
  "es_property1_labels" => array ( 1 => "von Benutzer",
                                   2 => "von einem Channel",
                                   3 => "von einer Playlist",
                                   4 => "mit Suchbegriff" ),
  "es_property7_labels" => array ( 1 => "Chronologisch",
                                   2 => "Anzahl Aufrufe",
                                   3 => "Bewertung",
                                   4 => "Relevanz" ),
  // 0 = default, use site id for specific properties
  // use ISO 639-2 language codes only
  // @see http://www.loc.gov/standards/iso639-2/php/code_list.php
  "es_property8_labels" => array ( 0 => array( 0    => "beliebig",
                                               "de" => "Deutsch",
                                               "en" => "Englisch" )),

  "end",""));


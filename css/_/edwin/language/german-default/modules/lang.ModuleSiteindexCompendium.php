<?php
/**
 * Lang: DE
 *
 * $LastChangedDate: 2018-03-27 17:03:14 +0200 (Di, 27 Mär 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Mayringer
 * @copyright (c) 2009 Q2E GmbH
 */

if (!isset($_LANG2['si'])) $_LANG2['si'] = array();

$_LANG = array_merge($_LANG, array(

  "m_mode_name_mod_siteindex" => "Startseite &auml;ndern",
  "modtop_ModuleSiteindex" => "Standard",

  "si_button_submit_label" => "speichern",

  "si_link_scope_none_label" => "",
  "si_link_scope_local_label" => "Der Link verweist auf die aktuelle Webseite.",
  "si_link_scope_global_label" => "Der Link verweist auf die Webseite '%s'.",
  "si_area_button_save_label" => "Startseiten-Bereich %s speichern",
  "si_area_label" => array(0 => "Startseiten-Bereich"),
  "si_area_box_button_save_label" => "Startseiten-Box %s speichern",
  "si_area_box_link_scope_none_label" => "",
  "si_area_box_link_scope_local_label" => "Der Link verweist auf die aktuelle Webseite.",
  "si_area_box_link_scope_global_label" => "Der Link verweist auf die Webseite '%s'.",

  "si_boxes_link" => "<a href=\"%s\" class=\"sn\">%s</a>",
  "si_special_page_link" => "<a href=\"%s\" class=\"sn\">%s</a>",
  "si_special_page_link_selected" => "%s",

  "si_message_invalid_links" => "%d ung&uuml;ltige Verlinkung(en) aufgrund gel&ouml;schter Seiten vorhanden.",

  "si_message_update_success" => "Startseite wurde gespeichert.",
  "si_message_deleteimage_success" => "Bild der Startseite wurde gel&ouml;scht.",
  "si_message_invalid_extlink" => "Die Daten konnten nicht gespeichert werden, da der angegebene externe Link ung&uuml;ltig ist. Bitte korrigieren Sie Ihre Eingaben oder entfernen Sie den Link.",

  "si_message_area_update_success" => "Startseiten-Bereich wurde gespeichert.",
  "si_message_area_move_success" => "Startseiten-Bereich wurde verschoben.",
  "si_message_area_delete_success" => "Startseiten-Bereich wurde gel&ouml;scht.",
  "si_message_area_deleteimage_success" => "Bild des Startseiten-Bereichs wurde gel&ouml;scht.",

  "si_message_area_box_update_success" => "Startseiten-Box wurde gespeichert.",
  "si_message_area_box_move_success" => "Startseiten-Box wurde verschoben.",
  "si_message_area_box_delete_success" => "Startseiten-Box wurde gel&ouml;scht.",
  "si_message_area_box_deleteimage_success" => "Bild der Startseiten-Box wurde gel&ouml;scht.",

  "si_area_message_activation_enabled"      => "Bereich erfolgreich aktiviert!",
  "si_area_message_activation_disabled"     => "Bereich erfolgreich deaktiviert!",
  "si_area_box_message_activation_enabled"  => "Box erfolgreich aktiviert!",
  "si_area_box_message_activation_disabled" => "Box erfolgreich deaktiviert!",

  "si_image_title_label" => "Bilduntertitel",
  "si_image1_title_label" => "", // optional
  "si_image2_title_label" => "", // optional
  "si_image3_title_label" => "", // optional

  "end",""));

$_LANG2['si'] = array_merge($_LANG2['si'], array(

  // main
  "si_siteindex_label" => "Startseite",
  "si_siteindex_label2" => "Inhalte der Startseite &auml;ndern",

  // common
  "si_link_label" => "Link für die Box (wohin soll die Box führen?)",
  "si_extlink_label" => "Externer Link <small>(\"http://...\" - wird nur verwendet, wenn kein interner Link angegeben wurde)</small>",
  "si_title_label" => "&Uuml;berschrift",
  "si_text1_label" => "Text 1",
  "si_text2_label" => "Text 2",
  "si_text3_label" => "Text 3",
  "si_image1_label" => "Bild 1",
  "si_image2_label" => "Bild 2",
  "si_image3_label" => "Bild 3",
  "si_delete_image_label" => "Bild l&ouml;schen",
  "si_delete_image_question_label" => "Bild wirklich l&ouml;schen?",

  "si_button_submit_label" => "speichern",

  // areas
  "si_area_delete_label" => "Startseiten-Bereich l&ouml;schen",
  "si_area_delete_question_label" => "Startseiten-Bereich wirklich l&ouml;schen?",
  "si_area_showhide_label" => "Startseiten-Bereich anzeigen/ausblenden",

  "si_area_link_label" => "Link für den Bereich (wohin soll der Bereich führen?)",
  "si_area_extlink_label" => "Externer Link <small>(\"http://...\" - wird nur verwendet, wenn kein interner Link angegeben wurde)</small>",
  "si_area_title_label" => "Bereich Überschrift",
  "si_area_text_label" => "Bereich Text",
  "si_area_image_label" => "Bereich-Grafik",
  "si_area_delete_image_label" => "Bild l&ouml;schen",
  "si_area_delete_image_question_label" => "Bild wirklich l&ouml;schen?",
  "si_area_move_up_label" => "Startseiten-Bereich nach oben verschieben",
  "si_area_move_down_label" => "Startseiten-Bereich nach unten verschieben",
  "si_area_move_label" => "Startseiten-Bereich verschieben",

  // boxes
  "si_area_boxes_label" => "Boxen des Startseiten-Bereichs",
  "si_area_box_label" => "Startseiten-Box",
  "si_area_box_delete_label" => "Startseiten-Box l&ouml;schen",
  "si_area_box_delete_question_label" => "Startseiten-Box wirklich l&ouml;schen?",
  "si_area_box_showhide_label" => "Startseiten-Box anzeigen/ausblenden",
  "si_area_box_move_up_label" => "Startseiten-Box nach oben verschieben",
  "si_area_box_move_down_label" => "Startseiten-Box nach unten verschieben",
  "si_area_box_move_label" => "Startseiten-Box verschieben",
  "si_area_box_position_locked_label" => "Startseiten-Box kann nicht verschoben werden",

  //"si_area_box_general_label" => "Allgemeiner Bereich der Box",
  "si_area_box_link_label" => "Link für die Box (wohin soll die Box führen?)",
  "si_area_box_extlink_label" => "Externer Link <small>(\"http://...\" - wird nur verwendet, wenn kein interner Link angegeben wurde)</small>",
  "si_area_box_title1_label" => "Box Überschrift",
  "si_area_box_title2_label" => "Box Zwischenüberschrift",
  "si_area_box_title3_label" => "Box Zwischenüberschrift",
  "si_area_box_text1_label" => "Box Alternativ-Text",
  "si_area_box_text2_label" => "Box Text",
  "si_area_box_text3_label" => "Box Text",
  "si_area_box_image1_label" => "Box-Grafik",
  "si_area_box_image2_label" => "Box-Grafik",
  "si_area_box_image3_label" => "Box-Grafik",
  "si_area_box_delete_image_label" => "Bild l&ouml;schen",
  "si_area_box_delete_image_question_label" => "Bild wirklich l&ouml;schen?",
  "si_area_box_noimage_label" => "Kein Bild anzeigen",

  "end", ""));


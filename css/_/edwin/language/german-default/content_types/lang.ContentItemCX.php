<?php
/**
 * Lang: DE
 *
 * $LastChangedDate: 2019-06-14 12:06:35 +0200 (Fr, 14 Jun 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2019 Q2E GmbH
 */

if (!isset($_LANG2['cx'])) $_LANG2['cx'] = array();

  $_LANG = array_merge($_LANG,array(

  "cx_message_internal_error" => "Es ist ein schwerwiegender Fehler aufgetreten. Bitte versuchen Sie es erneut oder wenden Sie sich an den Administrator.<br>%s",
  "cx_message_area_create_invalid_area" => "Der Bereich konnte nicht angelegt werden. Bitte wählen Sie einen Typ für den neuen Bereich.",
  "cx_message_area_create_max_areas_exceeded" => "Die maximale Anzahl an Bereichen wurde bereits erreicht. Es kann kein weiterer Bereich hinzugefügt werden.",
  "cx_message_area_create_success" => "Der Bereich wurde erfolgreich angelegt",
  "cx_message_area_success_moved" => "Der Bereich wurde erfolgreich verschoben.",
  "cx_message_area_update_success" => "Der Bereich wurde erfolgreich gespeichert.",
  "cx_message_area_update_failure" => "Der Bereich wurde nur teilweise erfolgreich gespeichert. Bitte korrigieren Sie Ihre Eingaben bei den betroffenen Feldern und speichern Sie erneut.",
  "cx_message_area_delete_success" => "Der gewünschte Bereich wurde erfolgreich gelöscht.",
  "cx_message_area_process_cx_area_element_action_failure" => "Die gewünschte Aktion konnte nicht ausgeführt werden. Bitte versuchen Sie es erneut oder wenden Sie sich an den Administrator.",
  "cx_message_area_process_cx_area_element_action_invalid" => "Die gewünschte Aktion konnte für dieses Element nicht ausgeführt werden.",
  "cx_message_area_process_cx_area_element_boxes_add_box_success" => "Eine neue Box wurde erfolgreich angelegt und kann nun bearbeitet werden.",
  "cx_message_area_process_cx_area_element_boxes_add_box_failure" => "Es konnte keine neue Box mehr angelegt werden.",
  "cx_message_area_process_cx_area_element_boxes_move_box_success" => "Die gewünschte Box wurde erfolgreich verschoben.",
  "cx_message_area_process_cx_area_element_boxes_move_box_failure" => "Die gewünschte Box konnte leider nicht verschoben werden. Bitte versuchen Sie es nochmal oder wenden Sie sich an den Administrator.",
  "cx_message_area_process_cx_area_element_box_activate_success" => "Die Box wurde erfolgreich aktiviert. Sie ist nun auf der Webseite sichtbar.",
  "cx_message_area_process_cx_area_element_box_deactivate_success" => "Die Box wurde erfolgrecih deaktiviert. Sie ist nun auf der Webseite nicht mehr sichtbar.",
  "cx_message_area_process_cx_area_element_box_delete_success" => "Die Box wurde mit allen Inhalten erfolgreich gelöscht und ist nun nicht mehr verfügbar.",
  "cx_message_area_process_cx_area_element_box_update_success" => "Der Inhalt der Box konnte erfolgreich gespeichert werden.",
  "cx_message_area_process_cx_area_element_box_update_failure" => "Der Inhalt der Box konnte aufgrund eines Fehlers nicht vollständig gespeichert werden.",
  "cx_area_message_activation_disabled" => "Der Bereich wurde erfolgreich deaktiviert.",
  "cx_area_message_activation_enabled" => "Der Bereich wurde erfolgreich aktiviert.",

  "cx_link_scope_none_label" => "",
  "cx_link_scope_local_label" => "Der Link verweist auf die aktuelle Webseite.",
  "cx_link_scope_global_label" => "Der Link verweist auf die Webseite '%s'.",

  "cx_area_button_save_label"     => "Bereich %s speichern",

    // default labels
  "cx_areas_title_label" => "Überschrift",
  "cx_areas_title_placeholder_label" => "",
  "cx_areas_text_label" => "Text",
  "cx_areas_image_label" => "Bild",
  "cx_areas_image_title_label" => "Bilduntertitel",
  "cx_areas_image_title_placeholder_label" => "Bilduntertitel (Titel des Bildes)",
  "cx_areas_image_alt_label" => "Bild Alt-Text",
  "cx_areas_image_alt_placeholder_label" => "Bild Alt-Text zur Beschreibung für Barrierefreiheit",
  "cx_areas_video_label" => "Video",
  "cx_areas_video_placeholder_label" => "https://www.youtube.com/watch?v=XXXXX-XXXXX",
  "cx_areas_area_link_label" => "Link",
  "cx_areas_area_link_intlink_label" => "Interner Link",
  "cx_areas_area_link_intlink_placeholder_label" => "Tippen um Vorschläge zu erhalten",
  "cx_areas_area_link_extlink_label" => "Externer Link <small>(Link zu einer anderen Webseite)</small>",
  "cx_areas_area_link_extlink_placeholder_label" => "https://www.q2e.at",
  "cx_areas_area_alternatives_label" => "Wahlweise Anzeige",
  "cx_areas_area_boxes_label" => "Boxen-Bereich",
  "cx_areas_area_boxes_message_warning_no_boxes" => "Es wurden noch keine Boxen angelegt.",
  "cx_areas_area_boxes_message_warning_maximum_boxes" => "Die Maximalanzahl an Boxen wurde bereits angelegt. Es kann keine neue Box hinzugefügt werden.",
  "cx_areas_area_boxes_btn_add_box_label" => "Neue Box",
  "cx_areas_area_box_label" => "Box",
  "cx_areas_area_box_btn_save_label" => "Box %s speichern",
  "cx_areas_area_box_list_move_label" => "Box verschieben",
  "cx_areas_area_box_list_activation_green_label" => "aktiviert",
  "cx_areas_area_box_list_activation_red_label" => "deaktiviert",
  "cx_areas_area_box_list_delete_label" => "Box löschen",
  "cx_areas_area_box_list_delete_question" => "Soll die gwünschte Box wirklich unwiderruflich gelöscht werden?",
  "cx_areas_area_box_list_showhide_label" => "Box anzeigen/ausblenden",

    // custom labels (examples)
  "cx_areas_area_example_label" => "Beispiel (nur Vorlage, bei Kundenprojekten nicht verwenden)",
  "cx_areas_area_example_title_1_label" => "Überschrift",
  "cx_areas_area_example_title_1_placeholder" => "",

    // custom labels for additional area here
    // ...
  "cx_message_area_process_cx_area_element_image_delete_image_success" => "Das Bild wurde erfolgreich gelöscht.",

  "end",""));

$_LANG2['cx'] = array_merge($_LANG2['cx'], array(

  "cx_message_add_subelement_unavailable" => "Die maximale Anzahl an Bereichen wurde bereits erreicht. Es können keine weiteren Bereiche angelegt werden.",

  "cx_button_new_element_label" => "Neuen Bereich",
  "cx_image_alt_label"          => "Bild",
  "cx_image_delete_label"       => "Bild löschen",
  "cx_image_delete_question"    => "Soll dieses Bild wirklich unwiderruflich gelöscht werden?",

  "cx_areas_label"                => "Weitere Bereiche",
  "cx_area_label"                 => "Bereich",
  "cx_area_showhide_label"        => "Bereich anzeigen/ausblenden",
  "cx_area_move_up_label"         => "Bereich nach oben verschieben",
  "cx_area_move_down_label"       => "Bereich nach unten verschieben",
  "cx_area_move_label"            => "Bereich verschieben",
  "cx_area_delete_label"          => "Bereich l&ouml;schen",
  "cx_area_delete_question_label" => "Bereich wirklich l&ouml;schen?",

  "end",""));


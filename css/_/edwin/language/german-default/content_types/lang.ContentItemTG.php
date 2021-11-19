<?php
/**
 * Lang: DE
 *
 * $LastChangedDate: 2017-09-12 09:02:14 +0200 (Di, 12 Sep 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2010 Q2E GmbH
 */
if (!isset($_LANG2['tg'])) $_LANG2['tg'] = array();

$_LANG = array_merge($_LANG, array(

  'tg_message_upload_missing_file_error' => 'Der Bildupload ist fehlgeschlagen, da kein Bild ausgew&auml;hlt wurde! Bitte w&auml;hlen Sie ein Bild aus!',
  'tg_message_tag_error' => 'Es konnte kein neuer Tag erstellt werden! Bitte geben Sie einen Titel an!',
  'tg_message_tag_success' => 'Ein neuer Tag wurde erfolgreich erstellt!',
  'tg_message_tag_duplicate_title' => 'Es existiert bereits ein Tag mit diesem Titel (Gro&szlig;- bzw. Kleinschreibung wird ignoriert)!',

  "tg_message_fileupload_success" => "ZIP-Datei wurde erfolgreich hochgeladen. Sie können nun die Bilder zur Galerie hinzufügen.",
  "tg_message_zipfile_error" => "Fehler beim &Ouml;ffnen der ZIP-Datei!",
  "tg_message_process_zip_success" => "ZIP Datei wurde verarbeitet (%s von %s erfolgreich).",
  "tg_message_process_zip_error" => "<br />Folgende Fehler wurden festgestellt: %sx falsche Aufl&ouml;sung; %sx falsches Format; %sx zu gro&szlig;.",
  "tg_message_process_zip_error_too_much_images" => "<br />Die ZIP Datei enthielt %s Bilder zu viel (maximal %s g&uuml;ltige erlaubt), die &uuml;berz&auml;hligen wurden verworfen.",
  "tg_message_insert_image_success" => "Ein neues Bild wurde an Position %s eingef&uuml;gt.",
  "tg_message_gallery_image_customdata_update_success" => "Spezieller Titel/Text wurde gespeichert.",
  "tg_message_gallery_image_move_success" => "Das Bild wurde verschoben.",
  "tg_message_gallery_image_delete_success" => "Das Bild wurde aus der Galerie gel&ouml;scht.",
  "tg_message_gallery_image_customdata_delete_success" => "Spezieller Titel und Text wurden vom Bild gel&ouml;scht.",
  "tg_message_gallery_images_delete_success_one" => "Ein Bild wurde aus der Galerie gel&ouml;scht.",
  "tg_message_gallery_images_delete_success_more" => "%s Bilder wurden aus der Galerie gel&ouml;scht.",

  "tg_gallery_image_customdata_available_label" => "Dieses Bild besitzt bereits eine Beschreibung",
  "tg_gallery_image_customdata_not_available_label" => "Noch keine Beschreibung verfügbar",

  "tg_file_extension_unknown_label" => "unbekannt",
  "tg_image_resolution" => "%s x %s",

  "end",""));

$_LANG2['tg'] = array_merge($_LANG2['tg'], array(

  "tg_boximage_data_label" => "Box-Bild",
  "tg_boximage_showhide_label" => "Bereich anzeigen/verstecken",
  "tg_common_label" => "Allgemeiner Layoutbereich",
  "tg_common_showhide_label" => "Hauptbereich anzeigen/ausblenden",
  "tg_common_actions_label" => "Allgemeiner Layoutbereich Aktionen",
  "tg_button_submit_label" => "speichern",
  "tg_gallery_upload_zip_tab_label" => "Zip Upload",
  "tg_gallery_upload_image_tab_label" => "Bildupload",

  "tg_gallery_upload_label" => "Galerie-Upload",
  "tg_gallery_upload_showhide_label" => "Uploadbereich anzeigen/ausblenden",

  "tg_gallery_upload_image_label" => "Weiteres Bild",
  "tg_gallery_upload_image_position_label" => "Position f&uuml;r neues Bild",
  "tg_button_gallery_upload_image_label" => "Bild einf&uuml;gen",

  "tg_gallery_upload_zip_label" => "Geben Sie hier eine ZIP-Datei an!",
  "tg_button_gallery_upload_zip_label" => "ZIP Datei hochladen",
  "tg_gallery_uploaded_zip_label" => "Hochgeladene ZIP-Datei",
  "tg_gallery_uploaded_zip_position_label" => "Einf&uuml;geposition",
  "tg_gallery_uploaded_zip_position_start_label" => "am Beginn",
  "tg_gallery_uploaded_zip_position_end_label" => "am Ende",
  "tg_gallery_uploaded_zip_process_label" => "Bilder einf&uuml;gen",

  "tg_gallery_image_customdata_subtitle_label" => "Bilduntertitel",
  "tg_gallery_image_customdata_title_label" => "Beschreibungstitel",
  "tg_gallery_image_customdata_text_label" => "Beschreibungstext",
  "tg_gallery_image_tags_label"  => "Tags f&uuml;r das Bild",
  "tg_gallery_image_tags_placeholder" => "Es wurden noch keine Tags zugewiesen. Klicken Sie hier um Tags zu diesem Bild zuzuweisen.",
  'tg_gallery_image_save_label'  => 'speichern',

  "tg_message_gallery_maximum_reached" => "Sie k&ouml;nnen keine weiteren Bilder in die Galerie einf&uuml;gen, da die Maximalanzahl der Bilder erreicht wurde.",

  "tg_gallery_images_label" => "Bereits vorhandene Bilder",
  "tg_gallery_images_showhide_label" => "Bereits vorhandene Bilder anzeigen/ausblenden",
  "tg_gallery_images_actions_label" => "Bereits vorhandene Bilder Aktionen",
  "tg_gallery_images_markall_label" => "Alle Bilder markieren",
  "tg_gallery_images_unmarkall_label" => "Alle Bilder demarkieren",
  "tg_gallery_images_delete_question_label" => "Ausgewählte Bilder wirklich löschen?",
  "tg_gallery_image_delete_label" => "Bild l&ouml;schen",
  "tg_gallery_image_delete_question_label" => "Bild wirklich l&ouml;schen?",
  "tg_gallery_image_customdata_label" => "Beschreibung bearbeiten",
  "tg_gallery_image_move_label" => "Bild verschieben",
  "tg_button_gallery_image_customdata_delete_label" => "L&ouml;schen",
  "tg_button_gallery_image_customdata_save_label" => "Speichern",
  "tg_button_gallery_images_delete_label" => "Bild(er) l&ouml;schen",
  "tg_button_gallery_image_customdata_cancel_label"  => "Abbrechen",

  // tags
  "tg_tag_add_label" => "Tag hinzuf&uuml;gen",
  "tg_tag_remove_label" => "Tag l&ouml;schen",
  "tg_message_max_tags" => "Keine weiteren Tags erlaubt!",

  "end",""));


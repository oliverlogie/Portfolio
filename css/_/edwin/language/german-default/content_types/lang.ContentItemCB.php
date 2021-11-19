<?php
  /**
   * Lang: DE
   *
   * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Stefan Podskubka
   * @copyright (c) 2009 Q2E GmbH
   */

if (!isset($_LANG2["cb"])) $_LANG2["cb"] = array();

$_LANG = array_merge($_LANG, array(

  "cb_button_save_label" => "Hauptbereich speichern",
  "cb_box_button_save_label" => "Clusterbereich %s speichern",
  "cb_box_biglink_button_save_label" => "Anrei&szlig;er-Box %s speichern",

  "cb_box_image_title_label" => "Bilduntertitel",

  "cb_message_invalid_links" => "%d ung&uuml;ltige Verlinkung(en) aufgrund gel&ouml;schter Seiten bzw. Downloads vorhanden.",
  "cb_message_multiple_link_failure" => "Link wurde bereits einmal angegeben!",

  "cb_message_box_success" => "Box-Daten wurden aktualisiert",
  "cb_message_box_insufficient_input" => "Link und zusätzlich entweder &Uuml;berschrift, Text oder Bild für Box angeben!",
  "cb_message_box_autoimage_notpossible" => "Das Bild der Box konnte nicht von der verlinkten Seite geholt werden, da dort keines verf&uuml;gbar war.",

  "cb_box_biglink_image_title_label" => "Bilduntertitel",

  "cb_message_box_biglink_delete_success" => "Anrei&szlig;er-Box wurde entfernt.",
  "cb_message_box_biglink_create_success" => "Anrei&szlig;er-Box wurde erstellt.",
  "cb_message_box_biglink_success" => "Anrei&szlig;er-Box Daten wurden aktualisiert",
  "cb_message_box_biglink_insufficient_input" => "Link und zus&auml;tzlich entweder &Uuml;berschrift, Text oder Bild f&uuml;r die Anrei&szlig;er-Box angeben!",
  "cb_message_box_biglink_autoimage_notpossible" => "Das Bild der Anrei&szlig;er-Box konnte nicht von der verlinkten Seite geholt werden, da dort keines verf&uuml;gbar war.",
  "cb_message_box_biglink_max_elements" => "Die maximale Anzahl an Anrei&szlig;er-Boxen wurde erreicht.",

  "cb_message_box_smalllink_success" => "Anrei&szlig;er-Link Daten wurden aktualisiert",
  "cb_message_box_smalllink_insufficient_input" => "Titel und Link f&uuml;r Anrei&szlig;er-Link angeben!",

  "cb_box_message_activation_enabled"  => "Clusterbereich erfolgreich aktiviert!",
  "cb_box_message_activation_disabled" => "Clusterbereich erfolgreich deaktiviert!",

  "cb_box_link_broken_label" => "Link im Clusterbereich",
  "cb_box_biglink_broken_label" => "Link in Anrei&szlig;erbox",
  "cb_box_smalllink_broken_label" => "Link in Anrei&szlig;erbox",

  "end",""));

$_LANG2["cb"] = array_merge($_LANG2["cb"], array(

  "cb_layoutarea1_label" => "Hauptbereich",
  "cb_button_submit_label" => "Hauptbereich speichern",

  // Boxes
  "cb_box_label" => "Clusterbereich",
  "cb_box_title_label" => "&Uuml;berschrift/Titel des Clusterbereichs",
  "cb_box_image_label" => "Bild des Clusterbereichs",
  "cb_box_delete_image_label" => "Bild l&ouml;schen",
  "cb_box_delete_image_question_label" => "Bild wirklich l&ouml;schen?",
  "cb_box_autoimage_label" => "Bild von verlinkter Seite holen",
  "cb_box_autoimage_action" => "Bild beim Speichern automatisch holen",
  "cb_box_autoimage_description" => "Das Bild der Box wird automatisch aus dem Bild der verlinkten Seite erstellt.",
  "cb_box_text_label" => "Text des Clusterbereichs",
  "cb_box_link_label" => "Link zu einem Inhalt des gesamten Clusterbereichs (mehr-Link)",
  "cb_box_showhide_label" => "Box anzeigen/ausblenden",
  "cb_box_move_up_label" => "Box nach oben verschieben",
  "cb_box_move_down_label" => "Box nach unten verschieben",
  "cb_box_move_label" => "Box verschieben",
  "cb_box_delete_label" => "Inhalt der Box l&ouml;schen",
  "cb_box_delete_question_label" => "Inhalt der Box wirklich l&ouml;schen?",
  "cb_button_box_autoimage_label" => "Bild jetzt holen",

  // BigLinks
  "cb_box_biglinks_label" => "Anrei&szlig;er-Boxen des Clusterbereichs",
  "cb_box_biglink_label" => "Anrei&szlig;er-Box",
  "cb_box_biglink_title_label" => "&Uuml;berschrift",
  "cb_box_biglink_image_label" => "Bild",
  "cb_box_biglink_delete_image_label" => "Bild l&ouml;schen",
  "cb_box_biglink_delete_image_question_label" => "Bild wirklich l&ouml;schen?",
  "cb_box_biglink_autoimage_label" => "Bild von der verlinkten Seite holen",
  "cb_box_biglink_autoimage_description" => "Das Bild der Anrei&szlig;er-Box wird automatisch aus dem Bild der verlinkten Seite erstellt.",
  "cb_box_biglink_autoimage_action" => "Bild beim Speichern automatisch holen",
  "cb_box_biglink_text_label" => "Text",
  "cb_box_biglink_link_label" => "Link",
  "cb_box_biglink_showhide_label" => "Anrei&szlig;er-Box anzeigen/ausblenden",
  "cb_box_biglink_move_up_label" => "Anrei&szlig;er-Box nach oben verschieben",
  "cb_box_biglink_move_down_label" => "Anrei&szlig;er-Box nach unten verschieben",
  "cb_box_biglink_move_label" => "Anrei&szlig;er-Box verschieben",
  "cb_box_biglink_delete_label" => "Anrei&szlig;er-Box l&ouml;schen",
  "cb_box_biglink_delete_question_label" => "Anrei&szlig;er-Box wirklich l&ouml;schen?",
  "cb_button_box_biglink_autoimage_label" => "Bild holen",
  "cb_button_box_biglink_new_element_label" => "Neue Anrei&szlig;er-Box",

  // SmallLinks
  "cb_box_smalllinks_label" => "Anrei&szlig;er-Links des Clusterbereichs",
  "cb_box_smalllink_create_label" => "Neuen Anrei&szlig;er-Link anlegen",
  "cb_box_smalllink_edit_label" => "Anrei&szlig;er-Link &auml;ndern",
  "cb_box_smalllinks_existing_label" => "Vorhandene Anrei&szlig;er-Links",
  "cb_box_smalllink_title_label" => "&Uuml;berschrift",
  "cb_box_smalllink_link_label" => "Link",
  "cb_box_smalllink_move_up_label" => "Anrei&szlig;er-Link nach oben verschieben",
  "cb_box_smalllink_move_down_label" => "Anrei&szlig;er-Link nach unten verschieben",
  "cb_box_smalllink_move_label" => "Anrei&szlig;er-Link verschieben",
  "cb_box_smalllink_delete_label" => "Anrei&szlig;er-Link l&ouml;schen",
  "cb_box_smalllink_delete_question_label" => "Anrei&szlig;er-Link wirklich löschen?",
  "cb_box_smalllink_actions_label" => "Anrei&szlig;er-Link Aktionen",
  "cb_button_box_smalllink_create_label" => "Anrei&szlig;er-Link anlegen",
  "cb_button_box_smalllink_edit_label" => "&auml;ndern",
  "cb_button_box_smalllink_cancel_label" => "abbrechen",
  "cb_message_box_smalllink_maximum_reached" => "Sie k&ouml;nnen keine weiteren Anrei&szlig;er-Links anlegen, da die Maximalanzahl<br /> der Anrei&szlig;er-Links erreicht wurde.",

  "end",""));

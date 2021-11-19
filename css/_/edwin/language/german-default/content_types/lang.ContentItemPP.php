<?php
/**
 * Lang: DE
 *
 * $LastChangedDate: 2017-10-09 14:04:21 +0200 (Mo, 09 Okt 2017) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2["pp"])) $_LANG2["pp"] = array();

$_LANG = array_merge($_LANG, array(

  "pp_attribute_global_title_none" => "kein",
  "pp_product_title_header"        => "- %s",

  'pp_message_no_product' => 'Daten wurden gespeichert. Zum Onlinestellen muss mindestens ein Produkt angelegt werden.',

  "pp_message_option_delete_success"     => "Option erfolgreich entfernt.",
  "pp_message_option_existing_failure"   => "Diese Option wurde bereits zum Produkt hinzugef&uuml;gt.",
  "pp_message_option_create_success"     => "Option erfolgreich hinzugef&uuml;gt.",
  "pp_message_option_insufficient_input" => "Es wurde keine Option gew&auml;hlt. Bitte eine Option w&auml;hlen!",
  "pp_message_option_edit_success"       => "Option wurde aktualisiert.",
  "pp_message_option_move_success"       => "Option erfolgreich verschoben",

  "pp_message_product_create_failure" => "Es konnte kein weiteres Produkt erstellt werden.",
  "pp_message_product_create_success" => "Produkt erfolgreich erstellt.",
  "pp_message_product_delete_success" => "Produkt wurde entfernt.",
  "pp_message_product_edit_failure"   => "Es existiert bereits ein Produkt mit diesen Attributen! Bitte andere Attribute w&auml;hlen.",
  "pp_message_product_move_success"   => "Produkt erfolgreich verschoben",
  "pp_message_product_max_elements"   => "Die maximale Anzahl an Produktvariationen wurde erreicht.",
  "pp_message_product_none_filter"    => "F&uuml;r dieses Filtereinstellungen k&ouml;nnen keine Produkte gefunden werden. <br/>&Auml;ndern Sie die Filtereigenschaften oder setzen Sie alle Filter <a class=\"sn\" href=\"%s\">zur&uuml;ck</a>, um Ergebnisse zu erhalten.",
  "pp_message_product_success"        => "Produkt erfolgreich ge&auml;ndert",
  "pp_product_message_activation_enabled"  => "Produkt erfolgreich aktiviert!",
  "pp_product_message_activation_disabled" => "Produkt erfolgreich deaktiviert!",
  "pp_product_message_activation_linked_enabled"  => "Produkt erfolgreich aktiviert! Es konnten %d verkn&uuml;pfte Produkte aktiviert werden.",
  "pp_product_message_activation_linked_disabled" => "Produkt erfolgreich deaktiviert! Es konnten %d verkn&uuml;pfte Produkte deaktiviert werden.",
  "pp_product_message_show_on_level_activated"    => "Dieses Produkt wird nun in der Produktanrei&szlig;erebene angezeigt.",
  "pp_product_message_show_on_level_deactivated"  => "Dieses Produkt wird nun nicht mehr in der Produktanrei&szlig;erebene angezeigt.",
  "pp_product_show_on_level_green_label"          => "Wird in der Produktanrei&szlig;erebene angezeigt",
  "pp_product_show_on_level_red_label"            => "Wird nicht in der Produktanrei&szlig;erebene angezeigt",
  "pp_product_additional_data_labels"             => array(),

  "pp_message_failure" => "Jede Attributgruppe darf nur einmal vergeben werden! Daten wurden nicht gespeichert.",

  "pp_product_image1_label" => "Produktbild",
  "pp_tax_rate_shortname"   => array(1 => "20", 2 => "10"),

  "pp_product_filter_none_label" => "kein",
  "pp_product_filter_compare"    => array( "equals"        => "gleich wie im allgemeinen Bereich",
                                           "different"     => "Unterscheidung in zumindest einem Wert von den allgemeinen Einstellungen",
                                           "casePacks"     => "Verpackungseinheiten abweichend",
                                           "name"          => "Name abweichend",
                                           "price"         => "Preis abweichend",
                                           "shippingCosts" => "Versandkosten abweichend" ),

  "end",""));

$_LANG2["pp"] = array_merge($_LANG2["pp"], array(

  "pp_additionalfunctions_label" => "Zus&auml;tzliche Funktionen des allgemeinen Bereichs",
  "pp_area_actions_label"        => "Allgemeiner Layoutbereich Aktionen",
  "pp_attribut_global_label"     => "Attributgruppen &amp; Preis",
  "pp_attribute_global_actions_label" => "Aktionen",
  "pp_attribut_global_showhide_label" => "Attributgruppen &amp; Preis anzeigen/ausblenden",
  "pp_button_submit_label"       => "speichern",
  "pp_button_new_element_label"  => "+ Neues Produkt",
  "pp_case_packs_label"          => "Verpackungseinheiten",
  "pp_shipping_costs_label"      => "Versandkosten",
  "pp_layoutarea1_label"         => "Allgemeiner Layoutbereich",
  "pp_price_label"               => "Preis",
  "pp_settings_label"            => "Lieferoptionen",
  "pp_showhide_label"            => "Hauptbereich anzeigen/ausblenden",
  'pp_page_label'                => 'Seite',

  "pp_image1_label" => "Produktbild",
  "pp_text1_label"  => "Produktbeschreibung",
  "pp_title1_label" => "Produktname",
  "pp_additional_images_label" => "Produkt-Detailbilder",

  // Products
  "pp_button_product_submit_label"   => "speichern",
  "pp_product_additional_data_label" => "Zusatzdaten",
  "pp_product_attributes_label"      => "Attribute",
  "pp_product_label"                 => "Produkt",
  "pp_product_showhide_label"        => "Produkt anzeigen/ausblenden",
  "pp_product_title_label"           => "Produktname",
  "pp_product_text_label"            => "Produktbeschreibung",
  "pp_product_delete_image_label"    => "Bild l&ouml;schen",
  "pp_product_delete_image_question_label" => "Bild wirklich l&ouml;schen?",
  "pp_product_move_up_label"         => "Produkt nach oben verschieben",
  "pp_product_move_down_label"       => "Produkt nach unten verschieben",
  "pp_product_move_label"            => "Produkt verschieben",
  "pp_product_delete_label"          => "Produkt l&ouml;schen",
  "pp_product_delete_question_label" => "Produkt wirklich l&ouml;schen?",
  "pp_product_actions_label"         => "Produkt-Aktionen",
  "pp_product_price_label"           => "Preis",
  "pp_product_number_label"          => "Artikelnummer",
  "pp_products_label"                => "Produkte",
  "pp_product_additional_images_label" => "Detailbilder",
  "pp_tax_rate_label"                => "Steuerrate",
  "pp_product_change_filter_label"   => "Filter &auml;ndern",
  "pp_product_reset_filter_label"    => "Filter zur&uuml;cksetzen",
  "pp_product_set_filter_label"      => "Filter setzen",
  "pp_product_filter_status_label"   => "Nach Attributen und &Auml;nderungen filtern",
  "pp_product_filter_compare_title"  => "Unterschiede von Produkten zu den im <i>Hauptbereich</i> definierten Werten f&uuml;r Produktname, Preis, Verpackungseinheiten und Versandkosten. W&auml;hlen Sie hier welche Unterscheidungen Sie genau filtern wollen:",
  "pp_product_tax_rate_default"      => "Standard übernehmen",

  // options
  "pp_button_option_create_label"      => "hinzuf&uuml;gen",
  "pp_button_option_edit_label"        => "&auml;ndern",
  "pp_button_option_cancel_label"      => "abbrechen",
  "pp_message_options_maximum_reached" => "Sie k&ouml;nnen keine weiteren Optionen hinzuf&uuml;gen, da die Maximalanzahl<br /> der Optionen erreicht wurde.",
  "pp_option_create_label"             => "Neue Option hinzuf&uuml;gen",
  "pp_option_edit_label"               => "Option &auml;ndern",
  "pp_option_delete_label"             => "Option l&ouml;schen",
  "pp_option_delete_question_label"    => "Option wirklich löschen?",
  "pp_option_option_label"             => "Option",
  "pp_option_price_label"              => "Preis",
  "pp_option_move_down_label"          => "Option nach unten verschieben",
  "pp_option_move_label"               => "Option verschieben",
  "pp_option_move_up_label"            => "Option nach oben verschieben",
  "pp_options_label"                   => "Optionen",
  "pp_options_existing_label"          => "Vorhandene Optionen",
  "pp_options_showhide_label"          => "Optionen anzeigen/ausblenden",

  "end",""));

<?php
/**
 * Lang: DE
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['tc'])) $_LANG2['tc'] = array();

$_LANG = array_merge($_LANG, array(

  'mod_tagcloud_new_label'  => 'Tag anlegen',
  'mod_tagcloud_edit_label' => 'Tag &auml;ndern',
  'modtop_ModuleTagcloud'   => 'Tagcloud',
  'm_mode_name_mod_tagcloud' => 'Tagcloud verwalten',

  'tc_message_delete_item_success'   => 'Tag erfolgreich gel&ouml;scht.',
  'tc_message_edit_item_success'     => 'Tag erfolgreich gespeichert.',
  'tc_message_failure_no_title'      => 'Es wurde kein Titel angegeben!',
  'tc_message_failure_existing'      => 'Es existiert bereits eine Tag mit diesem Titel',
  'tc_message_move_success'          => 'Tag wurde verschoben.',
  'tc_message_new_item_success'      => 'Tag erfolgreich angelegt.',
  'tc_message_no_categories'         => 'Es gibt noch keine Tagcloud Gruppen für diese Seite.',
  'tc_message_no_items'              => 'Keine Tags definiert.',
  'tc_message_no_link'               => 'Es wurde kein Link angegeben!',
  'tc_message_invalid_external_url_protocol' => 'Ung&uuml;ltiges Protokoll im externen Link! Erlaubte Protokolle: %s',
  'tc_message_invalid_internal_url_protocol' => 'Ung&uuml;ltiges Protokoll im internen Link! Erlaubte Protokolle: %s',
  'tc_message_bad_links'             => '%s ung&uuml;ltige Verlinkung(en) vorhanden.',
  'tc_message_invalid_link'          => 'Ung&uuml;ltige Verlinkung aufgrund einer gel&ouml;schten Seite vorhanden.',
  'tc_message_invisible_link'        => 'Ung&uuml;ltige Verlinkung aufgrund einer deaktivierten Seite vorhanden.',
  'tc_message_invalid_internal_url'  => 'Der manuelle interne Link ist ung&uuml;ltig!',

  'tc_function_edit_label'      => 'TAG&nbsp;&Auml;NDERN',
  'tc_function_edit_label2'     => 'Daten des bestehenden Tags &auml;ndern',
  'tc_function_new_label'       => 'TAG&nbsp;ANLEGEN',
  'tc_function_new_label2'      => 'Daten des neuen Tags eingeben',
  'tc_moduleleft_newitem_label' => '+ Neuer Tag',
  'tc_site_label'               => '<b>Aktiver Webseitenfilter</b>:<br /><span class="fontsize11">Tags zur Webseite <b>\'%s\'</b> werden angezeigt...</span>',

  'tc_link_scope_none_label'         => '',
  'tc_link_scope_local_label'        => 'Der Link verweist auf die aktuelle Webseite.',
  'tc_link_scope_custom_local_label' => 'Der manuelle Link verweist auf die aktuelle Webseite.',
  'tc_link_scope_global_label'       => 'Der Link verweist auf die Webseite \'%s\'.',
  'tc_tag_sizes' => array (
    1 => 'klein',
    2 => 'mittel',
    3 => 'gro&szlig;',
  ),

  'end',''));

$_LANG2['tc'] = array_merge($_LANG2['tc'], array(

  'tc_choose_label'    => 'Bitte w&auml;hlen ...',
  'tc_choose_category' => 'Gruppe w&auml;hlen',
  'tc_custom_link_label' => 'Manuell',
  'tc_custom_link_info' => '\'http://...\' - Manueller interner Link mit speziellen Parametern',
  'tc_delete_label'    => 'Tag l&ouml;schen',
  'tc_deleteitem_question_label' => 'Tag wirklich löschen?',
  'tc_edit_label'      => 'Tag &auml;ndern',
  'tc_existing_label'  => 'Vorhandene Tags',
  'tc_extlink_label'   => 'Externer Link',
  'tc_extlink_text'    => '(\'http://...\' - wird nur verwendet, wenn kein interner Link angegeben wurde)',
  'tc_intlink_label'   => 'Interner Link',
  'tc_link_label'      => 'Zielseite',
  'tc_list_label'      => 'Tagliste',
  'tc_list_label2'     => 'Liste aller Tags',
  'tc_list_size_label' => 'Gr&ouml;&szlig;e',
  'tc_move_up_label'   => 'Tag nach oben verschieben',
  'tc_move_down_label' => 'Tag nach unten verschieben',
  'tc_move_label'      => 'Tag verschieben',
  'tc_size_label'      => 'Schriftgr&ouml;&szlig;e des Tags innerhalb der Tagcloud',
  'tc_title_label'     => 'Titel',

  'end',''));


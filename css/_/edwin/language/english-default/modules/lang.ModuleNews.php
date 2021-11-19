<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

if (!isset($_LANG2['nw'])) $_LANG2['nw'] = array();

$_LANG = array_merge($_LANG,array(

  'mod_news_new_label'  => 'Create news',
  'mod_news_edit_label' => 'Edit news',
  'm_mode_name_mod_news' => 'Manage news',
  'nw_moduleleft_newitem_label' => '+ New news entry',

  // Main
  'nw_function_new_label' => 'CREATE&nbsp;News',
  'nw_function_new_label2' => 'Enter news data',
  'nw_function_edit_label' => 'EDIT&nbsp;News',
  'nw_function_edit_label2' => 'Change existing news data',
  'nw_function_list_label' => 'List of News',
  'nw_function_list_label2' => 'Available news',

  // List
  'nw_site_label'               => '<b>Active web filter</b>:<br /><span class="fontsize11">News to the Website <b>\'%s\'</b> are displayed...</span>',
  'nw_news_of_category' => 'News of category \'%s\'',

  // Messages
  'nw_message_no_news'        => 'No news in this category defined.',
  'nw_message_create_success' => 'News successfully created.',
  'nw_message_update_success' => 'News successfully edited',
  'nw_message_delete_success' => 'News successfully deleted.',
  'nw_message_no_categories'  => 'No news categories for this site available.',

  // News model fields
  'nw_title_label' => 'Title',
  'nw_text_label'  => 'Text',
  'nw_create_date_time_label' => 'Createdate',
  'nw_end_date_time_label'    => 'Enddate',
  'nw_start_date_time_label'  => 'Startdate',

  'end',''));

$_LANG2['nw'] = array_merge($_LANG2['nw'], array(

  // List
  'nw_news_label'            => 'News',
  'nw_delete_label'          => 'Delete news',
  'nw_delete_question_label' => 'Really delete news?',
  'nw_content_label'         => 'Edit news',
  'nw_choose_category_label' => 'Choose category',

  'end',''));

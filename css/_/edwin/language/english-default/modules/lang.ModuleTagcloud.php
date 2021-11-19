<?php
/**
 * Lang: EN
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

  'mod_tagcloud_new_label'  => 'Create tag',
  'mod_tagcloud_edit_label' => 'Edit tag',
  'modtop_ModuleTagcloud'   => 'Tagcloud',
  'm_mode_name_mod_tagcloud' => 'Manage tagcloud',

  'tc_message_delete_item_success'   => 'Tag successfully deleted.',
  'tc_message_edit_item_success'     => 'Tag successfully edited.',
  'tc_message_failure_no_title'      => 'Missing tag title!',
  'tc_message_failure_existing'      => 'There exists an tag using specified title! Please change title.',
  'tc_message_move_success'          => 'Tag successfully moved.',
  'tc_message_new_item_success'      => 'Tag successfully created.',
  'tc_message_no_items'              => 'No tags defined.',
  'tc_message_no_link'               => 'Missing link!',
  'tc_message_invalid_external_url_protocol' => 'Invalid protocol for external link! Possible protocols: %s',
  'tc_message_invalid_internal_url_protocol' => 'Invalid protocol for internal link! Possible protocols: %s',
  'tc_message_bad_links'             => '%s invalid links available.',
  'tc_message_invalid_link'          => 'Invalid link due to a deleted page available.',
  'tc_message_invisible_link'        => 'Invalid link due to a inactive page available.',
  'tc_message_invalid_internal_url'  => 'Specified custom internal link is invalid!',

  'tc_function_edit_label'      => 'EDIT&nbsp;TAG',
  'tc_function_edit_label2'     => 'Change existing tag data',
  'tc_function_new_label'       => 'CREATE&nbsp;TAG',
  'tc_function_new_label2'      => 'Enter new tag data',
  'tc_moduleleft_newitem_label' => '+ New tag',
  'tc_site_label'               => '<b>Active web filter</b>:<br /><span class="fontsize11">options to the Website <b>\'%s\'</b> are displayed...</span>',

  'tc_link_scope_none_label'         => '',
  'tc_link_scope_local_label'        => 'The link refers to the current website.',
  'tc_link_scope_custom_local_label' => 'The custom link refers to the current website.',
  'tc_link_scope_global_label'       => 'The link refers to the website \'%s\'.',
  'tc_tag_sizes' => array (
    1 => 'small',
    2 => 'medium',
    3 => 'large',
  ),

  'end',''));

$_LANG2['tc'] = array_merge($_LANG2['tc'], array(

  'tc_choose_label'    => 'Please choose ...',
  'tc_custom_link_label' => 'Custom',
  'tc_custom_link_info' => '\'http://...\' - Custom internal link with parameters',
  'tc_delete_label'    => 'Delete tag',
  'tc_deleteitem_question_label' => 'Really delete tag?',
  'tc_edit_label'      => 'Edit tag',
  'tc_existing_label'  => 'Existing tags',
  'tc_extlink_label'   => 'External link',
  'tc_extlink_text'    => '(\'http://...\' - used only, if internal link was not specified)',
  'tc_intlink_label'   => 'Internal link',
  'tc_link_label'      => 'Target page',
  'tc_list_label'      => 'Tag list',
  'tc_list_label2'     => 'List of tags',
  'tc_list_size_label' => 'Size',
  'tc_move_up_label'   => 'Move up tag',
  'tc_move_down_label' => 'Move down tag',
  'tc_move_label'      => 'Move tag',
  'tc_size_label'      => 'Tag\'s fontsize',
  'tc_title_label'     => 'Title',

  'end',''));


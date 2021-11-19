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

if (!isset($_LANG2['ep'])) $_LANG2['ep'] = array();

$_LANG = array_merge($_LANG, array(

  'modtop_ModuleEmployeeDepartment' => 'Departments',

  // Main
  'ep_function_edit_label'  => 'EDIT&nbsp;DEPARTMENT',
  'ep_function_edit_label2' => '',
  'ep_function_new_label'   => 'ADD&nbsp;DEPARTMENT',
  'ep_function_new_label2'  => '',
  'ep_list_label'           => 'Department list',
  'ep_list_label2'          => 'Manage departments of employees',
  'ep_moduleleft_newitem_label' => '+ New department',

  // List
  'ep_attribute_down_label' => 'Move department down',
  'ep_attribute_move_label' => 'Move department',
  'ep_attribute_up_label'   => 'Move department up',
  'ep_box_label'            => 'Department',
  'ep_content_label'        => 'Edit department',
  'ep_delete_label'         => 'Delete department',
  'ep_deleteitem_question_label' => 'Really delete department?',
  'ep_message_no_attribute' => 'No departments available',
  'en_site_label'           => '<b>Active web filter</b>:<br /><span class="fontsize11">departments to the Website <b>\'%s\'</b> are displayed...</span>',

  // Messages
  'ep_message_deleteitem_success' => 'Department successfully removed',
  'ep_message_edititem_success'   => 'Department successfully edited',
  'ep_message_move_success'       => 'Department successfully moved',
  'ep_message_newitem_success'    => 'Department successfully created',
  'ep_message_no_attribute_types' => 'Module is not installed correctly', // Solution: Create attribute group in database

  // Form fields
  'ep_title_label' => 'Title',

  'end',''));

$_LANG2['ep'] = array_merge($_LANG2['ep'], array(

  'end',''));


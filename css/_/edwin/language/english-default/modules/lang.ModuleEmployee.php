<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['ee'])) $_LANG2['ee'] = array();

$_LANG = array_merge($_LANG,array(

  "modtop_ModuleEmployee"       => "Employee",
  "mod_employee_new_label"       => "Create&nbsp;employee",
  "mod_employee_edit_label"      => "Edit&nbsp;employee",
  "m_mode_name_mod_employee"     => "Create/administer employee",
  "ee_moduleleft_newitem_label" => "+ New employee",
  "ee_extlink_link"             => " <a href=\"%s\" class=\"filelink\" target=\"_blank\">%s</a>",
  "ee_intlink_link"             => " (<a href=\"%s\" class=\"filelink\">%s</a>)",
  "ee_link_scope_none_label"    => "",
  "ee_link_scope_local_label"   => "The link refers to the current website.",
  "ee_link_scope_global_label"  => "Der Link refers to the website '%s'.",
  'ee_decimal_point' => '.',

  // Main
  "ee_function_label" => "Administer employees",
  "ee_function_new_label" => "CREATE&nbsp;EMPLOYEE",
  "ee_function_new_label2" => "Enter data of the new employee",
  "ee_function_edit_label" => "EDIT&nbsp;EMPLOYEE",
  "ee_function_edit_label2" => "",
  "ee_function_list_label" => "List of employees",
  "ee_function_list_label2" => "Created employees",

  // List
  'ee_site_label'               => '<b>Active web filter</b>:<br /><span class="fontsize11">Employees to the Website <b>\'%s\'</b> are displayed...</span>',

  // Filter
  'ee_filter_active_label'   => '<b>Active data filter</b>: <span class="fontsize11">%s contains <b title="%s">\'%s\'</b></span>',
  'ee_filter_inactive_label' => '<i>No active data filter</i>',
  'ee_filter_type_name'       => 'Name',
  'ee_filter_type_email'      => 'E-mail',
  'ee_filter_type_specialism' => 'Specialism',
  'ee_filter_type_function'   => 'Function',
  'ee_filter_type_job_title'  => 'Job title',
  'ee_filter_type_department' => 'Department',
  'ee_filter_type_location'   => 'Location',

  // Messages
  "ee_message_no_employee" => "No employees defined",
  "ee_message_create_success" => "Employee has been created",
  "ee_message_update_success" => "Employee has been edited",
  "ee_message_move_success" => "Employee has been moved",
  "ee_message_delete_success" => "Employee has been deleted",
  "ee_message_invalid_url_protocol" => "Invalid protocol for external link! Possible protocols: %s",
  'ee_message_no_employee_with_filter' => 'No employees found with data filter text: <br />&quot;%s&quot;',

  // Employee fields
  'ee_firstname_label'    => 'First Name',
  'ee_lastname_label'     => 'Last Name',
  'ee_staff_number_label' => 'Staff number',
  'ee_f_id_label'          => 'Salutation',
  'ee_f_id_options'        => array(
    1 => 'Mrs.',
    2 => 'Mr.',
   ),
  'ee_title_label'        => 'Title',
  'ee_company_label'      => 'Company',
  'ee_initials_label'     => 'Initials',
  'ee_country_label'      => 'Country',
  'ee_zip_label'          => 'ZIP',
  'ee_city_label'         => 'City',
  'ee_address_label'      => 'Address',
  'ee_phone_label'        => 'Phone',
  'ee_phone_direct_dial_label' => 'Phone direct dial',
  'ee_fax_label'          => 'Fax',
  'ee_fax_direct_dial_label' => 'Fax direct dial',
  'ee_mobile_phone_label' => 'Mobile phone',
  'ee_mobile_phone_direct_dial_label' => 'Mobile phone direct dial',
  'ee_email_label'        => 'E-mail',
  'ee_room_label'         => 'Room',
  'ee_department_label'   => 'Department',
  'ee_job_title_label'    => 'Job title',
  'ee_function_label'     => 'Function',
  'ee_specialism_label'   => 'Specialism',
  'ee_hourly_rate_label'  => 'Hourly rate',
  'ee_location_label'     => 'Location',

  "end",""));

$_LANG2['ee'] = array_merge($_LANG2['ee'], array(

  // List
  "ee_box_label" => "Employee",
  "ee_delete_label" => "Delete employee",
  "ee_delete_question_label" => "Do you really want to delete this employee?",
  "ee_move_up_label" => "Move this employee upwards",
  "ee_move_down_label" => "Move this employee downwards",
  "ee_move_label" => "Move this employee",
  "ee_content_label" => "Edit employee",
  'ee_name_label' => 'Name',
  'ee_email_label' => 'E-mail',

  // Filter
  'ee_filter_label'        => 'Apply filter',
  'ee_filter_reset_label'  => 'Reset filter',
  'ee_show_change_filter_label' => 'Change filter',
  'ee_filter_text1'        => 'to',
  'ee_filter_text2'        => 'contains',
  'ee_button_filter_label' => 'filter',
  'ee_filter_choose_option'  => 'Choose option',

  // Form
  "ee_title1_label" => "Title of the box",
  "ee_title2_label" => "META-information of the box (only visible to search engines!)",
  "ee_title3_label" => "Title",
  "ee_text1_label" => "Text in the box",
  "ee_text2_label" => "Text",
  "ee_text3_label" => "Text",
  "ee_image1_label" => "Image of the box - small",
  "ee_image2_label" => "Image of the box - entire surface",
  "ee_image3_label" => "Image",
  "ee_link_label" => "Landing page",
  "ee_intlink_label" => "Internal link",
  "ee_extlink_label" => "External link",
  "ee_extlink_text"  => "(\"http://...\" - used only, if internal link was not specified)",
  "ee_properties_label" => "Properties of the box",
  "ee_norandom_label" => "Box should NOT be displayed randomly!",
  "ee_image_alt_label" => "Image of the employee",
  'ee_employee_data_change_label' => 'Change employee data',
  'ee_employee_data_label' => 'Employee data',
  'ee_show_change_display_behaviour' => 'Change display behaviour of box',

  "end",""));

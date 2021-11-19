<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2018-10-08 14:25:53 +0200 (Mo, 08 Okt 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['ln'])) $_LANG2['ln'] = array();

$_LANG = array_merge($_LANG, array(

  'modtop_ModuleLeadManagementClient' => 'Add/Edit lead',
  "ln_moduleleft_newitem_label" => "+ New lead",

  'ln_message_no_site_campaigns' => 'No campaigns defined on this site!',
  'ln_message_save_form_errors'  => 'Error while saving data. Please check: %s',
  'ln_message_lead_taken_success' => 'Lead has been assigned successfully.',
  'ln_message_edit_status_success' => 'Status/Appointment saved successfully.',
  'ln_message_edit_status_no_data' => 'There is no data to save.',
  'ln_message_edit_lead_success' => 'Lead data saved successfully.',
  'ln_message_edit_campaign_success' => 'Campaign data saved successfully.',

  'ln_client_data_title' => 'Lead data',
  'ln_campaign_data_title' => 'Campaign data',
  'ln_appointment_title' => 'Appointment title',
  'ln_appointment_text' => 'Appointment description',
  'ln_appointment_date' => 'Appointment date (DD.MM.YYYY)',
  'ln_appointment_time' => 'Appointment time (hh:mm:ss)',
  'ln_campaign_data_info' => 'Put in special data of campaign &quot;%s&quot;',
  'ln_date_on_label' => 'Appointment on',
  'ln_client_newsletter_confirmed_recipient_label' => 'Newsletter recipient', // (double-opt-in finished)

  // form errors
  'ln_message_invalid_input_number'    => 'Field &quot;%s&quot; requires a number!',
  'ln_message_invalid_min_length'      => 'Field &quot;%s&quot; requires at least %s characters!',
  'ln_message_invalid_max_length'      => 'Field &quot;%s&quot; allows a maximum character length of %s!',
  'ln_message_invalid_min_value'       => 'Field &quot;%s&quot; requires a minimum value of %s!',
  'ln_message_invalid_max_value'       => 'Field &quot;%s&quot; allows a maximum value of %s!',
  'ln_message_dependency_failure'      => 'Field &quot;%s&quot; depends on field &quot;%s&quot;!',
  'ln_message_invalid_mail'            => 'E-mail address in field &quot;%s&quot; is not valid!',
  'ln_message_invalid_date'            => 'Date in field &quot;%s&quot; is not valid (DD.MM.YYYY)!',
  'ln_message_invalid_time'            => 'Time in field &quot;%s&quot; is not valid (HH:MM:SS)!',
  'ln_message_invalid_birthday'        => 'Date in field &quot;%s&quot; is not valid (DD.MM.YYYY)!',
  'ln_message_invalid_future_birthday' => 'Field &quot;%s&quot; can not have a date in the future!',
  'ln_message_incomplete_input'        => 'Please complete all mandatory fields! (with * marked fields)',
  'ln_message_required_field'          => 'Please fill out the field &quot;%s&quot;.',

  'ln_combobox_please_choose' => 'Please choose...',
  'ln_foas' => array ( 0 => '', 1 => 'Mrs.', 2 => 'Mr.', 3 => 'Company' ),
  'ln_checkbox_label' => array ( 0 => 'no', 1 => 'yes' ),
  'ln_element_labels' => array(
    1 => 'Company',
    2 => 'Function',
    3 => 'Salutation',
    4 => 'Pre nominal title',
    5 => 'Forename',
    6 => 'Surname',
    7 => 'Geburtstag',
    8 => 'Country',
    9 => 'Zip',
    10 => 'City',
    11 => 'Address',
    12 => 'Phone',
    13 => 'E-Mail Address',
    14 => 'Newsletter subscription',
    15 => 'Post nominal title',
    16 => 'Agreement to data processing',
    17 => 'Mobile',
  ),

  // export labels
  'ln_campaign_label' => 'Campaign',
  'ln_lead_status_label' => 'Status',
  'ln_lead_status_text_label' => 'Status text',
  'ln_create_date_time' => 'Createdate',
  'ln_change_date_time' => 'Changedate',
  'ln_assigned_user' => 'Assigned user',
  'ln_assigned_user_deleted_label' => '[deleted]',
  'ln_internal_id' => 'Internal ID',
  'ln_export_file_name' => 'Leadexport-Lead%s-%s',
  // export labels & additional static data
  'ln_competing_company_label' => 'Compitiors',
  'ln_doc_email_label'         => 'Wants documents via e-mail',
  'ln_doc_post_label'          => 'Wants documents via post',
  'ln_lead_appointment'        => 'Wants appointment',
  'ln_data_origin_label'       => 'Data origin',

  'end',''));

$_LANG2['ln'] = array_merge($_LANG2['ln'], array(

  'ln_export_data' => 'Export data',
  'ln_list_label' => 'Add/Edit lead',
  'ln_list_label2' => 'Add/Edit lead of specified campaign',
  'ln_select_campaign_label' => 'Lead to campaign',
  'ln_show_client_data' => 'Edit lead data',
  'ln_show_campaign_data' => 'Edit campaign data',
  'ln_status_data_appointment_title' => 'Status data and appointments',
  'ln_show_status_data' => 'Edit status data',
  'ln_status_label' => 'Select status',
  'ln_status_text_label' => 'Status text (optional)',
  'ln_status_data_info' => 'Put in lead\'s current status.',
  'ln_client_data_info' => 'Put in all relevant information about the lead.',
  'ln_lead_of_campaign_label' => 'Lead of campaign',
  'ln_appointment_finished_label' => 'Finished',
  'ln_appointment_title_label' => 'Title',
  'ln_appointment_date_label' => 'Appointment',
  'ln_appointment_time_label' => 'Time',
  'ln_appointment_text_label' => 'Note / Description',
  'ln_appointments_title' => 'Open appointments',
  'ln_appointment_datetime_label' => 'Appointment date',
  'ln_appointment_createdatetime_label' => 'Create time',
  'ln_appointment_creator_label' => 'Creator',
  'ln_time_input' => 'Set time',
  'ln_change_assignment_label' => 'Change user assignment',
  'ln_assigned_user_label' => 'Assigned user',
  'ln_please_choose_label' => 'Please choose...',
  'ln_appointment_status_urgent' => 'urgent',
  'ln_client_create_date_time_label' => 'Createdate',
  'ln_client_change_date_time_label' => 'Changedate',
  'ln_client_id_label' => 'Lead ID',
  'ln_client_company_label' => 'Company',
  'ln_client_name_label' => 'Name/Person',
  'ln_client_address_label' => 'Address',
  'ln_client_contact_label' => 'Phone number/Email',
  'ln_process_status' => 'Save status/appointment',
  'ln_process_status_and_return' => 'Save status/appointment &amp; return',
  'ln_process_client' => 'Save data',
  'ln_process_client_and_return' => 'Save data &amp; return',
  'ln_process_campaign' => 'Save data',
  'ln_process_campaign_and_return' => 'Save data &amp; return',

  // additional static data
  'ln_additional_data_title'   => 'Lead additional data',
  'ln_additional_data_info'    => 'Edit optional lead data.',

  'end',''));
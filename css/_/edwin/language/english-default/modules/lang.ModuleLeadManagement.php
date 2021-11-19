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

if (!isset($_LANG2['ld'])) $_LANG2['ld'] = array();

$_LANG = array_merge($_LANG, array(

  'm_mode_name_mod_leadmgmt'      => 'Manage leads',
  'modtop_ModuleLeadManagement'   => 'Infocenter',

  'ld_system_label' => 'System',
  'ld_lead_taken'   => 'Lead has been assigned to %s',
  'ld_lead_edited'   => 'Lead has been changed.',
  'ld_client_data_edited'   => 'Lead data have been changed.',
  'ld_campaign_data_edited'   => 'Campaign data have been changed.',
  'ld_status_history_open' => 'Open',
  'ld_status_history_finished' => 'Finished',

  // list
  'ld_site_label' => '<strong>Active web filter</strong>:<br /><span class="fontsize11">Campaigns of the website <strong>\'%s\'</strong> are displayed...</span>',
  'ld_show_campaign_type_label' => 'Show campaigns of "%s"',
  'ld_campaign_lead_status_label' => 'Number of leads with status &quot;%s&quot;',
  'ld_campaign_stat' => '<span class="label label-default">%s</span>   active: <span class="label label-default">%s</span>   archived: <span class="label label-default">%s</span>',
  'ld_message_no_site_campaigns' => 'No campaigns defined on this site or no data has been captured!',

  // general messages
  'ld_message_expired_appointment'  => 'One expired appointment! <a href="%s" class="icon_details" title="Details"><span class="q2e_icon_edit"></span></a>',
  'ld_message_expired_appointments' => '%s expired appointments! <a href="%s" class="icon_details" title="Details"><span class="q2e_icon_edit"></span></a>',
  'ld_message_urgent_appointment'  => 'One urgent appointment! <a href="%s" class="icon_details" title="Details"><span class="q2e_icon_edit"></span></a>',
  'ld_message_urgent_appointments' => '%s urgent appointments! <a href="%s" class="icon_details" title="Details"><span class="q2e_icon_edit"></span></a>',
  'ld_message_next_appointment'     => 'Next appointment in %s minutes (%s) with \'%s\'. <a href="%s" class="icon_details" title="Details"><span class="q2e_icon_edit"></span></a>',
  'ld_message_next_appointment_one' => 'Next appointment in one minute (%s) with \'%s\'. <a href="%s" class="icon_details" title="Details"><span class="q2e_icon_edit"></span></a>',
  'ld_message_lead_edit_success' => 'Lead successfully edited.',
  'ld_message_lead_edit_not_possible' => 'It is not possible to edit this lead!',
  'ld_message_lead_delete_success' => 'Lead successfully deleted.',
  'ld_message_lead_edit_no_data' => 'There is no data to save/edit.',

  // AJAX response messages
  'ld_message_choose_date' => 'Please choose date first (DD.MM.YYYY)!',
  'ld_message_future_date' => 'The date must not be in the past!',
  'ld_notime_question_label' => 'No appointments available on this day. Do you want to create an appointment on the end of this day?',

  'ld_foas' => array ( 1 => 'Mrs.', 2 => 'Mr.', 3 => 'Company' ),

  'ld_CCompany_label' => 'Company',
  'ld_FK_FID_label' => 'Salutation',
  'ld_CPosition_label' => 'Position',
  'ld_CTitlePre_label' => 'Pre nominal title',
  'ld_CTitlePost_label' => 'Post nominal title',
  'ld_CFirstname_label' => 'First Name',
  'ld_CLastname_label' => 'Last Name',
  'ld_CAddress_label' => 'Address',
  'ld_CZIP_label' => 'ZIP',
  'ld_CCity_label' => 'City',
  'ld_CCountry_label' => 'Country',
  'ld_CEmail_label' => 'Email address',
  'ld_CBirthday_label' => 'Birthday',
  'ld_CPhone_label' => 'Phone',
  'ld_CMobilePhone_label' => 'Mobile',
  'ld_CASDLastCS2ID_label' => 'Status',
  'ld_CGID_label' => 'Campaign',
  'ld_CGName_label' => 'Campaign',
  'ld_CNewsletterConfirmedRecipient_label' => 'N',
  'ld_CDataPrivacyConsent_label' => 'D',
  'ld_CCreateDateTime_label' => 'Creationdate',
  'ld_CChangeDateTime_label' => 'Changedate',
  'ld_CID_label' => 'Internal ID',
  'ld_CGLID_label' => 'Lead ID',
  'ld_FK_CGSID_label' => 'Status ID',
  'ld_FK_UID_label' => 'User',
  'ld_CGLADateTime_label' => 'Appointment',
  'ld_CGLATitle_label' => 'Title',
  'ld_CGLAText_label' => 'Description',
  'ld_CGSID_label' => 'Status',
  'ld_CGLDataOrigin_label' => 'Data origin',
  'ld_leadAgent_label' => 'Assigned user',

  // Order options
  'ld_filter_ignored_label' => 'none',
  'ld_order_ignored_label'  => 'none',
  'ld_order_asc_label'      => '%s ascending',
  'ld_order_desc_label'     => '%s descending',

  "lg_lead_no_upload_label" => "<span class=\"smalltxt\" href=\"%s\">file not available</span>",
  "lg_lead_upload_link" => "<a class=\"filelink smalltxt\" href=\"%s\">%s</a> ",
  "lg_lead_upload_delete_link" => "<a style=\"display:none\" href=\"%s\"><span class=\"q2e_icon_delete\" title=\"Delete file\"></span></a>",

  'end',''));

$_LANG2['ld'] = array_merge($_LANG2['ld'], array(

  'ld_list_label' => 'Statistics and campaigns',
  'ld_list_label2' => 'View statistics and list of all campaigns',
  'ld_general_statistics_label' => 'General statistic',
  'ld_number_of_clients_label' => 'Total leads',
  'ld_cg_type_clients_label' => 'Number of leads',
  'ld_cg_clients_label' => 'Number of leads of this campaign',
  'ld_cg_show_status_clients' => 'Show leads with this status',
  'ld_show_campaign_details_label' => 'Show campaign details',
  'ld_number_of_campaigns_label' => 'Number of campaigns',
  'ld_filter_campaigns_label' => 'Filter campaigns',
  'ld_old_campaigns_label' => 'old campaigns',
  'ld_new_campaigns_label' => 'new campaigns',
  'ld_show_label' => 'show',
  'ld_status_history_user_label' => 'User',
  'ld_status_history_status_label' => 'Status',
  'ld_status_history_datetime_label' => 'Date-Time',
  'ld_status_history_text_label' => 'Status text/Appointment',

  // appointments of selected day
  'ld_time_label1' => 'Morning',
  'ld_time_label2' => 'Afternoon',
  'ld_time_label3' => 'Evening',
  'ld_date_on_label' => 'Appointment on',

  'ld_status_history_title' => 'Status history',

  'end',''));

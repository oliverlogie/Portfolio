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

if (!isset($_LANG2['fum'])) $_LANG2['fum'] = array();

$_LANG = array_merge($_LANG,array(

  "modtop_ModuleFrontendUserManagement" => "Users",
  "mod_frontendusermgmt_new_label" => "Create&nbsp;user",
  "mod_frontendusermgmt_edit_label" => "Edit&nbsp;user",
  "m_mode_name_mod_frontendusermgmt" => "Administer the existing users",
  "m_moduleleft_newitem_label" => "+ New user",
  "m_moduleleft_export_label" => "CSV-Export",

  "fu_function_label" => "Administer user",
  "fu_function_new_label" => "CREATE&nbsp;USER",
  "fu_function_new_label2" => "Enter the new user data.",
  "fu_function_edit_label" => "EDIT&nbsp;USER",
  "fu_function_edit_label2" => "Edit the data of the selected user",

  "fu_submit_label" => "&nbsp;&nbsp;&nbsp;&nbsp;update",
  "fu_message_newitem_success" => "User has been created",
  "fu_message_edititem_success" => "User has been edited",
  "fu_message_deleteitem_success" => "User has been deleted",
  "fu_message_insufficient_input" => "Please, at least, enter the user name and the e-mail adress!",
  "fu_message_password_mismatch" => "The password does not match the password confirmation!",
  "fu_message_invalid_email" => "Invalid e-mail address specified!",
  "fu_message_invalid_zip" => "Invalid ZIP code given! Please use only numbers!",
  "fu_message_invalid_phone" => "Invalid phone number given! Enter digits, the + sign, brackets and whitspaces only!",
  "fu_message_invalid_mobile" => "Invalid mobile phone number given! Enter digits, the + sign, brackets and whitspaces only!",
  "fu_message_invalid_fax" => "Invalid fax phone number given! Enter digits, the + sign, brackets and whitspaces only!",
  "fu_message_invalid_too_short" => "Insecure password - The password must be at least %d characters!",
  "fu_message_invalid_too_weak" => "Insecure password - The password must be at least %d characters!",
  "fu_message_invalid_too_weak_spacer" => ", ",
  "fu_message_invalid_too_weak_lastspacer" => " and ",
  "fu_message_user_exists" => "This user name already exists!",
  "fu_message_email_exists" => "This e-mail address already exists!",
  "fu_password_character_type" => array ( "big" => "capital letters", "small" => " lower case letters", "numbers" => "numbers" ),

  // list
  "fu_filter_type_nick"         => "User name",
  "fu_filter_type_email"        => "E-mail adress",
  "fu_filter_type_group"        => "User group",
  "fu_results_showpage_current" => "<span class=\"mn_site_active_links\">%s</span>",
  "fu_results_showpage_other"   => "<a href=\"%s\" class=\"mm_site_sel_link\">%s</a>",
  "fu_filter_active_label"      => "<b>Active user filter</b>: <span class=\"fontsize11\">%s consists <b>'%s'</b>...</span>",
  "fu_filter_inactive_label"    => "<i>No active user filter</i>",

  // for csv export of frontend user data
  "fu_title_label"          => "Title",
  "fu_firstname_label"      => "First name",
  "fu_middlename_label"     => "Middle name",
  "fu_lastname_label"       => "Surname",
  "fu_street_label"         => "Street",
  "fu_country_label"        => "Country",
  "fu_zip_label"            => "Postal code",
  "fu_city_label"           => "City",
  "fu_email_label"          => "E-mail adress",
  "fu_phone_label"          => "Telephone",
  "fu_mobile_label"         => "Mobile phone",
  "fu_fax_label"            => "Fax",
  "fu_department_label"     => "Department",
  "fu_nick_label"           => "User name",
  "fu_birthday_label"       => "Date of birth",
  "fu_company_label"        => "Company",
  "fu_company_select_label" => "Company (internal)",
  "fu_position_label"       => "Position",
  "fu_createdate_label"     => "Date of registration",
  "fu_lastchange_label"     => "Last changes",
  "fu_newsletter_label"     => "Newsletter",
  "fu_yes_label"            => "Yes",
  "fu_countlogins_label"    => "Number of logins",
  "fu_lastlogin_label"      => "Last Login",
  "fu_foa_label"            => "Form of address",
  "fu_foas" => array(1 => "Mrs.", 2 => "Mr.", 3 => "Company"),

  "fu_FUNick_label" => "User name",
  "fu_FUEmail_label" => "E-mail adress",
  "fu_FK_FUGID_label" => "User group",

  "end",""));

$_LANG2['fum'] = array_merge($_LANG2['fum'], array(

  "fu_function_list_label"  => "List of users",
  "fu_function_list_label2" => "Created users",

  "fu_content_label"             => "Edit user",
  "fu_delete_label"              => "Delete user",
  'fu_ut_edit_label'             => 'Administer user area',
  "fu_deleteitem_question_label" => "Do you really want to delete this user?",
  "fu_auto_password_label"       => "Automatically generated password",
  "fu_groups_label"              => "Belonging to user group / s",
  "fu_groups_group"              => "User group",
  "fu_groups_desc"               => "Description",
  "fu_groups_sites"              => "Assigned sites",
  "fu_groups_sites_longdesc"     => "The members of the group have access to the web sites below. They are displayed in the login area of this website in the user list.",

  "fu_data_label"           => "Data of the user",
  "fu_title_label"          => "Title",
  "fu_firstname_label"      => "First name",
  "fu_middlename_label"     => "Middle name",
  "fu_lastname_label"       => "Surname",
  "fu_street_label"         => "Street",
  "fu_country_label"        => "Country",
  "fu_zip_label"            => "Postal code",
  "fu_city_label"           => "City",
  "fu_email_label"          => "E-mail address",
  "fu_phone_label"          => "Telephone",
  "fu_mobile_label"         => "Mobile phone",
  "fu_nick_label"           => "User name",
  "fu_birthday_label"       => "Date of birth",
  "fu_company_label"        => "Company",
  "fu_company_none_label"   => "none",
  "fu_position_label"       => "Position",
  "fu_password_label"       => "Password",
  "fu_password2_label"      => "Repeat password",
  "fu_newsletter_label"     => "Newsletter",
  "fu_foa_label"            => "Form of address",
  "fu_uid_label"            => "VAT registration number",
  "fu_fax_label"            => "Fax",
  "fu_department_label"     => "Department",

  "end",""));


<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2018-07-13 09:25:43 +0200 (Fr, 13 Jul 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin
 * @copyright (c) 2012 Q2E GmbH
 */

if (!isset($_LANG2['fu_cp'])) $_LANG2['fu_cp'] = array();

$_LANG = array_merge($_LANG,array(

  "modtop_ModuleFrontendUserManagementCompany" => "Companies",
  "fu_cp_function_edit_label"      => "EDIT&nbsp;COMPANY",
  "fu_cp_function_edit_label2"     => "Edit the data of the selected company",
  "fu_cp_function_new_label"       => "CREATE&nbsp;COMPANY",
  "fu_cp_function_new_label2"      => "Enter the new company data",
  "fu_cp_moduleleft_newitem_label" => "+ New company",

  "fu_cp_message_create_success"     => "Company successfully created",
  "fu_cp_message_deleteitem_success" => "Company successfully deleted",
  "fu_cp_message_save_failure"       => "Error saving company",
  "fu_cp_message_update_success"     => "Company successfully edited",

  // model field = grid column labels
  "fu_cp_FUCName_label"           => "Name",
  "fu_cp_FUCStreet_label"         => "Street",
  "fu_cp_FUCPostalCode_label"     => "ZIP",
  "fu_cp_FUCCity_label"           => "City",
  "fu_cp_FUCEmail_label"          => "E-mail",
  "fu_cp_FK_CID_Country_label"    => "Country",
  "fu_cp_FUCPhone_label"          => "Phone",
  "fu_cp_FUCFax_label"            => "Fax",
  "fu_cp_FUCWeb_label"            => "Web",
  "fu_cp_FUCNotes_label"          => "Notes",
  "fu_cp_FUCType_label"           => "Type",
  "fu_cp_FUCImage_label"          => "Image",
  "fu_cp_FUCVatNumber_label"      => "VAT number",
  "fu_cp_FUCCreateDatetime_label" => "Create date and time",
  "fu_cp_FUCChangeDatetime_label" => "Last modified date and time",
  "fu_cp_FK_FUCAID_Area_label"    => "Area",

  "fu_cp_area_none_label"       => "kein",

  "end",""));

$_LANG2['fu_cp'] = array_merge($_LANG2['fu_cp'], array(

  "fu_cp_list_label"  => "List of companies",
  "fu_cp_list_label2" => "Created companies",
  "fu_cp_deleteitem_question_label" => "Do you really want to delete this company?",

  "end",""));
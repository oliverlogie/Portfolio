<?php
  /**
   * Lang: EN
   *
   * $LastChangedDate: 2014-03-31 14:07:34 +0200 (Mo, 31 Mär 2014) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Benjamin Ulmer
   * @copyright (c) 2011 Q2E GmbH
   */

if (!isset($_LANG2["rm"])) $_LANG2["rm"] = array();

$_LANG = array_merge($_LANG,array(

  "m_mode_name_mod_reseller" => "Reseller administration",

  "rm_function_label1" => "Administer reseller",
  "rm_function_label2" => "Existing structure and resellers will be overwritten.!",
  "rm_file_reseller_label" => "Upload new reseller CSV file.",
  "rm_file_reseller_info_label" => "This file contains general information about the reseller and its areas of responsibility.",
  "rm_file_structure_label" => "Upload new structure CSV file",
  "rm_file_structure_info_label" => "This file contains the connections between all areas of responsibility.",
  "rm_export_reseller_label" => "Export reseller",
  "rm_export_structure_label" => "Export structure",
  "rm_import_submit_label" => "Import",
  "rm_message_invalid_assignation" => "This assignment is invalid.",
  "rm_message_no_file_structure" => "Structural data have not been changed, because no file was selected!.",
  "rm_message_no_file_reseller" => "Reseller data has not been changed, because no file was selected!",
  "rm_message_structure" => "No structural data available! First import CSV structure.",
  "rm_message_success" => "Data has been saved successfully",
  "rm_message_columns_error" => "The CSV file has missing columns or empty fields. Line:",
  "rm_message_invalid_email" => "E-mail address &quot;%s&quot; is not valid!",
  "rm_message_image_upload_success" => "The image has been uploaded successfully.",
  "rm_message_image_upload_error"   => "Invalid image file, please provide a valid image file as upload.",
  "rm_message_image_delete_success" => "Image deleted successfully.",
  "rm_message_image_delete_error"   => "Can not delete the specified image, it does not exist.",
  "rm_message_no_images_uploaded"   => "Currently there are no images available.",
  "rm_name_label" => "Name",
  "rm_address_label" => "Adress",
  "rm_postal_code_label" => "Postal code",
  "rm_city_label" => "City",
  "rm_country_label" => "Country",
  "rm_call_number_label" => "Telephone number",
  "rm_fax_label" => "Fax",
  "rm_email_label" => "E-mail",
  "rm_web_label" => "Web",
  "rm_notes_label" => "Notes",
  "rm_type_label" => "Type",
  "rm_image_label" => "Image",
  "rm_assignation_label" => "Assignation",
  "rm_category_label" => 'Category',

"end",""));

$_LANG2["rm"] = array_merge($_LANG2["rm"], array(

  "rm_image_upload_label" => "Image upload",
  "rm_image_info_label"   => "If you provided an image name in your reseller csv data, upload it here.<br/><b>Note: </b>When uploading a new image, any existing image with the same name will be replaced.",
  "rm_uploaded_images_label" => "Available images",
  "rm_image_delete_question_label" => "Do you really want to delete this image?",

  "end",""));

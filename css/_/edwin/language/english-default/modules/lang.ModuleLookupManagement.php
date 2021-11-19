<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2013-02-18 10:33:34 +0100 (Mo, 18 Feb 2013) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2013 Q2E GmbH
 */

if (!isset($_LANG2["lu"])) $_LANG2["lu"] = array();

$_LANG = array_merge($_LANG,array(

  "m_mode_name_mod_lookupmgmt" => "Lookup management",

  "lu_message_image_upload_success"        => "The image has been uploaded successfully.",
  "lu_message_image_upload_error"          => "Invalid image file, please provide a valid image file as upload.",
  "lu_message_image_delete_success"        => "Image deleted successfully.",
  "lu_message_image_delete_error"          => "Can not delete the specified image, it does not exist.",
  "lu_message_no_images_uploaded"          => "Currently there are no images available.",
  "lu_message_failure_no_file"             => "Please specify a valid CSV file ( *.csv ).",
  "lu_message_failure_file_not_readable"   => "Internal error: Could not read specified CSV file. Please try again or contact the website admin.",
  "lu_message_dependencies_import_failure" => "No dependencies imported.",
  "lu_message_dependencies_import_success" => "Successfully imported the product-answer dependencies.",
  "lu_message_product_import_success"      => "No products imported.",
  "lu_message_product_import_success"      => "Successfully imported the products.",

"end",""));

$_LANG2["lu"] = array_merge($_LANG2["lu"], array(

  "lu_function_label"               => "Manage Lookup",
  "lu_function_label2"              => "Lookup configuration CSV and with image upload",
  "lu_file_dependencies_label"      => "Product-answer dependency import",
  "lu_file_dependencies_info_label" => "<b>Structure of the product-answer matrix:</b><br/>The first column of your file is expected to contain product IDs. All further columns contain the specified weights ( number OR {number}*{from}-{to} ).<br/><ol class=\"padding_l_20\"><li>answer IDs ( columns without IDs will be ignored )</li><li>Answer description or additional information ( i.e. the question )</li><li>human readable answer text ( simplifies weight assignment )</li></ol>",
  "lu_file_products_label"          => "Product configuration",
  "lu_file_products_info_label"     => "The first two rows of your CSV file are expected to contain:<br/><ol class=\"padding_l_20\"><li>database column names</li><li>human readable field description</li></ol>",
  "lu_import_submit_label"          => "import",
  "lu_image_upload_label"           => "Image upload",
  "lu_image_info_label"             => "Please upload product images here.<br/><b>Note: </b>When uploading a new image, any existing image with the same name will be replaced.",
  "lu_uploaded_images_label"        => "Available images",
  "lu_image_delete_question_label"  => "Do you really want to delete this image?",

  "end",""));
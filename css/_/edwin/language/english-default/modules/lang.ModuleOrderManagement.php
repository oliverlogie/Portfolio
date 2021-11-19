<?php
/**
 * Lang: DE
 *
 * $LastChangedDate: 2018-10-08 14:25:53 +0200 (Mo, 08 Okt 2018) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2['om'])) $_LANG2['om'] = array();

$_LANG = array_merge($_LANG,array(

  "m_mode_name_mod_ordermgmt" => "Order administration",

  "om_function_label" => "Administrate orders",
  "om_last_export_label" => "Last export on%s at%s from %s ",
  "om_export_label" => "CSV-Export",
  "om_site_label" => "<b>Active web filter</b>:<br /><span class=\"fontsize11\">orders of the website <b>'%s'</b> are displayed...</span>",

  "om_id_label" => "ID",
  "om_total_price_label" => "Price (without shipping costs)",
  "om_total_tax_label" => "VAT",
  "om_total_price_without_tax_label" => "Price (VAT excluded)",
  "om_total_price_including_shipping_cost_label" => "Price",
  "om_shipping_cost_label" => "Shipping costs",
  "om_payment_type_label" => "Payment type",
  "om_createdate_label" => "Creation date",
  "om_changedate_label" => "Modification date",
  "om_items_label" => "Products",
  "om_company_label" => "Company",
  "om_firstname_label" => "First Name",
  "om_lastname_label" => "Last Name",
  "om_email_label" => "E-mail",
  "om_position_label" => "Position",
  "om_foa_label" => "Salutation",
  "om_title_label" => "Title",
  "om_birthday_label" => "Date of birth",
  "om_country_label" => "Country",
  "om_zip_label" => "Postal code",
  "om_city_label" => "City",
  "om_address_label" => "Address",
  "om_phone_label" => "Telephone",
  "om_mobile_phone_label" => "Mobile",
  "om_foas" => array ( 1 => "Mrs.", 2 => "Mr.", 3 => "Company" ), // set to same value as 'c_sc_foas'
  "om_payment_type_labels" => array( 1 => "Paypal"), // see $_CONFIG['sc_payment_types']

  "om_message_no_client" => "No orders in system",

  "end",""));

$_LANG2['om'] = array_merge($_LANG2['om'], array(

  "om_list_label" => "List of orders",
  "om_list_label2" => "List of orders on this website",

  "end", ""));


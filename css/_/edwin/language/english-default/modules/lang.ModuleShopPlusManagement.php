<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2014-04-04 11:54:55 +0200 (Fr, 04 Apr 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2["op"])) $_LANG2["op"] = array();

$_LANG = array_merge($_LANG, array(

  "mod_shopplusmgmt_main_edit_label"  => "View order",
  "m_mode_name_mod_shopplusmgmt"      => "Administer shop plus",
  "modtop_ModuleShopPlusManagement"   => "Shop plus",

  "op_message_filter_empty" => "No results found. Please reset filter!",
  "op_message_item_paid"    => "Order successfully marked as paid.",

  "op_export_label"          => "Export",
  "op_foas"                  => array ( 1 => "Mrs.", 2 => "Mr.", 3 => "Company" ),
  "op_function_edit_label"   => "VIEW&nbsp;ORDER",
  "op_function_edit_label2"  => "View products, invoice- and shipping data",
  "op_order_item_option_line"      => "<tr><td></td><td></td><td colspan=\"5\">%s %s %s</td></tr>",
  "op_order_info_checkbox_0_label" => "no",
  "op_order_info_checkbox_1_label" => "yes",
  "op_results_showpage_current"    => "<span class=\"mn_site_active_links\">%s</span>",
  "op_results_showpage_other"      => "<a href=\"%s\" class=\"mm_site_sel_link\">%s</a>",
  "op_status_label"          => array(1 => "paid", 2 => "outstanding"),

  // csv output labels ( copy of $_LANG2 values )
  "op_order_id_label"                      => "Order number",
  "op_order_date_create_label"             => "Createdate",
  "op_order_date_change_label"             => "Changedate",
  "op_order_total_price_label"             => "Price",
  "op_order_total_tax_label"               => "VAT",
  "op_order_total_price_without_tax_label" => "Price (VAT excluded)",
  "op_order_status_label"                  => "Status",
  "op_order_shipping_cost_label"           => "Shipping costs",
  "op_order_shipping_type_label"           => "Shipment mode",
  "op_order_payment_cost_label"            => "Payment costs",
  "op_order_payment_type_label"            => "Payment type",
  "op_order_sum_label"                     => "Sum",

  "op_order_customer_label"           => "Invoice",
  "op_order_customer_company_label"   => "Company",
  "op_order_customer_position_label"  => "Position",
  "op_order_customer_foa_label"       => "Salutation",
  "op_order_customer_title_label"     => "Title",
  "op_order_customer_firstname_label" => "First Name",
  "op_order_customer_lastname_label"  => "Last Name",
  "op_order_customer_birthday_label"  => "Birthday",
  "op_order_customer_country_label"   => "Contry",
  "op_order_customer_zip_label"       => "Postal code",
  "op_order_customer_city_label"      => "City",
  "op_order_customer_address_label"   => "Street",
  "op_order_customer_phone_label" => "Phone",
  "op_order_customer_email_label" => "e-mail",
  "op_order_customer_text1_label" => "Text 1",
  "op_order_customer_text2_label" => "Text 2",
  "op_order_customer_text3_label" => "Text 3",
  "op_order_customer_text4_label" => "Text 4",
  "op_order_customer_text5_label" => "Text 5",

  "op_order_shipping_label"           => "Shipping address",
  "op_order_shipping_company_label"   => "Company",
  "op_order_shipping_position_label"  => "Position",
  "op_order_shipping_foa_label"       => "Sylutation",
  "op_order_shipping_title_label"     => "Title",
  "op_order_shipping_firstname_label" => "First Name",
  "op_order_shipping_lastname_label"  => "Last Name",
  "op_order_shipping_birthday_label"  => "Birthday",
  "op_order_shipping_zip_label"       => "Postal code",
  "op_order_shipping_city_label"      => "City",
  "op_order_shipping_address_label"   => "Street",
  "op_order_shipping_phone_label" => "Phone",
  "op_order_shipping_email_label" => "e-mail",
  "op_order_shipping_text1_label" => "Text 1",
  "op_order_shipping_text2_label" => "Text 2",
  "op_order_shipping_text3_label" => "Text 3",
  "op_order_shipping_text4_label" => "Text 4",
  "op_order_shipping_text5_label" => "Text 5",

  "op_order_items_label"           => "Products",
  "op_order_item_title_label"      => "Label",
  "op_order_item_number_label"     => "Order number",
  "op_order_item_quantity_label"   => "Quantity",
  "op_order_item_sum_label"        => "Total price",
  "op_order_item_unit_price_label" => "Price/unit",
  "op_order_item_price_label"      => "Basic price",

  "op_option_title_label" => "Option",
  "op_option_price_label" => "Option price",

  "op_CPOID_label"             => "Number",
  "op_CPOTotalPrice_label"     => "Sum",
  "op_CPOCreateDateTime_label" => "Date",
  "op_CPOCAddress_label"       => "Street",
  "op_CPOCCity_label"          => "City",
  "op_CPOCCountry_label"       => "Country",
  "op_CPOCFirstname_label"     => "Firstname",
  "op_CPOCLastname_label"      => "Lastname",
  "op_CPOCZIP_label"           => "ZIP",

  "end",""));

$_LANG2["op"] = array_merge($_LANG2["op"], array(

  "op_content_label"          => "View order",
  "op_status_paid_link_label" => "outstanding - mark order as paid?",
  "op_status_paid_link_question_label" => "Really mark order as paid?",

  "op_list_label"           => "Order list",
  "op_list_label2"          => "List of orders",

  "op_order_label"                         => "Overview",
  "op_order_id_label"                      => "Order number",
  "op_order_date_create_label"             => "Createdate",
  "op_order_date_change_label"             => "Changedate",
  "op_order_total_price_label"             => "Price",
  "op_order_total_tax_label"               => "VAT",
  "op_order_total_price_without_tax_label" => "Price (VAT excluded)",
  "op_order_status_label"                  => "Status",
  "op_order_shipping_cost_label"           => "Shipping costs",
  "op_order_shipping_type_label"           => "Shipment mode",
  "op_order_payment_cost_label"            => "Payment costs",
  "op_order_payment_type_label"            => "Payment type",
  "op_order_sum_label"                     => "Sum",
  "op_order_pdf_label"                     => "Invoice PDF",

  "op_order_customer_label"           => "Invoice",
  "op_order_customer_company_label"   => "Company",
  "op_order_customer_position_label"  => "Position",
  "op_order_customer_foa_label"       => "Salutation",
  "op_order_customer_title_label"     => "Title",
  "op_order_customer_firstname_label" => "First Name",
  "op_order_customer_lastname_label"  => "Last Name",
  "op_order_customer_birthday_label"  => "Birthday",
  "op_order_customer_country_label"   => "Country",
  "op_order_customer_zip_label"       => "Postal code",
  "op_order_customer_city_label"      => "City",
  "op_order_customer_address_label"   => "Street",
  "op_order_customer_phone_label" => "Phone",
  "op_order_customer_email_label" => "e-mail",
  "op_order_customer_text1_label" => "Text 1",
  "op_order_customer_text2_label" => "Text 2",
  "op_order_customer_text3_label" => "Text 3",
  "op_order_customer_text4_label" => "Text 4",
  "op_order_customer_text5_label" => "Text 5",

  "op_order_shipping_label"           => "Shipping address",
  "op_order_shipping_company_label"   => "Company",
  "op_order_shipping_position_label"  => "Position",
  "op_order_shipping_foa_label"       => "Salutation",
  "op_order_shipping_title_label"     => "Title",
  "op_order_shipping_firstname_label" => "First Name",
  "op_order_shipping_lastname_label"  => "Last Name",
  "op_order_shipping_birthday_label"  => "Birthday",
  "op_order_shipping_zip_label"       => "Postal code",
  "op_order_shipping_city_label"      => "City",
  "op_order_shipping_address_label"   => "Street",
  "op_order_shipping_phone_label" => "Phone",
  "op_order_shipping_email_label" => "e-mail",
  "op_order_shipping_text1_label" => "Text 1",
  "op_order_shipping_text2_label" => "Text 2",
  "op_order_shipping_text3_label" => "Text 3",
  "op_order_shipping_text4_label" => "Text 4",
  "op_order_shipping_text5_label" => "Text 5",

  "op_order_items_label"           => "Products",
  "op_order_item_title_label"      => "Label",
  "op_order_item_number_label"     => "Order number",
  "op_order_item_quantity_label"   => "Quantity",
  "op_order_item_sum_label"        => "Total price",
  "op_order_item_unit_price_label" => "Price/unit",
  "op_order_item_price_label"      => "Basic price",

  "op_order_info_label" => "Additional information",

  "end",""));


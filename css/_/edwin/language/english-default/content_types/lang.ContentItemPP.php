<?php
/**
 * Lang: EN
 *
 * $LastChangedDate: 2017-10-09 14:04:21 +0200 (Mo, 09 Okt 2017) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

if (!isset($_LANG2["pp"])) $_LANG2["pp"] = array();

$_LANG = array_merge($_LANG, array(

  "pp_attribute_global_title_none" => "none",
  "pp_product_title_header"        => "- %s",

  'pp_message_no_product' => 'Data successfully saved. You have to create at least one product to put that content online.',

  "pp_message_option_delete_success"     => "Option successfully removed.",
  "pp_message_option_existing_failure"   => "This option has been added to the product before.",
  "pp_message_option_create_success"     => "Option successfully added.",
  "pp_message_option_insufficient_input" => "No option selected. Please select an option!",
  "pp_message_option_edit_success"       => "Option updated.",
  "pp_message_option_move_success"       => "Option successfully moved",

  "pp_message_product_create_failure" => "Creating a new product failed.",
  "pp_message_product_create_success" => "Product successfully created.",
  "pp_message_product_delete_success" => "Product successfully removed.",
  "pp_message_product_edit_failure"   => "There exists a product with selected attributes! Please choose different attributes.",
  "pp_message_product_move_success"   => "Product successfully moved.",
  "pp_message_product_max_elements"   => "Maximum number of products reached.",
  "pp_message_product_none_filter"    => "There are not any products matching your filter criteria. <br/>Please change the filter criteria or <a class=\"sn\" href=\"%s\">reset all filters</a> to display products again.",
  "pp_message_product_success"        => "Product successfully updated.",
  "pp_product_message_activation_enabled"  => "Product successfully activated!",
  "pp_product_message_activation_disabled" => "Product successfully deactivated!",
  "pp_product_message_activation_linked_enabled"  => "Product successfully activated! %d linked products activated.",
  "pp_product_message_activation_linked_disabled" => "Product successfully deactivated! %d linked products deactivated.",
  "pp_product_message_show_on_level_activated"    => "Product is displayed within product teaser level.",
  "pp_product_message_show_on_level_deactivated"  => "Product is not displayed within product teaser level.",
  "pp_product_show_on_level_green_label"          => "Product is displayed within product teaser level.",
  "pp_product_show_on_level_red_label"            => "Product is not displayed within product teaser level.",
  "pp_product_additional_data_labels"             => array(),

  "pp_message_failure" => "Duplicate attribute groups selected! Data could not be stored.",

  "pp_product_image1_label" => "Product image",
  "pp_tax_rate_shortname"   => array(1 => "20", 2 => "10"),

  "pp_product_filter_none_label" => "none",
  "pp_product_filter_compare"    => array( "equals"        => "settings equals to general settings",
                                           "different"     => "at least one setting differs from the general settings",
                                           "casePacks"     => "different case packs",
                                           "name"          => "different product name",
                                           "price"         => "different price",
                                           "shippingCosts" => "different shipping costs", ),

  "end",""));

$_LANG2["pp"] = array_merge($_LANG2["pp"], array(

  "pp_additionalfunctions_label" => "Additional functions of general layout area",
  "pp_area_actions_label"        => "Main area actions",
  "pp_attribut_global_label"     => "Attribute groups &amp; price",
  "pp_attribute_global_actions_label" => "Actions",
  "pp_attribut_global_showhide_label" => "Show/hide attribute groups &amp; price",
  "pp_button_submit_label"       => "save",
  "pp_button_new_element_label"  => "+ new product",
  "pp_case_packs_label"          => "Case packs",
  "pp_shipping_costs_label"      => "Shipping costs",
  "pp_layoutarea1_label"         => "Main area",
  "pp_price_label"               => "Price",
  "pp_settings_label"            => "Delivery options",
  "pp_showhide_label"            => "Show/hide general layout area",
  'pp_page_label'                => 'Page(s)',

  "pp_image1_label" => "Product image",
  "pp_text1_label"  => "Product description",
  "pp_title1_label" => "Product name",
  "pp_additional_images_label" => "Product-detail images",

  // Products
  "pp_button_product_submit_label"   => "save",
  "pp_product_additional_data_label" => "Additional data",
  "pp_product_attributes_label"      => "Attributes",
  "pp_product_label"                 => "Product",
  "pp_product_showhide_label"        => "Show/hide product",
  "pp_product_title_label"           => "Product name",
  "pp_product_text_label"            => "Product description",
  "pp_product_delete_image_label"    => "Delete image",
  "pp_product_delete_image_question_label" => "Do you really want to delete this image?",
  "pp_product_move_up_label"         => "Move up product",
  "pp_product_move_down_label"       => "Move down product",
  "pp_product_move_label"            => "Move product",
  "pp_product_delete_label"          => "Delete product",
  "pp_product_delete_question_label" => "Do you really want to delete this product?",
  "pp_product_actions_label"         => "Product-actions",
  "pp_product_price_label"           => "Price",
  "pp_product_number_label"          => "Item number",
  "pp_products_label"                => "Products",
  "pp_product_additional_images_label" => "Detail images",
  "pp_product_change_filter_label"   => "Change filter",
  "pp_product_reset_filter_label"    => "Reset filter",
  "pp_product_set_filter_label"      => "Apply filter",
  "pp_product_filter_status_label"   => "Filter product attributes and differences",
  "pp_product_filter_compare_title"  => "Differences of products considering productname, price, case packs and shipping costs. Please choose the difference to filter.",

  // options
  "pp_button_option_create_label"      => "add",
  "pp_button_option_edit_label"        => "update",
  "pp_button_option_cancel_label"      => "cancel",
  "pp_message_options_maximum_reached" => "Maximum number of options reached!",
  "pp_option_create_label"             => "Add new option",
  "pp_option_edit_label"               => "Change option",
  "pp_option_delete_label"             => "Delete option",
  "pp_option_delete_question_label"    => "Do you really want to delete this option?",
  "pp_option_option_label"             => "Option",
  "pp_option_price_label"              => "Price",
  "pp_option_move_down_label"          => "Move down option",
  "pp_option_move_label"               => "Move option",
  "pp_option_move_up_label"            => "Move up option",
  "pp_options_label"                   => "Options",
  "pp_options_existing_label"          => "Existing Options",
  "pp_options_showhide_label"          => "Show/hide options",

  "end",""));

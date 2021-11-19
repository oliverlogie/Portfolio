<?php

/*******************************************************************************

 $LastChangedDate: 2019-12-13 11:49:30 +0100 (Fr, 13 Dez 2019) $
 $LastChangedBy: ulb $

 @package config
 @author Benjamin Ulmer
 @copyright (c) 2012 Q2E GmbH

 EDWIN CMS DEFAULT CONFIG

 Do not change for EDWIN projects, except for new contenttypes, modules or
 other features developed for customer's project.

 Initialize variables for new contenttypes, features or modules here.

 Contents:
 - common
 - classes
 - content types
 - files
 - generals
 - modules
 - projects ( put default configuration values for EDWIN projects here )

*******************************************************************************/

/*******************************************************************************

 COMMON

*******************************************************************************/

if (!isset($_CONFIG['dbcharset'])) $_CONFIG['dbcharset'] = 'utf8mb4';
if (!isset($_CONFIG['charset'])) $_CONFIG['charset'] = 'UTF-8';
if (!isset($_CONFIG['m_mail_sender_label'])) $_CONFIG['m_mail_sender_label'] = array(0 => 'no-reply@q2e.at');
if (!isset($_CONFIG['m_mail_smtp_credentials'])) $_CONFIG['m_mail_smtp_credentials'] = array();
if (!isset($_CONFIG['sender_system_mailbox_address'])) $_CONFIG['sender_system_mailbox_address'] = null;
if (!isset($_CONFIG['m_debug'])) $_CONFIG['m_debug'] = true;
if (!isset($_CONFIG['m_logging'])) $_CONFIG['m_logging'] = null;
if (!isset($_CONFIG['minify_library'])) $_CONFIG['minify_library'] = '/var/www-hosts/dev.q2e.at/_edwin/minify/v/2.0/';
if (!isset($_CONFIG['m_session_name_backend'])) $_CONFIG['m_session_name_backend'] = 'edw_be';
if (!isset($_CONFIG['m_backend_theme'])) $_CONFIG['m_backend_theme'] = 'themes/default/';
if (!isset($_CONFIG['m_backend_live_mode'])) $_CONFIG['m_backend_live_mode'] = true;
if (!isset($_CONFIG['m_session_save_path'])) $_CONFIG['m_session_save_path'] = '';
if (!isset($_CONFIG['m_module_alias_shortnames'])) $_CONFIG['m_module_alias_shortnames'] = array(
  'medialibrary' => array('mediasidebox'),
);
if (!isset($_CONFIG['m_config_overrides'])) $_CONFIG['m_config_overrides'] = array();
if (!isset($_CONFIG['m_extended_data'])) $_CONFIG['m_extended_data'] = false;
if (!isset($_CONFIG['m_extended_data_handlers'])) $_CONFIG['m_extended_data_handlers'] = array(
  //
  // Handler configuration
  // - for image alt text extended data|number of images = number of alt texts to create, update, delete for content item
  //
  'ContentItemBG' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ContentItemCA' => array(
    'Handlers\\ImageAltTextHandler|3',
  ),
  'ContentItemCA_Areas' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ContentItemCA_Area_Boxes' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ContentItemCB' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ContentItemCB_Boxes' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ContentItemCB_Box_BigLinks' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ContentItemQP' => array(
    'Handlers\\ImageAltTextHandler|10',
  ),
  'ContentItemQP_Statements' => array(
    'Handlers\\ImageAltTextHandler|11',
  ),
  'ContentItemQS' => array(
    'Handlers\\ImageAltTextHandler|3',
  ),
  'ContentItemQS_Statements' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ModuleGlobalAreaManagement' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ModuleGlobalAreaManagement_Box' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ModuleSiteindex' => array(
    'Handlers\\ImageAltTextHandler|3',
  ),
  'ModuleSiteindexCompendium_Areas' => array(
    'Handlers\\ImageAltTextHandler|1',
  ),
  'ModuleSiteindexCompendium_Area_Boxes' => array(
    'Handlers\\ImageAltTextHandler|3',
  ),
);

/*******************************************************************************

 CLASSES

*******************************************************************************/

/* BackendRequest *************************************************************/
if (!isset($_CONFIG['be_editor_lang'])) $_CONFIG['be_editor_lang'] = array('german' => 'de', 'english' => 'en');
/* @deprecated Use m_backend_theme instead of be_skin */
if (!isset($_CONFIG['be_skin'])) $_CONFIG['be_skin'] = 'skin1';
if (!isset($_CONFIG['m_cache_resource_version'])) $_CONFIG['m_cache_resource_version'] = '';
if (!isset($_CONFIG['m_countries_use_deprecated'])) $_CONFIG['m_countries_use_deprecated'] = false;
if (!isset($_CONFIG['sf_cache_navigation'])) $_CONFIG['sf_cache_navigation'] = false;
if (!isset($_CONFIG['sn_cache_navigation'])) $_CONFIG['sn_cache_navigation'] = false;
if (!isset($_CONFIG['st_cache_navigation'])) $_CONFIG['st_cache_navigation'] = false;
if (!isset($_CONFIG['sx_cache_navigation'])) $_CONFIG['sx_cache_navigation'] = false;

/* CmsBugtracking *************************************************************/
if (!isset($_CONFIG['m_bugtracking_mail'])) $_CONFIG['m_bugtracking_mail'] = 'bugtracking@q2e.at';
if (!isset($_CONFIG['m_bugtracking_type'])) $_CONFIG['m_bugtracking_type'] = 'mail';

/* CmsDiskSpace ***************************************************************/

if (!isset($_CONFIG['m_disk_space_usage_limit'])) $_CONFIG['m_disk_space_usage_limit'] = 0;
if (!isset($_CONFIG['m_disk_space_usage_local'])) $_CONFIG['m_disk_space_usage_local'] = false;

/* CleverReach ****************************************************************/

if (!isset($_CONFIG['m_cleverreach_api_key'])) $_CONFIG['m_cleverreach_api_key'] = 'c33509572b5a4e0bc381d7c697b0b161-2';
if (!isset($_CONFIG['m_cleverreach_list_id'])) $_CONFIG['m_cleverreach_list_id'] = 522186;

/* Rapidmail ******************************************************************/

if (!isset($_CONFIG['m_rapidmail_lists'])) $_CONFIG['m_rapidmail_lists'] = array(
  array(
    'node_id'          => 0,
    'recipientlist_id' => 0,
    'apikey'           => '',
    'edw_sites'        => array(0),
    'edw_campaigns'    => array(0),
  ),
);

/* ContentBase ****************************************************************/

// Standardwerte für Bilder
if (!isset($_CONFIG['m_image_width'])) $_CONFIG['m_image_width'] = 240;
if (!isset($_CONFIG['m_image_height'])) $_CONFIG['m_image_height'] = 180;
if (!isset($_CONFIG['m_large_image_width'])) $_CONFIG['m_large_image_width'] = 600;
if (!isset($_CONFIG['m_large_image_height'])) $_CONFIG['m_large_image_height'] = 450;
if (!isset($_CONFIG['m_selection_width'])) $_CONFIG['m_selection_width'] = 0;
if (!isset($_CONFIG['m_selection_height'])) $_CONFIG['m_selection_height'] = 0;
if (!isset($_CONFIG['m_ignore_image_size'])) $_CONFIG['m_ignore_image_size'] = false;
if (!isset($_CONFIG['m_autofit_image_upload'])) $_CONFIG['m_autofit_image_upload'] = true;
if (!isset($_CONFIG['m_watermark'])) $_CONFIG['m_watermark'] = false;
if (!isset($_CONFIG['m_image_fix_orientation'])) $_CONFIG['m_image_fix_orientation'] = false;
if (!isset($_CONFIG['m_image_quality'])) $_CONFIG['m_image_quality'] = 95;
if (!isset($_CONFIG['m_boximage_source'])) $_CONFIG['m_boximage_source'] = 2;
if (!isset($_CONFIG['m_boximage2_source'])) $_CONFIG['m_boximage2_source'] = 2;
if (!isset($_CONFIG['m_th_image_width'])) $_CONFIG['m_th_image_width'] = 160;
if (!isset($_CONFIG['m_th_image_height'])) $_CONFIG['m_th_image_height'] = 120;
if (!isset($_CONFIG['m_th_selection_width'])) $_CONFIG['m_th_selection_width'] = 0;
if (!isset($_CONFIG['m_th_selection_height'])) $_CONFIG['m_th_selection_height'] = 0;
if (!isset($_CONFIG['m_th_large_selection_width'])) $_CONFIG['m_th_large_selection_width'] = 0;
if (!isset($_CONFIG['m_th_large_selection_height'])) $_CONFIG['m_th_large_selection_height'] = 0;

if (!isset($_CONFIG['m_blog_contenttype_excluded']) || !is_array($_CONFIG['m_blog_contenttype_excluded'])) $_CONFIG['m_blog_contenttype_excluded'][0] = array(3, 8, 12, 19, 21, 25, 29, 30, 32, 33, 75, 76, 77, 78, 79, 80);
if (!isset($_CONFIG["m_blog_default"])) $_CONFIG["m_blog_default"][0] = 0;
if (!isset($_CONFIG["m_blog_from_page"])) $_CONFIG["m_blog_from_page"] = array();
if (!isset($_CONFIG['m_date'])) $_CONFIG['m_date'] = array( 'german'  => 'd.m.Y', 'english' => 'Y-m-d' );
if (!isset($_CONFIG['m_date_time'])) $_CONFIG['m_date_time'] = array( 'german'  => 'd.m.Y - H:i', 'english' => 'Y-m-d H:i' );
if (!isset($_CONFIG["m_hierarchical_title_separator"])) $_CONFIG["m_hierarchical_title_separator"] = ' | ';
if (!isset($_CONFIG['m_infobox'])) $_CONFIG['m_infobox'] = true;
if (!isset($_CONFIG["m_share_contenttype_excluded"]) || !is_array($_CONFIG["m_share_contenttype_excluded"])) $_CONFIG["m_share_contenttype_excluded"][0] = array(3, 8, 12, 19, 21, 25, 29, 30, 32, 75, 76, 77, 78, 79);
if (!isset($_CONFIG["m_share_default"])) $_CONFIG["m_share_default"] = array(0 => 0);
if (!isset($_CONFIG["m_share_display"])) $_CONFIG["m_share_display"] = array(0 => 0);
if (!isset($_CONFIG["m_share_from_page"]) || !is_array($_CONFIG["m_share_from_page"])) $_CONFIG["m_share_from_page"] = array();
if (!isset($_CONFIG["m_structure_links"])) $_CONFIG["m_structure_links"] = array();
if (!isset($_CONFIG['m_sructure_links_enable_disable'])) $_CONFIG['m_sructure_links_enable_disable'] = array();
if (!isset($_CONFIG["m_structure_links_hierarchical_title_separator"])) $_CONFIG["m_structure_links_hierarchical_title_separator"] = null;
if (!isset($_CONFIG["m_taglevel_contenttypes"])) $_CONFIG["m_taglevel_contenttypes"] = array(75, 3, 77, 78, 81);
if (!isset($_CONFIG["m_additionaltextlevel_level"])) $_CONFIG["m_additionaltextlevel_level"] = array(0 => array(0, 1, 2, 3));
if (!isset($_CONFIG["m_additionalimagelevel_level"])) $_CONFIG["m_additionalimagelevel_level"] = array(0 => array(0, 1, 2, 3));
if (!isset($_CONFIG['m_url_protocols'])) $_CONFIG['m_url_protocols'] = array('http://', 'https://', 'ftp://', 'mailto:', 'tel:');
if (!isset($_CONFIG['m_form_contenttype_excluded'])) $_CONFIG['m_form_contenttype_excluded'][0] = array(55); // CM
if (!isset($_CONFIG['m_shorttext_aftertext'])) $_CONFIG['m_shorttext_aftertext'] = ' ...';
if (!isset($_CONFIG['m_shorttext_cut_exact'])) $_CONFIG['m_shorttext_cut_exact'] = false;
if (!isset($_CONFIG['m_live_mode'])) $_CONFIG['m_live_mode'] = false;
if (!isset($_CONFIG['m_modules_show_only_available_files'])) $_CONFIG['m_modules_show_only_available_files'] = false;
if (!isset($_CONFIG['bl_title_available'])) $_CONFIG['bl_title_available'] = false;
if (!isset($_CONFIG["se_min_wordlength"])) $_CONFIG["se_min_wordlength"] = 3;

/* ContentItem ****************************************************************/

if (!isset($_CONFIG['ci_be_text_source'])) $_CONFIG['ci_be_text_source'] = array();
if (!isset($_CONFIG['ci_be_image_source'])) $_CONFIG['ci_be_image_source'] = array();
if (!isset($_CONFIG['ci_be_shorttext_maxlength'])) $_CONFIG['ci_be_shorttext_maxlength'] = 350;
if (!isset($_CONFIG['ci_be_allowed_html'])) $_CONFIG['ci_be_allowed_html'] = '';
if (!isset($_CONFIG['ci_shorttext_maxlength'])) $_CONFIG['ci_shorttext_maxlength'] = 200;
/**
 * "ci_timing_type" 'activated' - timing is deactivated
 *                  'deactivated' - timing is activated
 *                  'startdateonly' - only start dates activated
 */
if (!isset($_CONFIG["ci_timing_type"])) $_CONFIG["ci_timing_type"] = 'deactivated';
/**
 * "ci_timing_allowed_ctypes" array of the content prefix string values provided by each contentitem
 * defines content items, where  timing is enabled inside
 */
if (!isset($_CONFIG["ci_timing_allowed_ctypes"])) $_CONFIG["ci_timing_allowed_ctypes"] = array(1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18, 20, 22, 24, 26, 27, 28, 33, 34, 36, 37, 42, 44, 45, 46, 55, 56, 75, 76);
if (!isset($_CONFIG["ci_copy_allowed_ctypes"])) $_CONFIG["ci_copy_allowed_ctypes"] = array(1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18, 20, 22, 24, 26, 27, 28, 33, 34, 36, 37, 42, 44, 45, 46, 55, 56);
if (!isset($_CONFIG["reserved_paths"])) $_CONFIG["reserved_paths"] = array ();
if (!isset($_CONFIG['strictly_reserved_paths'])) $_CONFIG['strictly_reserved_paths'] = array ('css', 'edwin', 'feed', 'files', 'img', 'includes', 'pix', 'prog', 'response', 'templates', 'tps');

/* ContentItemComments ********************************************************/

if (!isset($_CONFIG["bl_results_per_page"])) $_CONFIG["bl_results_per_page"] = 20;
if (!isset($_CONFIG["bl_hierarchical_title_separator"])) $_CONFIG["bl_hierarchical_title_separator"] = ' | ';

/* ContentItemFiles ***********************************************************/

if (!isset($_CONFIG["fi_file_size"])) $_CONFIG["fi_file_size"] = 26214400; // 25 MB
if (!isset($_CONFIG["fi_file_types"])) $_CONFIG["fi_file_types"] = array('jpg', 'JPG', 'jpeg', 'JPEG', 'tif', 'TIF', 'pdf', 'PDF', 'zip', 'ZIP', 'doc', 'DOC', 'xls', 'XLS',  'docx', 'DOCX', 'xlsx', 'XLSX', 'eps', 'EPS', 'wmv', 'WMV', 'avi', 'AVI', 'mov', 'MOV', 'mpeg', 'MPEG', 'vcf', 'VCF', 'mp4', 'MP4');

/* ContentItemLogical *********************************************************/

if (!isset($_CONFIG['lo_max_levels'])) $_CONFIG["lo_max_levels"][0] = array('main' => 4, 'footer'=> 1, 'hidden' => 1, 'login' => 2, 'pages' => 2, 'user' => 1);
if (!isset($_CONFIG["lo_max_items"])) $_CONFIG["lo_max_items"][0] = array(
  'main' => array ( 1 => 7, 2 => 10, 3 => 25, 4 => 25, ),
  'footer' => array ( 1 => 7, 2 => 5, 3 => 25),
  'hidden' => array (1 => 7),
  'login' => array ( 1 => 6, 2 => 10, 3 => 25 ),
  'pages' => array ( 1 => 7, 2 => 10, 3 => 12),
  'user' => array ( 1 => 7, 2 => 10),
);
if (!isset($_CONFIG["lo_excluded_contenttypes"])) $_CONFIG["lo_excluded_contenttypes"][0] = array(
    'main'  => array (),
    'footer'=> array (3, 75, 76, 77, 78, 79, 80),
    'hidden'  => array (),
    'login' => array (),
    'pages' => array (76),
    'user' => array (77, 78)
);
if (!isset($_CONFIG["lo_allow_imageboxes_at_level"])) $_CONFIG["lo_allow_imageboxes_at_level"][0] = array(
    'main'  => array (2, 3),
    'footer'=> array (0),
    'hidden'=> array (0),
    'login' => array (0),
    'pages' => array (0),
    'user' => array (0),
);
if (!isset($_CONFIG["lo_allow_ip_at_level"])) $_CONFIG["lo_allow_ip_at_level"][0] = array(
    'main'  => array (2, 3),
    'footer'=> array (0),
    'hidden'=> array (0),
    'login' => array (0),
    'pages' => array (0),
    'user' => array (0),
);
if (!isset($_CONFIG["lo_allow_lp_at_level"])) $_CONFIG["lo_allow_lp_at_level"][0] = array(
    'main'  => array (1, 2),
    'footer'=> array (0),
    'hidden'=> array (0),
    'login' => array (0),
    'pages' => array (0),
    'user' => array (0),
);
if (!isset($_CONFIG['lo_allow_be_at_level'])) $_CONFIG['lo_allow_be_at_level'][0] = array(
    'main'  => array (2, 3),
    'footer'=> array (0),
    'hidden'=> array (0),
    'login' => array (0),
    'pages' => array (0),
    'user' => array (0),
);
if (!isset($_CONFIG['be_level_data_active'])) $_CONFIG['be_level_data_active'] = 0;
if (!isset($_CONFIG['be_image_width'])) $_CONFIG['be_image_width'] = 200;
if (!isset($_CONFIG['be_image_height'])) $_CONFIG['be_image_height'] = 150;
if (!isset($_CONFIG['be_selection_width'])) $_CONFIG['be_selection_width'] = 0;
if (!isset($_CONFIG['be_selection_height'])) $_CONFIG['be_selection_height'] = 0;
if (!isset($_CONFIG['lo_be_image_width'])) $_CONFIG['lo_be_image_width'] = 200;
if (!isset($_CONFIG['lo_be_image_height'])) $_CONFIG['lo_be_image_height'] = 150;
if (!isset($_CONFIG["lo_image_width"])) $_CONFIG["lo_image_width"] = 60;
if (!isset($_CONFIG["lo_image_height"])) $_CONFIG["lo_image_height"] = 80;
if (!isset($_CONFIG["lo_image_width2"])) $_CONFIG["lo_image_width2"] = 45;
if (!isset($_CONFIG["lo_image_height2"])) $_CONFIG["lo_image_height2"] = 60;
if (!isset($_CONFIG["lo_selection_width"])) $_CONFIG["lo_selection_width"] = 0;
if (!isset($_CONFIG["lo_selection_height"])) $_CONFIG["lo_selection_height"] = 0;
if (!isset($_CONFIG["lo_selection_width2"])) $_CONFIG["lo_selection_width2"] = 0;
if (!isset($_CONFIG["lo_selection_height2"])) $_CONFIG["lo_selection_height2"] = 0;
if (!isset($_CONFIG["lo_additional_image_width"])) $_CONFIG["lo_additional_image_width"] = 100;
if (!isset($_CONFIG["lo_additional_image_height"])) $_CONFIG["lo_additional_image_height"] = 100;
if (!isset($_CONFIG["lo_additional_selection_width"])) $_CONFIG["lo_additional_selection_width"] = 0;
if (!isset($_CONFIG["lo_additional_selection_height"])) $_CONFIG["lo_additional_selection_height"] = 0;
if (!isset($_CONFIG["lo_results_per_page"])) $_CONFIG["lo_results_per_page"] = 50;
if (!isset($_CONFIG["m_user_rights_levels"])) $_CONFIG["m_user_rights_levels"] = 0;
if (!isset($_CONFIG["fo_edit_parent_form"])) $_CONFIG["fo_edit_parent_form"] = false;

/* DataGrid *******************************************************************/

if (!isset($_CONFIG['g_gri_items_per_page'])) $_CONFIG['g_gri_items_per_page'] = 50;
if (!isset($_CONFIG['g_gri_items_around'])) $_CONFIG['g_gri_items_around'] = 6;
if (!isset($_CONFIG['g_gri_date_format'])) $_CONFIG['g_gri_date_format'] = 'd.m.Y H:i';
if (!isset($_CONFIG['g_gri_selective_more_labels'])) $_CONFIG['g_gri_selective_more_labels'] = true;
if (!isset($_CONFIG['g_gri_selective_more_labels_separator'])) $_CONFIG['g_gri_selective_more_labels_separator'] = " | ";

/* db.mysql *******************************************************************/

if (!isset($_CONFIG["DEBUG_SQL"])) $_CONFIG["DEBUG_SQL"] = false;

/* Input **********************************************************************/

if (!isset($_CONFIG['be_allowed_html_level1'])) {
  $_CONFIG['be_allowed_html_level1'] = '<br><b><a><ul><li><br><i><u><sub><sup>'
                                     . '<span><table><thead><tbody><tr><td><th><iframe>';
}
if (!isset($_CONFIG['be_allow_html_in_titles'])) $_CONFIG['be_allow_html_in_titles'] = true;
if (!isset($_CONFIG['be_allowed_html_level2'])) $_CONFIG['be_allowed_html_level2'] = '<br><sub><sup>';
if (!isset($_CONFIG['be_allowed_html_level3'])) $_CONFIG['be_allowed_html_level3'] = '<sub><sup>';
if (!isset($_CONFIG['be_allowed_html_level4'])) $_CONFIG['be_allowed_html_level4'] = '<br>';

/* Issuu **********************************************************************/

if (!isset($_CONFIG["m_issuu_access"])) $_CONFIG["m_issuu_access"] = 'private';
if (!isset($_CONFIG["m_issuu_api_key"])) $_CONFIG["m_issuu_api_key"] = '66xnp22u2centujq0megn9bnaqpufzi6';
if (!isset($_CONFIG["m_issuu_api_key_secret"])) $_CONFIG["m_issuu_api_key_secret"] = 'xo2crqo4gugudzmvmnkp0qwrrjd2njd8';
if (!isset($_CONFIG["m_issuu_comments_allowed"])) $_CONFIG["m_issuu_comments_allowed"] = 'false';
if (!isset($_CONFIG["m_issuu_ratings_allowed"])) $_CONFIG["m_issuu_ratings_allowed"] = 'false';
if (!isset($_CONFIG["m_issuu_user"])) $_CONFIG["m_issuu_user"] = 'q2egmbh';

/* Login **********************************************************************/

if (!isset($_CONFIG['m_client_uploads_expiration'])) $_CONFIG['m_client_uploads_expiration'] = 60*60*24*30;

/* Module *********************************************************************/

if (!isset($_CONFIG['m_mod_filtertext_maxlength'])) $_CONFIG['m_mod_filtertext_maxlength'] = 50;
if (!isset($_CONFIG['m_mod_filtertext_aftertext'])) $_CONFIG['m_mod_filtertext_aftertext'] = '...';

/* User ***********************************************************************/

if (!isset($_CONFIG['m_user_create_content_prohibited'])) $_CONFIG['m_user_create_content_prohibited'] = array();
if (!isset($_CONFIG['m_user_delete_content_prohibited'])) $_CONFIG['m_user_delete_content_prohibited'] = array();

/* Validation *****************************************************************/

if (!isset($_CONFIG["m_validation_is_email_blacklist"])) $_CONFIG["m_validation_is_email_blacklist"] = array(
  '(.)+@nachname.at',
);

/*******************************************************************************

 CONTENT TYPES

*******************************************************************************/

/* ContentItemBG **************************************************************/

if (!isset($_CONFIG['bg_image_width'])) $_CONFIG['bg_image_width'] = 240;
if (!isset($_CONFIG['bg_image_height'])) $_CONFIG['bg_image_height'] = 180;
if (!isset($_CONFIG['bg_large_image_width'])) $_CONFIG['bg_large_image_width'] = 320;
if (!isset($_CONFIG['bg_large_image_height'])) $_CONFIG['bg_large_image_height'] = 240;
if (!isset($_CONFIG['bg_th_image_width'])) $_CONFIG['bg_th_image_width'] = 160;
if (!isset($_CONFIG['bg_th_image_height'])) $_CONFIG['bg_th_image_height'] = 120;
if (!isset($_CONFIG['bg_th_selection_width'])) $_CONFIG['bg_th_selection_width'] = 0;
if (!isset($_CONFIG['bg_th_selection_height'])) $_CONFIG['bg_th_selection_height'] = 0;
if (!isset($_CONFIG['bg_image_size'])) $_CONFIG['bg_image_size'] = 5242880; // 5 MB
if (!isset($_CONFIG['bg_file_size'])) $_CONFIG['bg_file_size'] = $_CONFIG['fi_file_size'];
if (!isset($_CONFIG['bg_max_images'])) $_CONFIG['bg_max_images'] = 100;
if (!isset($_CONFIG['bg_image_types'])) $_CONFIG['bg_image_types'] = array('gif', 'GIF', 'jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG');
if (!isset($_CONFIG['bg_galleryimages_per_page'])) $_CONFIG['bg_galleryimages_per_page'] = 18;

/* ContentItemCA **************************************************************/

if (!isset($_CONFIG['ca_number_of_boxes'])) $_CONFIG['ca_number_of_boxes'] = array();
if (!isset($_CONFIG['ca_type_of_boxes'])) $_CONFIG['ca_type_of_boxes'] = array();
if (!isset($_CONFIG['ca_area_image_width'])) $_CONFIG['ca_area_image_width'] = 60;
if (!isset($_CONFIG['ca_area_image_height'])) $_CONFIG['ca_area_image_height'] = 80;
if (!isset($_CONFIG['ca_area_large_image_width'])) $_CONFIG['ca_area_large_image_width'] = $_CONFIG['ca_area_image_width'];
if (!isset($_CONFIG['ca_area_large_image_height'])) $_CONFIG['ca_area_large_image_height'] = $_CONFIG['ca_area_image_height'];
if (!isset($_CONFIG['ca_area_box_image_width'])) $_CONFIG['ca_area_box_image_width'] = 60;
if (!isset($_CONFIG['ca_area_box_image_height'])) $_CONFIG['ca_area_box_image_height'] = 80;
if (!isset($_CONFIG['ca_area_box_large_image_width'])) $_CONFIG['ca_area_box_large_image_width'] = $_CONFIG['ca_area_box_image_width'];
if (!isset($_CONFIG['ca_area_box_large_image_height'])) $_CONFIG['ca_area_box_large_image_height'] = $_CONFIG['ca_area_box_image_height'];

/* ContentItemCB **************************************************************/

if (!isset($_CONFIG["cb_number_of_boxes"])) $_CONFIG["cb_number_of_boxes"] = 6;
if (!isset($_CONFIG["cb_number_of_biglinks"])) $_CONFIG["cb_number_of_biglinks"] = 4;
if (!isset($_CONFIG["cb_number_of_smalllinks"])) $_CONFIG["cb_number_of_smalllinks"] = 4;
// The ContentItemCB Box images should not have large images by default
// (because they are similar to the box images in the logical layer)
if (!isset($_CONFIG["cb_box_image_width"])) $_CONFIG["cb_box_image_width"] = 60;
if (!isset($_CONFIG["cb_box_image_height"])) $_CONFIG["cb_box_image_height"] = 80;
if (!isset($_CONFIG["cb_box_large_image_width"])) $_CONFIG["cb_box_large_image_width"] = $_CONFIG["cb_box_image_width"];
if (!isset($_CONFIG["cb_box_large_image_height"])) $_CONFIG["cb_box_large_image_height"] = $_CONFIG["cb_box_image_height"];
// The ContentItemCB BigLink images should not have large images by default
// (because they are similar to the box images in the logical layer)
if (!isset($_CONFIG["cb_box_biglink_image_width"])) $_CONFIG["cb_box_biglink_image_width"] = 60;
if (!isset($_CONFIG["cb_box_biglink_image_height"])) $_CONFIG["cb_box_biglink_image_height"] = 80;
if (!isset($_CONFIG["cb_box_biglink_large_image_width"])) $_CONFIG["cb_box_biglink_large_image_width"] = $_CONFIG["cb_box_biglink_image_width"];
if (!isset($_CONFIG["cb_box_biglink_large_image_height"])) $_CONFIG["cb_box_biglink_large_image_height"] = $_CONFIG["cb_box_biglink_image_height"];
if (!isset($_CONFIG['cb_box_box_link_required'])) $_CONFIG['cb_box_box_link_required'] = true;

/* ContentItemCX **************************************************************/

if (!isset($_CONFIG["cx_number_of_areas"])) $_CONFIG["cx_number_of_areas"] = 25;
if (!isset($_CONFIG["cx_area_title_max_length"])) $_CONFIG["cx_area_title_max_length"] = 25;
if (!isset($_CONFIG["cx_area_title_after_text"])) $_CONFIG["cx_area_title_after_text"] = '...';
if (!isset($_CONFIG["cx_areas"])) {
  $_CONFIG["cx_areas"] = array(
    'area_example' => array(
      'settings' => array(
        'disabled' => false, // set to `true` to disable this area type in selection - no new areas of this type can be created anymore
        // Area backend title options:
        // Set values to ensure an appropriate length on EDWIN CMS backend.
        // 'area_title_max_length' => 25, // optional, the default maximum length is set in $_CONFIG['cx_area_title_max_length']
        // 'area_title_after_text' => '...', // optional, the default after text is set in $_CONFIG['cx_area_title_after_text']
        // 'area_title_element' => 'title_1', // optional, first title with content used if this is not set to a specific element
      ),
      'lang'     => array(
        'title' => 'cx_areas_area_example_label',
      ),
      // Anything within the elements config will be available in frontend views
      'elements' => array(
        'title_1' => array(
          'type' => 'title',
          'lang' => array(
            'title' => 'cx_areas_area_example_title_1_label',
            'placeholder' => 'cx_areas_area_example_title_1_placeholder',
          ),
        ),
        'text_1' => array(
          'type' => 'text',
        ),
        //
        // Example image configuration
        // $_CONFIG['cx_area_element_area_example_image_1_image_width'] = 1920;
        // $_CONFIG['cx_area_element_area_example_image_1_image_height'] = 1080;
        // $_CONFIG['cx_area_element_area_example_image_1_image_width_large'] = 1920;
        // $_CONFIG['cx_area_element_area_example_image_1_image_height_large'] = 1080;
        //
        'image_1' => array(
          'type' => 'image',
        ),
        'video_1' => array(
          'type' => 'video',
        ),
        'link_1' => array(
          'type' => 'link',
        ),
        'alternatives_1' => array(
          'type' => 'alternatives',
          'elements' => array(
            'image_2' => array(
              'type' => 'image',
            ),
            'video_2' => array(
              'type' => 'video',
            ),
          ),
        ),
        'boxes_1' => array(
          'settings' => array(
            // 'maximum' => 6, // sets the maximum number of boxes that can be created
          ),
          'type' => 'boxes',
          'elements' => array(
            'box_1' => array(
              'type' => 'box',
              'lang' => array(
                // 'title'                       => 'cx_areas_area_box_label',
                // 'btn_save'                    => 'cx_areas_area_box_btn_save_label',
                // 'list_move_label'             => 'cx_areas_area_box_list_move_label',
                // 'list_activation_green_label' => 'cx_areas_area_box_list_activation_green_label',
                // 'list_activation_red_label'   => 'cx_areas_area_box_list_activation_red_label',
                // 'list_delete_question'        => 'cx_areas_area_box_list_delete_question',
                // 'list_delete_label'           => 'cx_areas_area_box_list_delete_label',
                // 'list_showhide_label'         => 'cx_areas_area_box_list_showhide_label',
              ),
              'elements' => array(
                'title_1' => array(
                  'type' => 'title',
                ),
                'text_1' => array(
                  'type' => 'text',
                ),
                //
                // Example image configuration
                // $_CONFIG['cx_area_element_area_example_boxes_1_box_1_image_1_image_width'] = 1920;
                // $_CONFIG['cx_area_element_area_example_boxes_1_box_1_image_1_image_height'] = 1080;
                // $_CONFIG['cx_area_element_area_example_boxes_1_box_1_image_1_image_width_large'] = 1920;
                // $_CONFIG['cx_area_element_area_example_boxes_1_box_1_image_1_image_height_large'] = 1080;
                //
                'image_1' => array(
                  'type' => 'image',
                ),
              ),
            ),
          ),
        ),
      )
    ),
  );
}

/* ContentItemDL **************************************************************/

if (!isset($_CONFIG['dl_file_size'])) $_CONFIG['dl_file_size'] = $_CONFIG['fi_file_size'];
if (!isset($_CONFIG['dl_file_types'])) $_CONFIG['dl_file_types'] = $_CONFIG['fi_file_types'];
if (!isset($_CONFIG['dl_number_of_areas'])) $_CONFIG['dl_number_of_areas'] = 4;
if (!isset($_CONFIG['dl_number_of_files'])) $_CONFIG['dl_number_of_files'] = 15;
if (!isset($_CONFIG['dl_area_file_new_days'])) $_CONFIG['dl_area_file_new_days'] = 14;
if (!isset($_CONFIG['dl_area_file_updated_days'])) $_CONFIG['dl_area_file_updated_days'] = 14;
if (!isset($_CONFIG['dl_area_file_size_display_threshold'])) $_CONFIG['dl_area_file_size_display_threshold'] = '1KB';
if (!isset($_CONFIG['dl_area_file_insert_download_at_first_position'])) $_CONFIG['dl_area_file_insert_download_at_first_position'] = false;

/* ContentItemES **************************************************************/

if (!isset($_CONFIG["es_available_ext_sources"])) $_CONFIG["es_available_ext_sources"] = array( 2 );
if (!isset($_CONFIG["es_properties_quantity"])) $_CONFIG["es_properties_quantity"] = 0;

/* ContentItemES_EXT02 ( Youtube ) ********************************************/

$_CONFIG["es_ext02_properties_quantity"] = 8;
if (!isset($_CONFIG["es_ext02_property_quantity_preset"])) $_CONFIG["es_ext02_property_quantity_preset"] = 10;

/* ContentItemES_EXT03 ( IFrame ) *********************************************/

$_CONFIG["es_ext03_properties_quantity"] = 1;
if (!isset($_CONFIG["es_ext03_property_preset"])) $_CONFIG["es_ext03_property_preset"] = "http://";

/* ContentItemES_EXT04 ( marCO XML Feed ) *************************************/

$_CONFIG["es_ext04_properties_quantity"] = 1;

/* ContentItemFQ **************************************************************/

if (!isset($_CONFIG["fq_number_of_questions"])) $_CONFIG["fq_number_of_questions"] = 25;

/* ContentItemIB **************************************************************/

if (!isset($_CONFIG['ib_level_data_active'])) $_CONFIG['ib_level_data_active'] = false;

/* ContentItemIP **************************************************************/

if (!isset($_CONFIG['ip_level_data_active'])) $_CONFIG['ip_level_data_active'] = false;

/* ContentItemPB **************************************************************/

if (!isset($_CONFIG['pb_level_data_active'])) $_CONFIG['pb_level_data_active'] = false;

/* ContentItemPP **************************************************************/

if (!isset($_CONFIG["pp_number_of_attributes"])) $_CONFIG["pp_number_of_attributes"] = 5;
if (!isset($_CONFIG["pp_number_of_options"])) $_CONFIG["pp_number_of_options"] = 5;
if (!isset($_CONFIG["pp_product_additional_data"])) $_CONFIG["pp_product_additional_data"] = array();
if (!isset($_CONFIG["pp_product_links_per_page"])) $_CONFIG["pp_product_links_per_page"] = 5;
if (!isset($_CONFIG["pp_product_results_per_page"])) $_CONFIG["pp_product_results_per_page"] = 15;
if (!isset($_CONFIG["pp_product_filterable"])) $_CONFIG["pp_product_filterable"] = false;
if (!isset($_CONFIG["pp_product_show_on_level"])) $_CONFIG["pp_product_show_on_level"] = false;

/* ContentItemQP **************************************************************/

if (!isset($_CONFIG['qp_number_of_statements'])) $_CONFIG['qp_number_of_statements'] = 25;

/* ContentItemQS **************************************************************/

if (!isset($_CONFIG["qs_number_of_statements"])) $_CONFIG["qs_number_of_statements"] = 25;

/* ContentItemTG **************************************************************/

if (!isset($_CONFIG['tg_file_size'])) $_CONFIG['tg_file_size'] = $_CONFIG['fi_file_size'];
if (!isset($_CONFIG['tg_image_width'])) $_CONFIG['tg_image_width'] = 240;
if (!isset($_CONFIG['tg_image_height'])) $_CONFIG['tg_image_height'] = 180;
if (!isset($_CONFIG['tg_image_size'])) $_CONFIG['tg_image_size'] = 5242880; // 5 MB
if (!isset($_CONFIG['tg_image_types'])) $_CONFIG['tg_image_types'] = array('gif', 'GIF', 'jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG');
if (!isset($_CONFIG['tg_galleryimages_per_page'])) $_CONFIG['tg_galleryimages_per_page'] = 18;
if (!isset($_CONFIG['tg_large_image_width'])) $_CONFIG['tg_large_image_width'] = 320;
if (!isset($_CONFIG['tg_large_image_height'])) $_CONFIG['tg_large_image_height'] = 240;
if (!isset($_CONFIG['tg_max_images'])) $_CONFIG['tg_max_images'] = 100;
if (!isset($_CONFIG['tg_max_image_tags'])) $_CONFIG['tg_max_image_tags'] = 20;
if (!isset($_CONFIG['tg_max_zip_upload_tags'])) $_CONFIG['tg_max_zip_upload_tags'] = 4;
if (!isset($_CONFIG['tg_th_image_width'])) $_CONFIG['tg_th_image_width'] = 160;
if (!isset($_CONFIG['tg_th_image_height'])) $_CONFIG['tg_th_image_height'] = 120;
if (!isset($_CONFIG['tg_th_selection_width'])) $_CONFIG['tg_th_selection_width'] = 0;
if (!isset($_CONFIG['tg_th_selection_height'])) $_CONFIG['tg_th_selection_height'] = 0;
if (!isset($_CONFIG['tg_file_size_display_threshold'])) $_CONFIG['tg_file_size_display_threshold'] = '1KB';

/* ContentItemTS **************************************************************/

if (!isset($_CONFIG['ts_number_of_blocks'])) $_CONFIG['ts_number_of_blocks'] = 2;
if (!isset($_CONFIG['ts_number_of_links'])) $_CONFIG['ts_number_of_links'] = 10;

/* ContentItemVA **************************************************************/

if (!isset($_CONFIG["va_level_data_active"])) $_CONFIG["va_level_data_active"] = 1;
if (!isset($_CONFIG["va_level_data_attributes_on_path"])) $_CONFIG["va_level_data_attributes_on_path"] = array();

/* ContentItemVC **************************************************************/

if (!isset($_CONFIG["vc_video_types"])) $_CONFIG["vc_video_types"] = array(
  "youtube" => array(
    "data_regex" => '/^https?\:\/\/www\.youtube\.com\/watch\?v\=([^&]+)/',
    "data_format" => 'http://www.youtube.com/watch?v=%s',
    "url" => 'http://www.youtube.com/watch?v=%s',
  ),
    "vimeo" => array(
      "data_regex" => '/^https?\:\/\/(?:www\.)?vimeo\.com\/(\d+)/',
      "data_format" => 'http://vimeo.com/%s',
      "url" => 'http://vimeo.com/%s',
  ),
    "myspace" => array(
      "data_regex" => '/^https?\:\/\/www\.myspace\.com\/.*\/+(\d+)/',
      "data_format" => 'http://www.myspace.com/video/vid/%s',
      "url" => 'http://www.myspace.com/video/vid/%s',
  ),
    "myvideo" => array(
      "data_regex" => '/^https?\:\/\/www\.myvideo\.([^\/]+)\/watch\/(\d+)/',
      "data_format" => 'http://www.myvideo.%s/watch/%s',
      "url" => 'http://www.myvideo.%s/watch/%s',
  ),
);
if (!isset($_CONFIG["vc_video_types_available"])) $_CONFIG["vc_video_types_available"] = array("youtube", "vimeo", "myspace", "myvideo");

/* ContentItemVD **************************************************************/

// issuu.com API path
if (!isset($_CONFIG["vd_issuu_uri"])) $_CONFIG["vd_issuu_uri"] = 'http://api.issuu.com/1_0';
// This is the endpoint (= action in forms) to upload documents on issuu.com
if (!isset($_CONFIG["vd_issuu_uri_upload"])) $_CONFIG["vd_issuu_uri_upload"] = 'http://upload.issuu.com/1_0';
// Username of the issuu.com account:
if (!isset($_CONFIG["vd_issuu_user"])) $_CONFIG["vd_issuu_user"] = $_CONFIG["m_issuu_user"];
// Application key of the issuu.com account:
if (!isset($_CONFIG["vd_issuu_api_key"])) $_CONFIG["vd_issuu_api_key"] = $_CONFIG["m_issuu_api_key"];
// Secret application key of the issuu.com account to generate a signature:
if (!isset($_CONFIG["vd_issuu_api_key_secret"])) $_CONFIG["vd_issuu_api_key_secret"] = $_CONFIG["m_issuu_api_key_secret"];
// Can other people comment on this document?
if (!isset($_CONFIG["vd_issuu_comments_allowed"])) $_CONFIG["vd_issuu_comments_allowed"] = $_CONFIG["m_issuu_comments_allowed"];
// Must be "public" or "private". Private documents are not shown in search engines or on issuu.com
if (!isset($_CONFIG["vd_issuu_access"])) $_CONFIG["vd_issuu_access"] = $_CONFIG["m_issuu_access"];
// Can other people rate this document?
if (!isset($_CONFIG["vd_issuu_ratings_allowed"])) $_CONFIG["vd_issuu_ratings_allowed"] = $_CONFIG["m_issuu_ratings_allowed"];

/*******************************************************************************

 FILES

*******************************************************************************/

/* index.php ******************************************************************/

if (!isset($_CONFIG['DEBUG_PHP'])) $_CONFIG['DEBUG_PHP'] = 2147483647;
if (!isset($_CONFIG['m_default_timezone'])) $_CONFIG['m_default_timezone'] = 'Europe/Vienna';
if (!isset($_CONFIG['m_use_compressed_lang_file'])) $_CONFIG['m_use_compressed_lang_file'] = false;
if (!isset($_CONFIG['protocol'])) $_CONFIG['protocol'] = 'http://';

/* main.inc.php ***************************************************************/

if (!isset($_CONFIG["m_password_quality"])) $_CONFIG["m_password_quality"] = 3;
if (!isset($_CONFIG["m_password_length"])) $_CONFIG["m_password_length"] = 8;
if (!isset($_CONFIG["m_password_types"])) $_CONFIG["m_password_types"] = array( "big", "small", "numbers" );

if (!isset($_CONFIG["m_login_password_quality"])) $_CONFIG["m_login_password_quality"] = 3;
if (!isset($_CONFIG["m_login_password_length"])) $_CONFIG["m_login_password_length"] = 8;
if (!isset($_CONFIG["m_login_password_types"])) $_CONFIG["m_login_password_types"] = array( "big", "small", "numbers" );

/*******************************************************************************

 GENERALS

*******************************************************************************/

/* GeneralBlog ****************************************************************/

if (!isset($_CONFIG['g_blg_shorttext_maxlength'])) $_CONFIG['g_blg_shorttext_maxlength'] = 70;

/*******************************************************************************

 MODULES

*******************************************************************************/

/* AbstractModuleLeadManagement ***********************************************/

if (!isset($_CONFIG['ld_appointment_reminder_period'])) $_CONFIG['ld_appointment_reminder_period'] = '45'; // minutes
if (!isset($_CONFIG['ld_day_part1'])) $_CONFIG['ld_day_part1'] = array ( 'start' => '00:00', 'end' => '12:00' );
if (!isset($_CONFIG['ld_day_part2'])) $_CONFIG['ld_day_part2'] = array ( 'start' => '12:01', 'end' => '17:00' );
if (!isset($_CONFIG['ld_day_part3'])) $_CONFIG['ld_day_part3'] = array ( 'start' => '17:01', 'end' => '23:59' );
if (!isset($_CONFIG['ld_appointment_shorttext_maxlength'])) $_CONFIG['ld_appointment_shorttext_maxlength'] = array(0 => 35);
if (!isset($_CONFIG['ld_status_history_shorttext_maxlength'])) $_CONFIG['ld_status_history_shorttext_maxlength'] = array(0 => 60);

/* ModuleAnnouncement *********************************************************/

if (!isset($_CONFIG["an_time_format"])) $_CONFIG["an_time_format"] = "H:i";

/* ModuleBlog *****************************************************************/

if (!isset($_CONFIG["bl_hierarchical_title_separator"])) $_CONFIG["bl_hierarchical_title_separator"] = ' | ';
if (!isset($_CONFIG['bl_title_available'])) $_CONFIG['bl_title_available'] = false;

/* ModuleCmsindex *************************************************************/

if (!isset($_CONFIG["ci_disk_space_usage"])) $_CONFIG["ci_disk_space_usage"] = false;

/* ModuleDownloadTicker *******************************************************/

if (!isset($_CONFIG["dt_number_of_topdownloads"])) $_CONFIG["dt_number_of_topdownloads"] = 4;

/* ModuleEmployee *************************************************************/

if (!isset($_CONFIG['ee_no_random'])) $_CONFIG['ee_no_random'][0] = false;
if (!isset($_CONFIG['ee_hidden_fields'])) $_CONFIG['ee_hidden_fields'][0] = array();

/* ModuleFrontendUserManagement ***********************************************/

if (!isset($_CONFIG["fu_results_per_page"])) $_CONFIG["fu_results_per_page"] = 20;

/* ModuleFrontendUserManagementCompany ****************************************/

if (!isset($_CONFIG["fu_cp_results_per_page"])) $_CONFIG["fu_cp_results_per_page"] = 20;

/* ModuleGlobalAreaManagement *************************************************/

if (!isset($_CONFIG['ga_number_of_boxes'])) $_CONFIG['ga_number_of_boxes'] = array();
if (!isset($_CONFIG['ga_type_of_boxes'])) $_CONFIG['ga_type_of_boxes'] = array();
if (!isset($_CONFIG['ga_assignments_available'])) $_CONFIG['ga_assignments_available'] = array();
// The Global Area images must not have large images.
if (!isset($_CONFIG['ga_area_image_width'])) $_CONFIG['ga_area_image_width'] = 60;
if (!isset($_CONFIG['ga_area_image_height'])) $_CONFIG['ga_area_image_height'] = 80;
if (!isset($_CONFIG['ga_area_large_image_width'])) $_CONFIG['ga_area_large_image_width'] = $_CONFIG['ga_area_image_width'];
if (!isset($_CONFIG['ga_area_large_image_height'])) $_CONFIG['ga_area_large_image_height'] = $_CONFIG['ga_area_image_height'];
// The Global Area Box images must not have large images.
if (!isset($_CONFIG['ga_area_box_image_width'])) $_CONFIG['ga_area_box_image_width'] = 60;
if (!isset($_CONFIG['ga_area_box_image_height'])) $_CONFIG['ga_area_box_image_height'] = 80;
if (!isset($_CONFIG['ga_area_box_large_image_width'])) $_CONFIG['ga_area_box_large_image_width'] = $_CONFIG['ga_area_box_image_width'];
if (!isset($_CONFIG['ga_area_box_large_image_height'])) $_CONFIG['ga_area_box_large_image_height'] = $_CONFIG['ga_area_box_image_height'];
if (!isset($_CONFIG['ga_area_box_timing_activated'])) $_CONFIG['ga_area_box_timing_activated'] = false;

/* ModuleHtmlCreator **********************************************************/

if (!isset($_CONFIG['hc_config'])) $_CONFIG['hc_config'] = array(

  // configuration available for all sites
  // change to site id to create site specific configuration
  0 => array(

    // the unique identifier for this newsletter template
    'default' => array(

      // the label displayed in the template selection
      'label' => 'Standard',

      // the template filename to use
      'template' => '0-default',

      // available fields ( displayed in the html creator form )
      'fields' => array(

        'title1',
        'text1',
        'text2',
        'image1',
        'image2',

      ),

      // labels for available fields
      'lang' => array(

        'title1' => 'Titel',
        'text1'  => 'Einleitungstext',
        'image1' => 'Headerbild ( oben unter Logoleiste )',
        'image2' => 'Breites Bild ( unter Einleitungstext )',
        'text2'  => 'Zentrierter Text ( unter Einleitungstext )'

      ),

      // actual configuration
      'config' => array(

        'image' => array(

          // default common area image width and height for image 1 - 3
          // use index 1 - 3 to define image specific dimensions
          0 => array(

            'width'  => 580,
            'height' => array(230, 580),

          ),

//          1 => array(
//
//            'width' => 580,
//            'height' => 230,
//
//          ),

        ),

        // the number of boxes this html creator template allows
        'number_of_boxes' => 4,
      ),

      // boxes configuration
      // the place to define various box type configurations
      'boxes' => array(

        // the unique box type identifier for this newsletter template
        //
        // within the database the box template for this configuration is going to
        // be 'default.full-width-content'
        //
        'full-width-content' => array(

          // the label displayed in the box type selection
          'label' => 'Inhalt über volle Breite',

          'fields' => array(
            'title',
            'text',
            'image',
          ),

          'lang' => array(
            'title' => 'Überschrift ( unter Bild )',
            'image' => 'Bild ( ganz oben )',
            'text'  => 'Inhalt ( nach Bild und Überschrift )',
          ),

          'config' => array(
            'image' => array(
              'width'  => 580,
              'height' => array(230, 580),
            ),
          ),
        ),

        '2-column-image-left' => array(

          'label' => '2-spaltig mit Bild links',

          'fields' => array(
            'title',
            'text',
            'image',
            'url',
          ),

          'lang' => array(
            'title' => 'Überschrift ( rechte Spalte)',
            'image' => 'Bild ( linke Spalte )',
            'text'  => 'Inhalt ( rechte Spalte unter Überschrift )',
            'url'   => 'Url für Button',
          ),

          'config' => array(
            'image' => array(
              'width'  => 280,
              'height' => array(120, 400),
            ),
          ),
        ),

        '2-column-image-right' => array(

          'label' => '2-spaltig mit Bild rechts',

          'fields' => array(
            'title',
            'text',
            'image',
            'url',
          ),

          'lang' => array(
            'title' => 'Überschrift ( linke Spalte)',
            'image' => 'Bild ( rechte Spalte )',
            'text'  => 'Inhalt ( linke Spalte unter Überschrift )',
            'url'   => 'Url für Button',
          ),

          'config' => array(
            'image' => array(
              'width'  => 280,
              'height' => array(120, 400),
            ),
          ),
        ),
      ),
    ),
  ),
);

/* ModuleLeadManagement *******************************************************/

if (!isset($_CONFIG['ld_infocenter_percent_max_width'])) $_CONFIG['ld_infocenter_percent_max_width'] = 100;
if (!isset($_CONFIG['ld_appointment_day_start'])) $_CONFIG['ld_appointment_day_start'] = '08:00';
if (!isset($_CONFIG['ld_appointment_day_end'])) $_CONFIG['ld_appointment_day_end'] = '17:00';
if (!isset($_CONFIG['ld_appointment_duration'])) $_CONFIG['ld_appointment_duration'] = 15;
if (!isset($_CONFIG['ld_appointment_lunchtime_start'])) $_CONFIG['ld_appointment_lunchtime_start'] = '12:00';
if (!isset($_CONFIG['ld_appointment_lunchtime_end'])) $_CONFIG['ld_appointment_lunchtime_end'] = '13:00';

/* ModuleLeadManagementClient *************************************************/

if (!isset($_CONFIG['ln_appointment_list_shorttext_maxlength'])) $_CONFIG['ln_appointment_list_shorttext_maxlength'] = array(0 => 55);
if (!isset($_CONFIG['ln_validation_required_field_message_summary'])) $_CONFIG['ln_validation_required_field_message_summary'] = false;

/* ModuleLeagueManager ********************************************************/

if (!isset($_CONFIG["lm_mode"])) $_CONFIG["lm_mode"] = "soccer";
switch($_CONFIG["lm_mode"]){
  case "soccer":
    if (!isset($_CONFIG["lm_active_game_max_duration"])) $_CONFIG["lm_active_game_max_duration"] = 150;
    if (!isset($_CONFIG["lm_icons"])) $_CONFIG["lm_icons"] = array ( 1 => "pix/lm_noting.gif", 2 => "pix/lm_goal.gif", 3 => "pix/lm_action.gif", 4 => "pix/lm_chance.gif", 5 => "pix/lm_yellowcard.gif", 6 => "pix/lm_yellowredcard.gif", 7 => "pix/lm_redcard.gif", 8 => "pix/lm_substitution.gif" );
    break;
  case "basketball":
    if (!isset($_CONFIG["lm_active_game_max_duration"])) $_CONFIG["lm_active_game_max_duration"] = 100;
    if (!isset($_CONFIG["lm_icons"])) $_CONFIG["lm_icons"] = array ( 1 => "pix/lm_noting.gif", 2 => "pix/lm_goal.gif", 3 => "pix/lm_action.gif", 4 => "pix/lm_chance.gif", 5 => "pix/lm_yellowcard.gif", 6 => "pix/lm_yellowredcard.gif", 7 => "pix/lm_redcard.gif", 8 => "pix/lm_substitution.gif" );
    break;
}
if (!isset($_CONFIG["lm_active_game_start"])) $_CONFIG["lm_active_game_start"] = 30;

/* ModuleLookupManagement *****************************************************/

if (!isset($_CONFIG['lu_image_width'])) $_CONFIG['lu_image_width'] = 250;
if (!isset($_CONFIG['lu_image_height'])) $_CONFIG['lu_image_height'] = 250;
$_CONFIG['lu_large_image_width'] = $_CONFIG['lu_image_width'];
$_CONFIG['lu_large_image_height'] = $_CONFIG['lu_image_height'];

/* ModuleMediaManagement ******************************************************/

if (!isset($_CONFIG["mm_results_per_page"])) $_CONFIG["mm_results_per_page"] = 15;
if (!isset($_CONFIG['mm_file_size'])) $_CONFIG['mm_file_size'] = $_CONFIG['fi_file_size'];
if (!isset($_CONFIG['mm_file_types'])) $_CONFIG['mm_file_types'] = $_CONFIG['fi_file_types'];
if (!isset($_CONFIG["mm_show_always_default"])) $_CONFIG["mm_show_always_default"] = false;
if (!isset($_CONFIG["mm_file_upload_on_issuu"])) $_CONFIG["mm_file_upload_on_issuu"] = false;

/* ModuleMediaManagementAll ***************************************************/

if (!isset($_CONFIG["ml_results_per_page"])) $_CONFIG["ml_results_per_page"] = 15;

/* ModuleMediaManagementNode **************************************************/

if (!isset($_CONFIG["mn_results_per_page"])) $_CONFIG["mn_results_per_page"] = 15;

/* ModuleMultimediaLibrary ****************************************************/

if (!isset($_CONFIG['ms_video_types'])) $_CONFIG['ms_video_types'] = array(
  'youtube' => array(
    'data_regex' => '/^https?\:\/\/www\.youtube\.com\/watch\?v\=([^&]+)/',
    'data_format' => 'http://www.youtube.com/watch?v=%s',
    'url' => 'http://www.youtube.com/watch?v=%s',
    // @see http://code.google.com/intl/uk-US/apis/youtube/getting_started.html
    'videos_api' => 'http://gdata.youtube.com/feeds/api/videos',
),
  'vimeo' => array(
    'data_regex' => '/^https?\:\/\/(?:www\.)?vimeo\.com\/(\d+)/',
    'data_format' => 'http://vimeo.com/%s',
    'url' => 'http://vimeo.com/%s',
),
  'myspace' => array(
    'data_regex' => '/^https?\:\/\/www\.myspace\.com\/.*\/+(\d+)/',
    'data_format' => 'http://www.myspace.com/video/vid/%s',
    'url' => 'http://www.myspace.com/video/vid/%s',
),
  'myvideo' => array(
    'data_regex' => '/^https?\:\/\/www\.myvideo\.([^\/]+)\/watch\/(\d+)/',
    'data_format' => 'http://www.myvideo.%s/watch/%s',
    'url' => 'http://www.myvideo.%s/watch/%s',
),
);
if (!isset($_CONFIG['ms_video_types_available'])) $_CONFIG['ms_video_types_available'] = array('youtube', 'vimeo', 'myspace', 'myvideo');
if (!isset($_CONFIG['ms_insert_box_at_top_position'])) $_CONFIG['ms_insert_box_at_top_position'] = array(0 => false);
if (!isset($_CONFIG['ms_list_selected_default_category'])) $_CONFIG['ms_list_selected_default_category'] = array(0 => 0);
if (!isset($_CONFIG['ms_available_tabs'])) $_CONFIG['ms_available_tabs'] = array('image', 'video', 'document');
if (!isset($_CONFIG['ms_file_size'])) $_CONFIG['ms_file_size'] = $_CONFIG['fi_file_size'];
if (!isset($_CONFIG['ms_file_types'])) $_CONFIG['ms_file_types'] = $_CONFIG['fi_file_types'];
if (!isset($_CONFIG['ms_random_box_for_mixed_category'])) $_CONFIG['ms_random_box_for_mixed_category'] = array(0 => false);
if (!isset($_CONFIG['ms_random_box_for_mixed_category_randomly_shown_by_default'])) $_CONFIG['ms_random_box_for_mixed_category_randomly_shown_by_default'] = array(0 => true);
if (!isset($_CONFIG['ms_timing_activated'])) $_CONFIG['ms_timing_activated'] = false;
if (!isset($_CONFIG["ms_results_per_page"])) $_CONFIG["ms_results_per_page"] = 25;

/* ModuleOrderManagament ******************************************************/

if (!isset($_CONFIG["om_item_csv_format"])) $_CONFIG["om_item_csv_format"] = "%s x %s ;";
if (!isset($_CONFIG["om_currency_format"])) $_CONFIG["om_currency_format"] = "%.2f";
if (!isset($_CONFIG["sc_order_id_format"])) $_CONFIG["sc_order_id_format"] = '%09d';

/* ModulePopUp ****************************************************************/
if (!isset($_CONFIG["pu_options"])) $_CONFIG['pu_options'][0] = array(
  'show_up_seconds' => array('type'       => 'text',
                             'value'      => '0' ),
  'hidden_seconds'  => array('type'       => 'select'),
);

/* ModuleReseller *************************************************************/

// Default definition of the delimiter for export files (csv)
if (!isset($_CONFIG['rm_export_glue'])) $_CONFIG['rm_export_glue'] = ';';
if (!isset($_CONFIG['rm_export_area_ids'])) $_CONFIG['rm_export_area_ids'] = false;
if (!isset($_CONFIG['rm_image_width'])) $_CONFIG['rm_image_width'] = 100;
if (!isset($_CONFIG['rm_image_height'])) $_CONFIG['rm_image_height'] = 100;
$_CONFIG['rm_large_image_width'] = $_CONFIG['rm_image_width'];
$_CONFIG['rm_large_image_height'] = $_CONFIG['rm_image_height'];

/* ModuleShopPlusManagement ***************************************************/

if (!isset($_CONFIG["cp_order_invoice_file"])) $_CONFIG["cp_order_invoice_file"] = "files/shop/invoice";
if (!isset($_CONFIG["cp_order_number_file_format"])) $_CONFIG["cp_order_number_file_format"] = "///%09d/"; //<chars>/<date>/<chars>/<number>/<chars>
if (!isset($_CONFIG["cp_order_number_invoice_format"])) $_CONFIG["cp_order_number_invoice_format"] = "///%09d/"; //<chars>/<date>/<chars>/<number>/<chars>
if (!isset($_CONFIG["cp_currency_format"])) $_CONFIG["cp_currency_format"] = "%.2f";
if (!isset($_CONFIG["cp_order_number_offset_value"])) $_CONFIG["cp_order_number_offset_value"] = 0;
if (!isset($_CONFIG["op_results_per_page"])) $_CONFIG["op_results_per_page"] = 25;
if (!isset($_CONFIG["cp_tax_rates"])) $_CONFIG["cp_tax_rates"] = array();

/* ModuleSearch ***************************************************************/

if (!isset($_CONFIG['sh_results_per_page'])) $_CONFIG['sh_results_per_page'] = 10;

/* ModuleSidebox **************************************************************/

if (!isset($_CONFIG['sb_no_random'])) $_CONFIG['sb_no_random'][0] = false;

/* ModuleSiteindexCompendium **************************************************/

// The SiteindexCompendium images must not have large images.
// (not supported at the FE)
if (!isset($_CONFIG['si_image_width'])) $_CONFIG['si_image_width'] = 320;
if (!isset($_CONFIG['si_image_height'])) $_CONFIG['si_image_height'] = 240;
if (!isset($_CONFIG['si_large_image_width'])) $_CONFIG['si_large_image_width'] = $_CONFIG['si_image_width'];
if (!isset($_CONFIG['si_large_image_height'])) $_CONFIG['si_large_image_height'] = $_CONFIG['si_image_height'];
if (!isset($_CONFIG['si_number_of_boxes'])) $_CONFIG['si_number_of_boxes'] = array();
if (!isset($_CONFIG['si_type_of_boxes'])) $_CONFIG['si_type_of_boxes'] = array();
// The SiteindexCompendium area images must not have large images.
// (not supported at the FE)
if (!isset($_CONFIG['si_area_image_width'])) $_CONFIG['si_area_image_width'] = 60;
if (!isset($_CONFIG['si_area_image_height'])) $_CONFIG['si_area_image_height'] = 80;
if (!isset($_CONFIG['si_area_large_image_width'])) $_CONFIG['si_area_large_image_width'] = $_CONFIG['si_area_image_width'];
if (!isset($_CONFIG['si_area_large_image_height'])) $_CONFIG['si_area_large_image_height'] = $_CONFIG['si_area_image_height'];
// The SiteindexCompendium box images must not have large images.
// (not supported at the FE)
if (!isset($_CONFIG['si_area_box_image_width'])) $_CONFIG['si_area_box_image_width'] = 60;
if (!isset($_CONFIG['si_area_box_image_height'])) $_CONFIG['si_area_box_image_height'] = 80;
if (!isset($_CONFIG['si_area_box_large_image_width'])) $_CONFIG['si_area_box_large_image_width'] = $_CONFIG['si_area_box_image_width'];
if (!isset($_CONFIG['si_area_box_large_image_height'])) $_CONFIG['si_area_box_large_image_height'] = $_CONFIG['si_area_box_image_height'];

/* ModuleSiteindexCompendiumMobile ********************************************/

if (!isset($_CONFIG['si_mobile_buttons']) || !isset($_CONFIG['si_mobile_buttons'][0])) $_CONFIG['si_mobile_buttons'][0] = array(
  'mobile'   => array('template' => '<div class="icon"><div class="button"><a href="tel:%s" class="fa fa-mobile"></a></div></div>'),
  'phone'    => array('template' => '<div class="icon"><div class="button"><a href="tel:%s" class="fa fa-phone"></a></div></div>'),
  'email'    => array('template' => '<div class="icon"><div class="button"><a href="mailto:%s" class="fa fa-envelope"></a></div></div>'),
  'maps'     => array('template' => '<div class="icon"><div class="button"><a href="%s" target="_blank" class="fa fa-map-marker"></a></div></div>'),
  'maps_alt' => array('template' => '<div class="icon"><div class="button"><a href="%s" target="_blank" class="fa fa-map-marker"></a></div></div>'),
  'facebook' => array('template' => '<div class="icon"><div class="button"><a href="%s" target="_blank" class="fa fa-facebook"></a></div></div>'),
  'xing'     => array('template' => '<div class="icon"><div class="button"><a href="%s" target="_blank" class="fa fa-xing"></a></div></div>'),
  'twitter'  => array('template' => '<div class="icon"><div class="button"><a href="%s" target="_blank" class="fa fa-twitter"></a></div></div>'),
  'youtube'  => array('template' => '<div class="icon"><div class="button"><a href="%s" target="_blank" class="fa fa-youtube"></a></div></div>'),
  'skype'    => array('template' => '<div class="icon"><div class="button"><a href="skype:%s?call" class="fa fa-skype"></a></div></div>'),
);
if (!isset($_CONFIG['si_mobile_max_items']) || !isset($_CONFIG['si_mobile_max_items'][0])) $_CONFIG['si_mobile_max_items'][0] = 4;

/*******************************************************************************

 PROJECTS

 Project specific default values from here:

*******************************************************************************/

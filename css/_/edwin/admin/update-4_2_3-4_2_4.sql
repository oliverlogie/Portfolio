/**
 * [INFO]
 *
 * Folgende *deprecated* Template Variablen wurde vom System entfernt und
 * müssen durch die entsprechenden aktuellen Variablen ersetzt werden:
 *
 * ContentItemBG
 *   IF 'zoom' -> 'gallery_zoom'
 *   'c_bg_zoom_link' -> 'c_bg_gallery_zoom_link'
 *	 VARS
 *   'c_bg_image_src' -> 'c_bg_gallery_image_src'
 *	 'c_bg_title' -> 'c_bg_title1'
 *	 'c_bg_title_plain' ->
 *
 * ContentItemDL
 *   IF 'area_image'
 *   	'c_dl_image_src' -> 'c_dl_area_image_src'
 *	 LOOP 'download_items' -> 'area_file_items'
 *
 * ContentItemDL Area
 *	 'c_dl_atitle' -> 'c_dl_area_title'
 * 	 'c_dl_atext' -> 'c_dl_area_text'
 *
 * ContentItemDL Files
 *	 'c_dl_title' -> 'c_dl_area_file_title'
 *	 'c_dl_link' -> 'c_dl_area_file_link'
 *
 * ContentItemQS
 *	 VARS
 *	 'c_qs_title' -> 'c_qs_title1'
 *	 'c_qs_image_src' -> 'c_qs_image_src1'
 *
 * ContentItemQS Statement
 *	 'c_qs_stitle' -> 'c_qs_statement_title'
 *	 'c_qs_stext' -> 'c_qs_statement_text'
 *   IF 'zoom'
 *	 'c_qs_zoom_link' -> 'c_qs_statement_zoom_link'
 *   IF 'statement_image'
 *	 'c_qs_image_src' -> 'c_qs_statement_image_src'
 *
 * ContentItemTG
 *   'zoom1' ->
 *     'c_tg_zoom1_link' ->
 *   'image1' ->
 *	   'c_tg_image_src1' ->
 *
 * ContentItem
 *   'li_label' -> 'il_label'
 *   'li_link' -> 'il_link'
 *   'le_label' -> 'el_label'
 *   'le_link' -> 'el_link'
 *
 * ContentRequest
 *    'm_nv_label' -> 'm_nv_title'
 *	  'm_nv_link' -> 'm_nv_url'
 *    'm_nv_link_length' -> 'm_nv_title_length'
 *    'm_nv_class' ->
 *	  'm_nv_id' -> 'm_nv_position'
 *    'm_nv_site_label' -> 'm_nv_site_title'
 *    'm_nv_parrent_label' -> 'm_nv_parent_title'
 *    'm_nv_parrent_link_length' -> 'm_nv_parent_title_length'
 *	  'm_nv_parrent_link' -> 'm_nv_parent_url'
 *
 * AbstractModuleNestedNavigation
 *   'm_nv_link' -> 'm_nv_url'
 *   'm_nv_label' -> 'm_nv_title'
 *	 'm_nv_class' ->
 *   'm_nv_topid' ->
 *
 * ModuleLanguageSwitch
 *   'm_link' -> 'c_ls_url'
 *   'm_label' -> 'c_ls_title'
 *   'm_shortlabel' -> 'c_ls_language'
 *   'm_class' ->
 *   'm_sid' -> 'c_ls_sid'
 *
 * ModuleSideBox
 *   'm_title1' -> 'c_sb_title1'
 *   'm_title2' -> 'c_sb_title2'
 *   'm_title3' -> 'c_sb_title3'
 *   'm_text1' -> 'c_sb_text1'
 *	 'm_text2' -> 'c_sb_text2'
 *   'm_text3' -> 'c_sb_text3'
 *   'm_image_src1' -> 'c_sb_image_src1'
 *   'm_image_src2' -> 'c_sb_image_src2'
 *   'm_image_src3' -> 'c_sb_image_src3'
 *   'm_link' -> 'c_sb_link'
 *
 * ModuleSitesMenu
 *   'm_link' -> 'c_sm_url'
 *	 'm_label' -> 'c_sm_title'
 * 	 'm_sid' -> 'c_sm_sid'
 *
 * ModuleSiteindexCompendium
 *   'area_box{c_si_area_box_id}_image' -> 'area_box{c_si_area_box_id}_image1'
 *   'c_si_area_box_title' -> 'c_si_area_box_title1'
 *   'c_si_area_box_text' -> 'c_si_area_box_text1'
 *   'c_si_area_box_image_src' -> 'c_si_area_box_image_src1'
 *
 * Folgenede Template Variablen, die aus $_LANG geparst wurden werden nicht mehr
 * geparst:
 *     - m_footertext
 *     - main_additional_text1
 *     - main_additional_text2
 *     - main_additional_text3
 *     - main_additional_text4
 *     - main_additional_text5
 *     - main_additional_text6
 *     - main_additional_text7
 *     - main_additional_text8
 *     - main_additional_text9
 *
 * [/INFO]
 */

/******************************************************************************/
/* Indizes in mc_user_rights Tabelle setzen                                   */
/******************************************************************************/

ALTER TABLE mc_user_rights ADD INDEX (FK_UID);
ALTER TABLE mc_user_rights ADD INDEX (FK_SID);
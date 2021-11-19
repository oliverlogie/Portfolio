/**
 * [INFO]
 *
 * MYSQL sql_mode 'NO_ZERO_DATE' Kompatibilität herstellen:
 *
 * Bei Kundenprojekten muss bei kundenspezifischen Entwicklungen wie Inhaltstypen,
 * Modulen oder anderen Komponenten die Kompatibilität manuell hergestellt oder
 * ein Vermerk beim Projekt gemacht werden, dass die 'NO_ZERO_DATE' noch nicht
 * sichergestellt wurde.
 *
 * Die AbstractModel Basisklasse für Models setzt nun den Wert für ein Datum
 * automatisch auf NULL, wenn es kein Datumswert ist, da 0000-00-00 00:00:00
 * nicht mehr als Standardwert in der Datenbank verwendet wird. Die Datenbanktabellen
 * bei kundenspezifischen Entwicklungen müssen entsprechend angepasst werden
 * (siehe SQL Update Statements unten).
 *
 * ----------------------------------------
 * Bug: MYSQL Problem bei *Strict SQL Mode*
 *
 * Zur Sicherstellung der EDWIN Kompatibilität mit dem *Strict SQL Mode* von
 * MYSQL werden für alle notwendigen Datenbankspalten Standardwerte hinterlegt.
 * TEXT/BLOG Felder wurden zu *DEFAULT NULL* geändert, da ein Standardwert auf
 * Windows Systemen nicht möglich ist.
 *
 * Bei Kundenprojekten muss bei kundenspezifischen Entwicklungen wie Inhaltstypen,
 * Modulen oder anderen Komponenten die Kompatibilität manuell hergestellt oder
 * ein Vermerk beim Projekt gemacht werden, dass die *Strict SQL Mode*
 * Kompatibilität noch nicht sichergestellt wurde.
 *
 * Hinweis zu Models: die Model Felder müssen in der Model-Klasse korrekt als
 * InterfaceField::TYPE_CHECKBOX bzw. InterfaceField::TYPE_DATETIME damit das
 * Model die Daten korrekt speichern kann.
 *
 * [/INFO]
 */

/******************************************************************************/
/* Edwin Kompatibilität mit MYSQL 5.7 herstellen - sql_mode = 'NO_ZERO_DATE'  */
/******************************************************************************/

ALTER TABLE mc_campaign_lead_appointment
CHANGE CGLACreateDateTime CGLACreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE CGLADateTime CGLADateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_campaign_lead_appointment SET CGLACreateDateTime = NULL
WHERE CGLACreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_campaign_lead_appointment SET CGLADateTime = NULL
WHERE CGLADateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_campaign_lead_manipulated_log
CHANGE CGLMLDateTime CGLMLDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_campaign_lead_manipulated_log SET CGLMLDateTime = NULL
WHERE CGLMLDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_campaign_lead_status
CHANGE CGLSDateTime CGLSDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_campaign_lead_status SET CGLSDateTime = NULL
WHERE CGLSDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_centralfile
CHANGE CFCreated CFCreated DATETIME NULL DEFAULT NULL,
CHANGE CFModified CFModified DATETIME NULL DEFAULT NULL;

UPDATE mc_centralfile SET CFCreated = NULL
WHERE CFCreated = '0000-00-00 00:00:00';
UPDATE mc_centralfile SET CFModified = NULL
WHERE CFModified = '0000-00-00 00:00:00';

ALTER TABLE mc_client
CHANGE CNewsletterOptInSuccessDateTime CNewsletterOptInSuccessDateTime DATETIME NULL DEFAULT NULL,
CHANGE CCreateDateTime CCreateDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_client SET CNewsletterOptInSuccessDateTime = NULL
WHERE CNewsletterOptInSuccessDateTime = '0000-00-00 00:00:00';
UPDATE mc_client SET CCreateDateTime = NULL
WHERE CCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_client SET CBirthday = NULL
WHERE CBirthday = '0000-00-00 00:00:00';

ALTER TABLE mc_client_actions
CHANGE CADateTime CADateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_client_actions SET CADateTime = NULL
WHERE CADateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_client_uploads
CHANGE CUCreateDateTime CUCreateDateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_comments
CHANGE CCreateDateTime CCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE CChangeDateTime CChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_comments SET CCreateDateTime = NULL
WHERE CCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_comments SET CChangeDateTime = NULL
WHERE CChangeDateTime = '0000-00-00 00:00:00';

UPDATE mc_contentitem SET CShowFromDate = NULL
WHERE CShowFromDate = '0000-00-00 00:00:00';
UPDATE mc_contentitem SET CShowUntilDate = NULL
WHERE CShowUntilDate = '0000-00-00 00:00:00';
UPDATE mc_contentitem SET CCreateDateTime = NULL
WHERE CCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_contentitem SET CChangeDateTime = NULL
WHERE CChangeDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_contentitem_bg_image
CHANGE BICreateDateTime BICreateDateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_contentitem_cp_order
CHANGE CPOCreateDateTime CPOCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE CPOChangeDateTime CPOChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_contentitem_cp_order SET CPOCreateDateTime = NULL
WHERE CPOCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_contentitem_cp_order SET CPOChangeDateTime = NULL
WHERE CPOChangeDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_contentitem_cp_order_customer
CHANGE CPOCCreateDateTime CPOCCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE CPOCChangeDateTime CPOCChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_contentitem_cp_order_customer SET CPOCCreateDateTime = NULL
WHERE CPOCCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_contentitem_cp_order_customer SET CPOCChangeDateTime = NULL
WHERE CPOCChangeDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_contentitem_cp_order_shipping_address
CHANGE CPOSCreateDateTime CPOSCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE CPOSChangeDateTime CPOSChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_contentitem_cp_order_shipping_address SET CPOSCreateDateTime = NULL
WHERE CPOSCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_contentitem_cp_order_shipping_address SET CPOSChangeDateTime = NULL
WHERE CPOSChangeDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_contentitem_dl_area_file
CHANGE DFCreated DFCreated DATETIME NULL DEFAULT NULL,
CHANGE DFModified DFModified DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_contentitem_log
CHANGE LDateTime LDateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_contentitem_sc_order
CHANGE SOCreateDateTime SOCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE SOChangeDateTime SOChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_contentitem_sc_order SET SOCreateDateTime = NULL
WHERE SOCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_contentitem_sc_order SET SOChangeDateTime = NULL
WHERE SOChangeDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_contentitem_tg_image
CHANGE TGICreateDateTime TGICreateDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_contentitem_tg_image SET TGICreateDateTime = NULL
WHERE TGICreateDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_cron_log
CHANGE CDateTime CDateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_download_log
CHANGE DLDatetime DLDatetime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_file
CHANGE FCreated FCreated DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_frontend_user
CHANGE FULastLogin FULastLogin DATETIME NULL DEFAULT NULL;

UPDATE `mc_frontend_user` SET FUBirthday = NULL
WHERE FUBirthday = '0000-00-00';
UPDATE `mc_frontend_user` SET FUCreateDateTime = NULL
WHERE FUCreateDateTime = '0000-00-00';
UPDATE `mc_frontend_user` SET FUChangeDateTime = NULL
WHERE FUChangeDateTime = '0000-00-00';
UPDATE mc_frontend_user SET FULastLogin = NULL
WHERE FULastLogin = '0000-00-00 00:00:00';

ALTER TABLE mc_frontend_user_company
CHANGE FUCCreateDatetime FUCCreateDatetime DATETIME NULL DEFAULT NULL,
CHANGE FUCChangeDatetime FUCChangeDatetime DATETIME NULL DEFAULT NULL;

UPDATE mc_frontend_user_company SET FUCCreateDatetime = NULL
WHERE FUCCreateDatetime = '0000-00-00 00:00:00';
UPDATE mc_frontend_user_company SET FUCChangeDatetime = NULL
WHERE FUCChangeDatetime = '0000-00-00 00:00:00';

ALTER TABLE mc_frontend_user_history_download
CHANGE FUHDDatetime FUHDDatetime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_frontend_user_history_login
CHANGE FUHLDatetime FUHLDatetime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_frontend_user_log
CHANGE FULastLogin FULastLogin DATETIME NULL DEFAULT NULL,
CHANGE FULogDateTime FULogDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_frontend_user_log SET FULastLogin = NULL
WHERE FULastLogin = '0000-00-00 00:00:00';
UPDATE mc_frontend_user_log SET FUBirthday = NULL
WHERE FUBirthday = '0000-00-00 00:00:00';

ALTER TABLE mc_frontend_user_sessions
CHANGE FUSLastAction FUSLastAction DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_issuu_document
CHANGE IDCreateDateTime IDCreateDateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_log_simple
CHANGE DateTime DateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_module_global_area_box
CHANGE GABShowFromDateTime GABShowFromDateTime DATETIME NULL DEFAULT NULL,
CHANGE GABShowUntilDateTime GABShowUntilDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_global_area_box SET GABShowFromDateTime = NULL
WHERE GABShowFromDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_global_area_box SET GABShowUntilDateTime = NULL
WHERE GABShowUntilDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_module_html_creator
CHANGE HCCreateDateTime HCCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE HCChangeDateTime HCChangeDateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_module_html_creator_export_log
CHANGE HCELDateTime HCELDateTime DATETIME NULL DEFAULT NULL;

ALTER TABLE mc_module_leaguemanager_game
CHANGE GDateTime GDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_leaguemanager_game SET GDateTime = NULL
WHERE GDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_module_leaguemanager_year
CHANGE YStartDate YStartDate DATE NULL DEFAULT NULL,
CHANGE YEndDate YEndDate DATE NULL DEFAULT NULL;

ALTER TABLE mc_module_medialibrary
CHANGE MCreateDateTime MCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE MChangeDateTime MChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_medialibrary SET MCreateDateTime = NULL
WHERE MCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_medialibrary SET MChangeDateTime = NULL
WHERE MChangeDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_medialibrary SET MShowFromDateTime = NULL
WHERE MShowFromDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_medialibrary SET MShowUntilDateTime = NULL
WHERE MShowUntilDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_medialibrary SET MVideoPublishedDate1 = NULL
WHERE MVideoPublishedDate1 = '0000-00-00 00:00:00';
UPDATE mc_module_medialibrary SET MVideoPublishedDate2 = NULL
WHERE MVideoPublishedDate2 = '0000-00-00 00:00:00';
UPDATE mc_module_medialibrary SET MVideoPublishedDate3 = NULL
WHERE MVideoPublishedDate3 = '0000-00-00 00:00:00';

ALTER TABLE mc_module_news
CHANGE NWStartDateTime NWStartDateTime DATETIME NULL DEFAULT NULL,
CHANGE NWEndDateTime NWEndDateTime DATETIME NULL DEFAULT NULL,
CHANGE NWCreateDateTime NWCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE NWChangeDateTime NWChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_news SET NWStartDateTime = NULL
WHERE NWStartDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_news SET NWEndDateTime = NULL
WHERE NWEndDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_news SET NWCreateDateTime = NULL
WHERE NWCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_news SET NWChangeDateTime = NULL
WHERE NWChangeDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_module_newsletter_export
CHANGE EDateTime EDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_newsletter_export SET EDateTime = NULL
WHERE EDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_module_order_export
CHANGE EDateTime EDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_order_export SET EDateTime = NULL
WHERE EDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_module_popup
CHANGE PUCreateDateTime PUCreateDateTime DATETIME NULL DEFAULT NULL,
CHANGE PUChangeDateTime PUChangeDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_popup SET PUCreateDateTime = NULL
WHERE PUCreateDateTime = '0000-00-00 00:00:00';
UPDATE mc_module_popup SET PUChangeDateTime = NULL
WHERE PUChangeDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_module_shopplusmgmt_log
CHANGE LDateTime LDateTime DATETIME NULL DEFAULT NULL;

UPDATE mc_module_shopplusmgmt_log SET LDateTime = NULL
WHERE LDateTime = '0000-00-00 00:00:00';

ALTER TABLE mc_user
CHANGE ULastLogin ULastLogin DATETIME NULL DEFAULT NULL;

UPDATE mc_user SET ULastLogin = NULL
WHERE ULastLogin = '0000-00-00 00:00:00';

/******************************************************************************/
/* Bug: MYSQL Problem bei *Strict SQL Mode*                                   */
/******************************************************************************/

ALTER TABLE `mc_blocked_users`
CHANGE `BSection` `BSection` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `BIP` `BIP` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_campaign_attached`
CHANGE `CGAAdditionalDataOrigin` `CGAAdditionalDataOrigin` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CGARecipients` `CGARecipients` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_campaign_contentitem`
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CGID` `FK_CGID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_campaign_data`
CHANGE `CGDFiletypes` `CGDFiletypes` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CGDName` `CGDName` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CGDValue` `CGDValue` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_campaign_lead_data`
CHANGE `CGLDValue` `CGLDValue` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_campaign_lead_status`
CHANGE `CGLSText` `CGLSText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_campaign_status`
CHANGE `CGSPosition` `CGSPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_campaign_type`
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_centralfile`
CHANGE `CFTitle` `CFTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CFFile` `CFFile` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_IDID_IssuuDocument` `FK_IDID_IssuuDocument` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_client`
CHANGE `CCompany` `CCompany` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPosition` `CPosition` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CFirstname` `CFirstname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CLastName` `CLastName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CCountry` `CCountry` INT(11) NOT NULL DEFAULT '0',
CHANGE `CZIP` `CZIP` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CCity` `CCity` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAddress` `CAddress` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPhone` `CPhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CEmail` `CEmail` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_client_actions`
CHANGE `FK_CID` `FK_CID` INT(11) NOT NULL DEFAULT '0',
CHANGE `CAAction` `CAAction` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAActionID` `CAActionID` INT(11) NOT NULL DEFAULT '0',
CHANGE `CAActionText` `CAActionText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_client_uploads`
CHANGE `FK_CID` `FK_CID` INT(11) NOT NULL DEFAULT '0',
CHANGE `CUFile` `CUFile` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentabstract`
CHANGE `CImageBlog` `CImageBlog` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAdditionalText` `CAdditionalText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_contentitem`
CHANGE `CSEODescription` `CSEODescription` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_contentitem_be`
CHANGE `BTitle` `BTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `BImage` `BImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_bg`
CHANGE `GTitle1` `GTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `GTitle2` `GTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `GTitle3` `GTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `GImage` `GImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_bg_image`
CHANGE `BITitle` `BITitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `BIImage` `BIImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `BIImageTitle` `BIImageTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `BIPosition` `BIPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_bi`
CHANGE `BTitle` `BTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `BImage1` `BImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `BImage2` `BImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `BNumber` `BNumber` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_ca`
CHANGE `CATitle` `CATitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAImage1` `CAImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAImage2` `CAImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAImage3` `CAImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAText1` `CAText1` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CAText2` `CAText2` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CAText3` `CAText3` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CALink` `CALink` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_ca_area`
CHANGE `CAATitle` `CAATitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAAText` `CAAText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CAAImage` `CAAImage` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAAPosition` `CAAPosition` TINYINT(4) NOT NULL DEFAULT '0',
CHANGE `CAALink` `CAALink` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_ca_area_box`
CHANGE `CAABTitle` `CAABTitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAABText` `CAABText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CAABImage` `CAABImage` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CAABLink` `CAABLink` INT(11) NOT NULL DEFAULT '0',
CHANGE `CAABExtlink` `CAABExtlink` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_CAAID` `FK_CAAID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cb`
CHANGE `CBTitle` `CBTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CBImage` `CBImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

UPDATE `mc_contentitem_cb_box`
SET `CBBLink` = 0 WHERE CBBLink IS NULL;
ALTER TABLE `mc_contentitem_cb_box`
CHANGE `CBBTitle` `CBBTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CBBImage` `CBBImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CBBLink` `CBBLink` INT(11) NOT NULL DEFAULT '0',
CHANGE `CBBPosition` `CBBPosition` INT(11) NOT NULL DEFAULT '0';

UPDATE `mc_contentitem_cb_box_biglink`
SET `BLLink` = 0 WHERE BLLink IS NULL;
ALTER TABLE `mc_contentitem_cb_box_biglink`
CHANGE `BLImage` `BLImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `BLImageTitles` `BLImageTitles` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `BLLink` `BLLink` INT(11) NOT NULL DEFAULT '0',
CHANGE `BLPosition` `BLPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CBBID` `FK_CBBID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cb_box_smalllink`
CHANGE `SLTitle` `SLTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SLLink` `SLLink` INT(11) NOT NULL DEFAULT '0',
CHANGE `SLPosition` `SLPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CBBID` `FK_CBBID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cm`
CHANGE `CMTitle1` `CMTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CMTitle2` `CMTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CMTitle3` `CMTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CMImage1` `CMImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CMImage2` `CMImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CMImage3` `CMImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_cp`
CHANGE `CPTitle1` `CPTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPTitle2` `CPTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPTitle3` `CPTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPText1` `CPText1` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CPText2` `CPText2` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CPText3` `CPText3` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CPImage1` `CPImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPImage2` `CPImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPImage3` `CPImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_cp_cartsetting`
CHANGE `CPCTitle` `CPCTitle` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPCText` `CPCText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_contentitem_cp_info`
CHANGE `CPIName` `CPIName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_order`
CHANGE `CPOTotalPrice` `CPOTotalPrice` DOUBLE NOT NULL DEFAULT '0',
CHANGE `CPOTotalTax` `CPOTotalTax` DOUBLE NOT NULL DEFAULT '0',
CHANGE `CPOTotalPriceWithoutTax` `CPOTotalPriceWithoutTax` DOUBLE NOT NULL DEFAULT '0',
CHANGE `CPOTransactionID` `CPOTransactionID` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOTransactionNumber` `CPOTransactionNumber` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOTransactionNumberDay` `CPOTransactionNumberDay` INT(11) NOT NULL DEFAULT '0',
CHANGE `CPOTransactionSessionData` `CPOTransactionSessionData` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CPOShippingCost` `CPOShippingCost` DOUBLE NOT NULL DEFAULT '0',
CHANGE `FK_CPSID` `FK_CPSID` INT(11) NOT NULL DEFAULT '0',
CHANGE `CPOPaymentCost` `CPOPaymentCost` DOUBLE NOT NULL DEFAULT '0',
CHANGE `FK_CYID` `FK_CYID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_order_cartsetting`
CHANGE `CPOCTitle` `CPOCTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCSum` `CPOCSum` DOUBLE NOT NULL DEFAULT '0',
CHANGE `CPOCUnitPrice` `CPOCUnitPrice` DOUBLE NOT NULL DEFAULT '0',
CHANGE `FK_CPOID` `FK_CPOID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CPCID` `FK_CPCID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_order_customer`
CHANGE `CPOCCompany` `CPOCCompany` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCPosition` `CPOCPosition` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCTitle` `CPOCTitle` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCFirstname` `CPOCFirstname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCLastName` `CPOCLastName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCCountry` `CPOCCountry` INT(11) NOT NULL DEFAULT '0',
CHANGE `CPOCZIP` `CPOCZIP` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCCity` `CPOCCity` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCAddress` `CPOCAddress` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCPhone` `CPOCPhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCEmail` `CPOCEmail` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCText1` `CPOCText1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCText2` `CPOCText2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCText3` `CPOCText3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCText4` `CPOCText4` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOCText5` `CPOCText5` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_cp_order_item`
CHANGE `CPOITitle` `CPOITitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOINumber` `CPOINumber` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOISum` `CPOISum` DOUBLE NOT NULL DEFAULT '0',
CHANGE `CPOITax` `CPOITax` DOUBLE NOT NULL DEFAULT '0',
CHANGE `CPOIUnitPrice` `CPOIUnitPrice` DOUBLE NOT NULL DEFAULT '0',
CHANGE `CPOIProductPrice` `CPOIProductPrice` DOUBLE NOT NULL DEFAULT '0',
CHANGE `FK_PPPID` `FK_PPPID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CPOID` `FK_CPOID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_order_item_option`
CHANGE `CPOIOName` `CPOIOName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOIOPrice` `CPOIOPrice` DOUBLE NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_order_shipping_address`
CHANGE `CPOSCompany` `CPOSCompany` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSPosition` `CPOSPosition` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSFoa` `CPOSFoa` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSTitle` `CPOSTitle` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSFirstname` `CPOSFirstname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSLastName` `CPOSLastName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSCountry` `CPOSCountry` INT(11) NOT NULL DEFAULT '0',
CHANGE `CPOSZIP` `CPOSZIP` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSCity` `CPOSCity` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSAddress` `CPOSAddress` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSPhone` `CPOSPhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSEmail` `CPOSEmail` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSText1` `CPOSText1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSText2` `CPOSText2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSText3` `CPOSText3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSText4` `CPOSText4` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CPOSText5` `CPOSText5` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_cp_payment_type`
CHANGE `CYName` `CYName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CYClass` `CYClass` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CYText` `CYText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_contentitem_cp_payment_type_country`
CHANGE `FK_CYID` `FK_CYID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_COID` `FK_COID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_preferences`
CHANGE `CPPName` `CPPName` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_shipment_mode`
CHANGE `CPSName` `CPSName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_cp_shipment_mode_country`
CHANGE `FK_CPSID` `FK_CPSID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_COID` `FK_COID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_dl`
CHANGE `DLTitle` `DLTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `DLImage` `DLImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_dl_area`
CHANGE `DATitle` `DATitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `DAImage` `DAImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `DAPosition` `DAPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_dl_area_file`
CHANGE `DFTitle` `DFTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `DFPosition` `DFPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_DAID` `FK_DAID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_ec`
CHANGE `ECRecipient` `ECRecipient` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ECTitle` `ECTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ECImage` `ECImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_ETID` `FK_ETID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_EDID` `FK_EDID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_es`
CHANGE `ETitle1` `ETitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ETitle2` `ETitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EImage1` `EImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EImage2` `EImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EImage3` `EImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EExt` `EExt` TINYINT(4) NOT NULL DEFAULT '0',
CHANGE `EFrameHeight` `EFrameHeight` SMALLINT(6) NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_fq`
CHANGE `FQTitle1` `FQTitle1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FQTitle2` `FQTitle2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FQTitle3` `FQTitle3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FQImage1` `FQImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FQImage2` `FQImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `FQImage3` `FQImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_fq_question`
CHANGE `FQQTitle` `FQQTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FQQImage` `FQQImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FQQPosition` `FQQPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_ib`
CHANGE `ITitle` `ITitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `IImage` `IImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_ip`
CHANGE `ITitle` `ITitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `IImage` `IImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_log`
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0',
CHANGE `CIIdentifier` `CIIdentifier` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_UID` `FK_UID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_login`
CHANGE `LTitle1` `LTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle2` `LTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle3` `LTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle4` `LTitle4` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle5` `LTitle5` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle6` `LTitle6` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle7` `LTitle7` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle8` `LTitle8` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LTitle9` `LTitle9` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage1` `LImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage2` `LImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage3` `LImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage4` `LImage4` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage5` `LImage5` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage6` `LImage6` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage7` `LImage7` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage8` `LImage8` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LImage9` `LImage9` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_ls`
CHANGE `STitle1` `STitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STitle2` `STitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage1` `SImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_mb`
CHANGE `MBTitle1` `MBTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MBTitle2` `MBTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MBTitle3` `MBTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MBImage1` `MBImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MBImage2` `MBImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MBImage3` `MBImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_nl`
CHANGE `NLTitle1` `NLTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `NLTitle2` `NLTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `NLTitle3` `NLTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `NLImage` `NLImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_pa`
CHANGE `PTitle1` `PTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PTitle2` `PTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PTitle3` `PTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage1` `PImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage2` `PImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage3` `PImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_pb`
CHANGE `PBTitle` `PBTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PBImage` `PBImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_pi`
CHANGE `PTitle1` `PTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PTitle2` `PTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PTitle3` `PTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage1` `PImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage2` `PImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage3` `PImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_po`
CHANGE `PTitle` `PTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage1` `PImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage2` `PImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage3` `PImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PNumber` `PNumber` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_pp`
CHANGE `PPTitle1` `PPTitle1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPTitle2` `PPTitle2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPTitle3` `PPTitle3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPText1` `PPText1` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `PPText2` `PPText2` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `PPText3` `PPText3` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `PPImage1` `PPImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPImage2` `PPImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPImage7` `PPImage7` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPImage8` `PPImage8` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPImageTitles` `PPImageTitles` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_contentitem_pp_option_global`
CHANGE `OPCode` `OPCode` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `OPName` `OPName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `OPText` `OPText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `OPImage` `OPImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `OPPosition` `OPPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_pp_product`
CHANGE `PPPTitle` `PPPTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPPText` `PPPText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `PPPImage1` `PPPImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPPImage2` `PPPImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPPImage3` `PPPImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPPImage4` `PPPImage4` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPPImage5` `PPPImage5` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPPImage6` `PPPImage6` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PPPImageTitles` `PPPImageTitles` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `PPPAdditionalData` `PPPAdditionalData` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `PPPPosition` `PPPPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `PPPNumber` `PPPNumber` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_pt`
CHANGE `PTitle1` `PTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PTitle2` `PTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage1` `PImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage2` `PImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage3` `PImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_qp`
CHANGE `QPTitle1` `QPTitle1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPTitle2` `QPTitle2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPTitle3` `QPTitle3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage1` `QPImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage2` `QPImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage3` `QPImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage4` `QPImage4` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage5` `QPImage5` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage6` `QPImage6` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage7` `QPImage7` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage8` `QPImage8` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage9` `QPImage9` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPImage10` `QPImage10` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_qp_statement`
CHANGE `QPSTitle1` `QPSTitle1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSTitle2` `QPSTitle2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSTitle3` `QPSTitle3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSTitle4` `QPSTitle4` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage1` `QPSImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage2` `QPSImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage3` `QPSImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage4` `QPSImage4` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage5` `QPSImage5` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage6` `QPSImage6` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage7` `QPSImage7` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage8` `QPSImage8` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage9` `QPSImage9` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage10` `QPSImage10` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSImage11` `QPSImage11` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QPSPosition` `QPSPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_qs`
CHANGE `QTitle1` `QTitle1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QTitle2` `QTitle2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QImage1` `QImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QImage2` `QImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QImage3` `QImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_qs_statement`
CHANGE `QSTitle` `QSTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QSImage` `QSImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `QSPosition` `QSPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_rl`
CHANGE `RLTitle1` `RLTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RLTitle2` `RLTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RLTitle3` `RLTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RLImage1` `RLImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RLImage2` `RLImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RLImage3` `RLImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RLTplType` `RLTplType` TINYINT(4) NOT NULL DEFAULT '0',
CHANGE `FK_RCID` `FK_RCID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_rs`
CHANGE `RSTitle1` `RSTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RSTitle2` `RSTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RSTitle3` `RSTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RSImage1` `RSImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RSImage2` `RSImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RSImage3` `RSImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_sc`
CHANGE `STitle1` `STitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STitle2` `STitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STitle3` `STitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage1` `SImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage2` `SImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage3` `SImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_sc_order`
CHANGE `SOTotalPrice` `SOTotalPrice` DOUBLE NOT NULL DEFAULT '0',
CHANGE `SOTotalTax` `SOTotalTax` DOUBLE NOT NULL DEFAULT '0',
CHANGE `SOTotalPriceWithoutTax` `SOTotalPriceWithoutTax` DOUBLE NOT NULL DEFAULT '0',
CHANGE `SOTransactionID` `SOTransactionID` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SOTransactionNumber` `SOTransactionNumber` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SOTransactionNumberDay` `SOTransactionNumberDay` INT(11) NOT NULL DEFAULT '0',
CHANGE `SOShippingCost` `SOShippingCost` DOUBLE NOT NULL DEFAULT '0',
CHANGE `SOShippingDiscount` `SOShippingDiscount` DOUBLE NOT NULL DEFAULT '0',
CHANGE `SOShippingInsurance` `SOShippingInsurance` DOUBLE NOT NULL DEFAULT '0',
CHANGE `FK_FUID` `FK_FUID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CID` `FK_CID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_sc_order_item`
CHANGE `SOITitle` `SOITitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SOINumber` `SOINumber` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SOID` `FK_SOID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_sc_shipment_mode`
CHANGE `SMName` `SMName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_sc_shipment_mode_country`
CHANGE `FK_SMID` `FK_SMID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_COID` `FK_COID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_sd`
CHANGE `SDTitle1` `SDTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SDTitle2` `SDTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SDTitle3` `SDTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SDImage1` `SDImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SDImage2` `SDImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SDImage3` `SDImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_se`
CHANGE `STitle` `STitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage` `SImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_sp`
CHANGE `PImage1` `PImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage2` `PImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PImage3` `PImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PShortDescription` `PShortDescription` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PNick` `PNick` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PHeight` `PHeight` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PCountry` `PCountry` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PNumber` `PNumber` TINYINT(4) NOT NULL DEFAULT '0',
CHANGE `PPosition` `PPosition` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `PFamilyStatus` `PFamilyStatus` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_st`
CHANGE `STTitle1` `STTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STTitle2` `STTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STTitle3` `STTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STImage1` `STImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STImage2` `STImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STImage3` `STImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_su`
CHANGE `STitle1` `STitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STitle2` `STitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `STitle3` `STitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage1` `SImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage2` `SImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SImage3` `SImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_tg`
CHANGE `TGTitle` `TGTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TGImage` `TGImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_tg_image`
CHANGE `TGITitle` `TGITitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TGIImage` `TGIImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TGIImageTitle` `TGIImageTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TGIPosition` `TGIPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_tg_image_tags`
CHANGE `FK_TGIID` `FK_TGIID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_TAID` `FK_TAID` INT(11) NOT NULL DEFAULT '0',
CHANGE `TGITPosition` `TGITPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_ti`
CHANGE `TTitle1` `TTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TTitle2` `TTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TTitle3` `TTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TImage1` `TImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TImage2` `TImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TImage3` `TImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_to`
CHANGE `TTitle1` `TTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TTitle2` `TTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TTitle3` `TTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_ts`
CHANGE `TTitle1` `TTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TTitle2` `TTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TTitle3` `TTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TImage1` `TImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TImage2` `TImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TImage3` `TImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_ts_block`
CHANGE `TBTitle` `TBTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TBImage` `TBImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TBPosition` `TBPosition` TINYINT(4) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_ts_block_link`
CHANGE `TLTitle` `TLTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TLLink` `TLLink` INT(11) NOT NULL DEFAULT '0',
CHANGE `TLPosition` `TLPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_TBID` `FK_TBID` INT(11) NULL DEFAULT '0';

ALTER TABLE `mc_contentitem_va`
CHANGE `VTitle` `VTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VImage` `VImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_vc`
CHANGE `VTitle1` `VTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VTitle2` `VTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VTitle3` `VTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VImage1` `VImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VImage2` `VImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VImage3` `VImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VImage4` `VImage4` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VVideoType1` `VVideoType1` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VVideo1` `VVideo1` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VVideoType2` `VVideoType2` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VVideo2` `VVideo2` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VVideoType3` `VVideoType3` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VVideo3` `VVideo3` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_vd`
CHANGE `VDocumentId` `VDocumentId` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VDocumentName` `VDocumentName` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VDocumentTitle` `VDocumentTitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VDocumentDescription` `VDocumentDescription` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VTitle1` `VTitle1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VTitle2` `VTitle2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `VTitle3` `VTitle3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_words`
CHANGE `WWord` `WWord` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_contentitem_xs`
CHANGE `XSUrl` `XSUrl` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

UPDATE `mc_contentitem_xu` SET XULink = 0 WHERE XULink IS NULL;
UPDATE `mc_contentitem_xu` SET XUUrl = '' WHERE XUUrl IS NULL;
ALTER TABLE `mc_contentitem_xu`
CHANGE `XUUrl` `XUUrl` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `XULink` `XULink` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_contenttype`
CHANGE `CTClass` `CTClass` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_country`
CHANGE `COName` `COName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `COSymbol` `COSymbol` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `COPosition` `COPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_country_contenttype`
CHANGE `FK_COID` `FK_COID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CTID` `FK_CTID` INT(11) NOT NULL DEFAULT '0',
CHANGE `COCName` `COCName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `COCPosition` `COCPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_cron_log`
CHANGE `CText` `CText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_download_log`
CHANGE `DLFile` `DLFile` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `DLFiletypeType` `DLFiletypeType` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_externallink`
CHANGE `ELTitle` `ELTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ELUrl` `ELUrl` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ELPosition` `ELPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_file`
CHANGE `FTitle` `FTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FPosition` `FPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_frontend_user`
CHANGE `FUSID` `FUSID` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCompany` `FUCompany` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUPosition` `FUPosition` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUTitle` `FUTitle` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUFirstname` `FUFirstname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUMiddlename` `FUMiddlename` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FULastname` `FULastname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUNick` `FUNick` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUPW` `FUPW` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCountry` `FUCountry` INT(11) NOT NULL DEFAULT '0',
CHANGE `FUZIP` `FUZIP` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCity` `FUCity` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUAddress` `FUAddress` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUPhone` `FUPhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUMobilePhone` `FUMobilePhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUFax` `FUFax` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUEmail` `FUEmail` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUUID` `FUUID` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUDepartment` `FUDepartment` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUActivationCode` `FUActivationCode` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_frontend_user_company`
CHANGE `FUCName` `FUCName` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCStreet` `FUCStreet` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCPostalCode` `FUCPostalCode` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCCity` `FUCCity` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_CID_Country` `FK_CID_Country` INT(11) NOT NULL DEFAULT '0',
CHANGE `FUCPhone` `FUCPhone` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCFax` `FUCFax` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCEmail` `FUCEmail` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCWeb` `FUCWeb` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCNotes` `FUCNotes` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCType` `FUCType` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCImage` `FUCImage` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_FUCAID_Area` `FK_FUCAID_Area` INT(11) NOT NULL DEFAULT '0',
CHANGE `FUCDeleted` `FUCDeleted` TINYINT(1) NOT NULL DEFAULT '0',
CHANGE `FUCVatNumber` `FUCVatNumber` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_frontend_user_company_area`
CHANGE `FUCAName` `FUCAName` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_FUCAID_Parent` `FK_FUCAID_Parent` INT(11) NOT NULL DEFAULT '0',
CHANGE `FUCADeleted` `FUCADeleted` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE `mc_frontend_user_group`
CHANGE `FUGName` `FUGName` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUGDescription` `FUGDescription` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_frontend_user_group_pages`
CHANGE `FK_FUGID` `FK_FUGID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_frontend_user_group_sites`
CHANGE `FK_FUGID` `FK_FUGID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_frontend_user_history_login`
CHANGE `FK_FUID` `FK_FUID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_frontend_user_log`
CHANGE `FUSID` `FUSID` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCompany` `FUCompany` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUPosition` `FUPosition` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUTitle` `FUTitle` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUFirstname` `FUFirstname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUMiddlename` `FUMiddlename` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FULastname` `FULastname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUNick` `FUNick` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUPW` `FUPW` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCountry` `FUCountry` INT(11) NOT NULL DEFAULT '0',
CHANGE `FUZIP` `FUZIP` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUCity` `FUCity` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUAddress` `FUAddress` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUPhone` `FUPhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUMobilePhone` `FUMobilePhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUUID` `FUUID` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUFax` `FUFax` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUDepartment` `FUDepartment` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FUActivationCode` `FUActivationCode` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_UID_User` `FK_UID_User` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_UID_FrontendUser` `FK_UID_FrontendUser` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_frontend_user_sessions`
CHANGE `FUSSID` `FUSSID` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_internallink`
CHANGE `ILTitle` `ILTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ILPosition` `ILPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID_Link` `FK_CIID_Link` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_issuu_document`
CHANGE `IDDocumentId` `IDDocumentId` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'Generated by Issuu',
CHANGE `IDUsername` `IDUsername` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `IDName` `IDName` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'Must be unique (http://issuu.com/<IDUsername>/docs/<IDName>)',
CHANGE `IDTitle` `IDTitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `IDState` `IDState` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'A-Active P-Processing F-Failure',
CHANGE `IDErrorCode` `IDErrorCode` SMALLINT(6) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_log_simple`
CHANGE `Level` `Level` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'PSR-3 Log Levels: emergency, alert, critical, error, warning, notice, info, debug',
CHANGE `Identifier` `Identifier` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'Use this column to define some kind of categorization based on i.e. log origin',
CHANGE `User` `User` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'user ID, IP address, ...',
CHANGE `Data` `Data` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'The data to log: serialized, plaintext, json, ...',
CHANGE `DataType` `DataType` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'The log type, to identify the type of this log entry, i.e. CronResult, Mail, ApiErrorResponse, ApplicationConfigurationError, ...';

ALTER TABLE `mc_moduletype_backend`
CHANGE `MShortname` `MShortname` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MClass` `MClass` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_moduletype_frontend`
CHANGE `MShortname` `MShortname` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MClass` `MClass` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_announcement`
CHANGE `ATitle` `ATitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_attribute`
CHANGE `AVTitle` `AVTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `AVPosition` `AVPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_AID` `FK_AID` INT(11) NOT NULL DEFAULT '0',
CHANGE `AVCode` `AVCode` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_attribute_global`
CHANGE `ATitle` `ATitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `AText` `AText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `APosition` `APosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `AIdentifier` `AIdentifier` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CTID` `FK_CTID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_customtext`
CHANGE `CTTitle` `CTTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CTText` `CTText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CTName` `CTName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `CTDescription` `CTDescription` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `CTTemplateVariables` `CTTemplateVariables` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_CTCID_CustomtextCategory` `FK_CTCID_CustomtextCategory` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_customtext_category`
CHANGE `CTCName` `CTCName` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_downloadticker`
CHANGE `DTPosition` `DTPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_employee`
CHANGE `EUrl` `EUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EFirstname` `EFirstname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ELastname` `ELastname` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EStaffNumber` `EStaffNumber` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_FID` `FK_FID` INT(11) NOT NULL DEFAULT '0',
CHANGE `ETitle` `ETitle` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ECompany` `ECompany` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EInitials` `EInitials` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ECountry` `ECountry` INT(11) NOT NULL DEFAULT '0',
CHANGE `EZIP` `EZIP` VARCHAR(6) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ECity` `ECity` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EAddress` `EAddress` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EPhone` `EPhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EPhoneDirectDial` `EPhoneDirectDial` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EFax` `EFax` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EFaxDirectDial` `EFaxDirectDial` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EMobilePhone` `EMobilePhone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EMobilePhoneDirectDial` `EMobilePhoneDirectDial` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EEmail` `EEmail` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ERoom` `ERoom` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EJobTitle` `EJobTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EFunction` `EFunction` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `ESpecialism` `ESpecialism` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EHourlyRate` `EHourlyRate` DOUBLE NOT NULL DEFAULT '0',
CHANGE `FK_CGAID` `FK_CGAID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_employee_attribute`
CHANGE `FK_EID` `FK_EID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_AVID` `FK_AVID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_global_area`
CHANGE `GATitle` `GATitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `GAText` `GAText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `GAImage` `GAImage` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_global_area_box`
CHANGE `GABTitle` `GABTitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `GABText` `GABText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `GABImage` `GABImage` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_GAID` `FK_GAID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_html_creator`
CHANGE `HCTitle1` `HCTitle1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCTitle2` `HCTitle2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCTitle3` `HCTitle3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCImage1` `HCImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCImage2` `HCImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCImage3` `HCImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCUrl` `HCUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_html_creator_box`
CHANGE `HCBTitle` `HCBTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCBImage` `HCBImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCBUrl` `HCBUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCBTemplate` `HCBTemplate` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `HCBPosition` `HCBPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_HCID` `FK_HCID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_html_creator_export_log`
CHANGE `FK_UID` `FK_UID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_HCID` `FK_HCID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_infoticker`
CHANGE `IText` `IText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_module_leaguemanager_game`
CHANGE `FK_YID` `FK_YID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_leaguemanager_game_ticker`
CHANGE `FK_GID` `FK_GID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_leaguemanager_league`
CHANGE `LName` `LName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `LShortname` `LShortname` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_leaguemanager_team`
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_leaguemanager_year`
CHANGE `YName` `YName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_medialibrary`
CHANGE `MImage4` `MImage4` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MImage5` `MImage5` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MImage6` `MImage6` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideoType1` `MVideoType1` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideoThumbnail1` `MVideoThumbnail1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideo1` `MVideo1` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideoType2` `MVideoType2` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideoThumbnail2` `MVideoThumbnail2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideo2` `MVideo2` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideoType3` `MVideoType3` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideoThumbnail3` `MVideoThumbnail3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MVideo3` `MVideo3` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MUrl` `MUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_medialibrary_category`
CHANGE `MCTitle` `MCTitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MCIdentifier` `MCIdentifier` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `MCPosition` `MCPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_medialibrary_category_assignment`
CHANGE `FK_MID` `FK_MID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_MCID` `FK_MCID` INT(11) NOT NULL DEFAULT '0',
CHANGE `MCAPosition` `MCAPosition` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_news`
CHANGE `NWTitle` `NWTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_newsletter_export`
CHANGE `EName` `EName` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_UID` `FK_UID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_newsticker`
CHANGE `TTitle` `TTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TText` `TText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `TImage` `TImage` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_news_category`
CHANGE `NWCTitle` `NWCTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `NWCIdentifier` `NWCIdentifier` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_order_export`
CHANGE `FK_UID` `FK_UID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_popup`
CHANGE `PUUrl` `PUUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_popup_option`
CHANGE `FK_PUID` `FK_PUID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_reseller`
CHANGE `RType` `RType` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_reseller_assignation`
CHANGE `FK_RAID` `FK_RAID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_RID` `FK_RID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_reseller_category`
CHANGE `RCName` `RCName` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_reseller_category_assignation`
CHANGE `FK_RID` `FK_RID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_RCID` `FK_RCID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_reseller_labels`
CHANGE `FK_RAID` `FK_RAID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_rssfeed`
CHANGE `RTitle` `RTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `RText` `RText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_rssfeed_items`
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_shopplusmgmt_log`
CHANGE `LAction` `LAction` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_sidebox`
CHANGE `BUrl` `BUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_CGAID` `FK_CGAID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_siteindex_compendium`
CHANGE `SITitle` `SITitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SIImage1` `SIImage1` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SIImage2` `SIImage2` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SIImage3` `SIImage3` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SIText1` `SIText1` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SIText2` `SIText2` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SIText3` `SIText3` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SIExtlink` `SIExtlink` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SIType` `SIType` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_siteindex_compendium_area`
CHANGE `SATitle` `SATitle` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SAText` `SAText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SAImage` `SAImage` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SAExtlink` `SAExtlink` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0',
CHANGE `SASiteindexType` `SASiteindexType` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SAPosition` `SAPosition` TINYINT(4) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_siteindex_compendium_area_box`
CHANGE `SBTitle1` `SBTitle1` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SBTitle2` `SBTitle2` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SBTitle3` `SBTitle3` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SBText1` `SBText1` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SBText2` `SBText2` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SBText3` `SBText3` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SBImage1` `SBImage1` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SBImage2` `SBImage2` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SBImage3` `SBImage3` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SBExtlink` `SBExtlink` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_SAID` `FK_SAID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_siteindex_compendium_mobile`
CHANGE `SIMText` `SIMText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SIMType` `SIMType` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_module_tag`
CHANGE `TATitle` `TATitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TAPosition` `TAPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_TAGID` `FK_TAGID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_tagcloud`
CHANGE `TCTitle` `TCTitle` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TCInternalUrl` `TCInternalUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TCUrl` `TCUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `FK_TCCID` `FK_TCCID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_module_tagcloud_category`
CHANGE `TCCTitle1` `TCCTitle1` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TCCTitle2` `TCCTitle2` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TCCTitle3` `TCCTitle3` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TCCText1` `TCCText1` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `TCCText2` `TCCText2` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `TCCText3` `TCCText3` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_module_tag_global`
CHANGE `TAGTitle` `TAGTitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `TAGText` `TAGText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `TAGPosition` `TAGPosition` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID` `FK_SID` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_site`
CHANGE `STitle` `STitle` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SText` `SText` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `SUrlInternal` `SUrlInternal` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SUrlExternal` `SUrlExternal` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `SPathExternal` `SPathExternal` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE `mc_site_scope`
CHANGE `SCContentItem` `SCContentItem` TINYINT(4) NOT NULL DEFAULT '0',
CHANGE `SCDownload` `SCDownload` TINYINT(4) NOT NULL DEFAULT '0',
CHANGE `FK_SID_From` `FK_SID_From` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_SID_To` `FK_SID_To` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_structurelink`
CHANGE `FK_CIID` `FK_CIID` INT(11) NOT NULL DEFAULT '0',
CHANGE `FK_CIID_Link` `FK_CIID_Link` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE `mc_user`
CHANGE `USID` `USID` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `UNick` `UNick` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `UEmail` `UEmail` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `UBlockedMessage` `UBlockedMessage` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `UModuleRights` `UModuleRights` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `mc_user_rights`
CHANGE `UPaths` `UPaths` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

/* EDWIN Updater table only available if in use. Execute manually if required */
/* ALTER TABLE `mc_edwin_update_log`
CHANGE `EULDateTime` `EULDateTime` DATETIME NULL DEFAULT NULL,
CHANGE `EULOldVersion` `EULOldVersion` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `EULNewVersion` `EULNewVersion` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''; */
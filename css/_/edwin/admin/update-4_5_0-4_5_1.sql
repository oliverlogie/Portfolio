/******************************************************************************/
/* Lead Mgmt um Frontend User Relation erweitern                              */
/******************************************************************************/

ALTER TABLE mc_campaign_lead_appointment ADD COLUMN FK_FUID_FrontendUser int(11) NOT NULL DEFAULT 0 AFTER FK_UID, ADD INDEX(FK_FUID_FrontendUser);
ALTER TABLE mc_campaign_lead_appointment ADD COLUMN FK_FUID_FinishedBy_FrontendUser int(11) NOT NULL DEFAULT 0 AFTER FK_FUID_FrontendUser, ADD INDEX(FK_FUID_FinishedBy_FrontendUser);

ALTER TABLE mc_campaign_lead_manipulated_log ADD COLUMN FK_FUID_FrontendUser int(11) NOT NULL DEFAULT 0 AFTER FK_UID, ADD INDEX(FK_FUID_FrontendUser);
ALTER TABLE mc_campaign_lead_status ADD COLUMN FK_FUID_FrontendUser int(11) NOT NULL DEFAULT 0 AFTER FK_UID, ADD INDEX(FK_FUID_FrontendUser);

/******************************************************************************/
/* Lead Mgmt: Primary Key für Log / User name in Status History speicherbar   */
/******************************************************************************/

ALTER TABLE mc_campaign_lead_manipulated_log ADD COLUMN CGLMLID INT(11) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`CGLMLID`);
ALTER TABLE mc_campaign_lead_status ADD COLUMN CGLSUserName varchar(255) NOT NULL DEFAULT '' AFTER CGLSText;

/******************************************************************************/
/* Lead Mgmt: Das Änderungsdatum darf nicht mehr NULL sein                    */
/******************************************************************************/

UPDATE mc_campaign_lead_appointment SET CGLAChangeDateTime = CGLACreateDateTime WHERE CGLAChangeDateTime IS NULL;

/******************************************************************************/
/* Bug: CA, Globale Bereiche - MYSQL Problem bei *Strict SQL Mode*            */
/******************************************************************************/

ALTER TABLE mc_contentitem_ca_area CHANGE CAAExtlink CAAExtlink varchar(255) NOT NULL DEFAULT '';
ALTER TABLE mc_contentitem_cb_box_biglink CHANGE BLTitle BLTitle varchar(255) NOT NULL DEFAULT '';
ALTER TABLE mc_contentitem_es CHANGE ETitle3 ETitle3 varchar(255) NOT NULL DEFAULT '';
ALTER TABLE mc_contentitem_ls CHANGE SImage2 SImage2 varchar(255) NOT NULL DEFAULT '';
ALTER TABLE mc_contentitem_pt CHANGE PTitle3 PTitle3 varchar(255) NOT NULL DEFAULT '';
ALTER TABLE mc_contentitem_qs CHANGE QTitle3 QTitle3 varchar(255) NOT NULL DEFAULT '';
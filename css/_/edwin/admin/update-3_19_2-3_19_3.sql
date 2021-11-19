/******************************************************************************/
/* mc_campaign_lead_status um Primary Key erweitern                           */
/******************************************************************************/

ALTER TABLE `mc_campaign_lead_status` ADD `CGLSID` INT(11) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`CGLSID`);
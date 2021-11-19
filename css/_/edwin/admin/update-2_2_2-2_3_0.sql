/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 * Beim Anlegen neuer Root-Seiten muss beachtet werden, dass für jedes dieser
 * mc_contentitem Einträge auch ein Eintrag in der mc_contentabstract Tabelle
 * erstellt wird (mc_contentabstract enthält allgemeine Daten zum Item z.B.
 * Box-Bilder, Anreißertexte, Navigationsbild, Text & Bild für die Blogebene).
 *
 * Der Navigationsbaum 'user' existiert nur für die erste Seite. Es wird für
 * diesen Navigationsbaum für jeden User eine Root-Seite erstellt. (FK_FUID ->
 * ID des Benutzers)
 *
 * Um die alte Version der Footer-Navigation weiterhin verwenden zu können, muss
 * $_CONFIG['m_nv_footer_use_deprecated'] = 1 gesetzt werden.
 * [/INFO]
 */

/******************************************************************************/
/*                       Blogebene & Blognavigation                           */
/******************************************************************************/

INSERT INTO `mc_contenttype` (`CTID`, `CTClass`, `CTActive`, `CTPosition`) VALUES ('80', 'ContentItemBE', '0', '7');

CREATE TABLE `mc_contentitem_be` (
  `BID` int(11) NOT NULL AUTO_INCREMENT,
  `BTitle` varchar(150) NOT NULL,
  `BText` text,
  `BImage` varchar(150) NOT NULL,
  `FK_CIID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`BID`),
  KEY `FK_CID` (`FK_CIID`)
) ENGINE=MyISAM;

CREATE TABLE `mc_contentitem_be_item_data` (
  `FK_CIID` int(11) NOT NULL DEFAULT '0',
  `BIText` text,
  `BImage` varchar(150) NOT NULL,
  PRIMARY KEY (`FK_CIID`)
) ENGINE=MyISAM;

CREATE TABLE `mc_contentabstract` (
  `FK_CIID` int(11) NOT NULL DEFAULT '0',
  `CShortText` text DEFAULT NULL,
  `CShortTextManual` text DEFAULT NULL,
  `CImage` varchar(150) DEFAULT NULL,
  `CLockImage` tinyint(4) NOT NULL DEFAULT '0',
  `CImage2` varchar(150) DEFAULT NULL,
  `CLockImage2` tinyint(1) DEFAULT '0',
  `CShortTextBlog` text,
  `CImageBlog` varchar(150) NOT NULL,
  PRIMARY KEY (`FK_CIID`)
) ENGINE=MyISAM;

INSERT INTO `mc_contentabstract` 
(FK_CIID, CShortText, CShortTextManual, CImage, CLockImage, CImage2, CLockImage2)
SELECT ci.CIID, ci.CShortText, ci.CShortTextManual, ci.CImage, ci.CLockImage, ci.CImage2, ci.CLockImage2
FROM mc_contentitem ci;

ALTER TABLE `mc_contentitem` DROP `CShortText` ,
DROP `CShortTextManual` ,
DROP `CImage` ,
DROP `CLockImage` ,
DROP `CImage2` ,
DROP `CLockImage2` ;

/******************************************************************************/
/*                       Zusätzliches ModuleNestedNavLevel1                   */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`) VALUES(39, 'nestednavlevel1', 'ModuleNestedNavLevel1', 0, 0, 0, 0);


/******************************************************************************/
/*                   Eigener Navigationsbaum auf Userebene                    */
/******************************************************************************/

ALTER TABLE mc_contentitem ADD FK_FUID INT NULL ,
ADD INDEX ( FK_FUID );

ALTER TABLE mc_contentitem 
MODIFY CTree ENUM('main', 'footer', 'hidden', 'login', 'pages', 'user') NOT NULL DEFAULT 'main';

ALTER TABLE mc_user_rights 
MODIFY UTree ENUM('main', 'footer', 'login', 'pages', 'user') DEFAULT NULL;

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`) VALUES ('26', 'frontendusertree', 'ModuleFrontendUserTree', '0', '0', '0');

ALTER TABLE `mc_moduletype_frontend` ADD `MActiveUser` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `MActiveLandingPages`;

/******************************************************************************/
/*                   Navigation gesamt ausgeben / Sitemap                     */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`, `MActiveUser`) VALUES ('40', 'sitemapnavmain', 'ModuleSitemapNavMain', '0', '0', '0', '0', '0');

INSERT INTO `mc_contenttype` (`CTID`, `CTClass`, `CTActive`, `CTPosition`) VALUES ('33', 'ContentItemST', '0', '52');

CREATE TABLE mc_contentitem_st (
  STID int(11) NOT NULL auto_increment,
  STTitle1 varchar(150) NOT NULL,
  STTitle2 varchar(150) NOT NULL,
  STTitle3 varchar(150) NOT NULL,
  STImage1 varchar(150) NOT NULL,
  STImage2 varchar(150) NOT NULL,
  STImage3 varchar(150) NOT NULL,
  STImageTitles text,
  STText1 text,
  STText2 text,
  STText3 text,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (STID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

/******************************************************************************/
/*                          Fotogalerie mit Tagging                           */
/******************************************************************************/

CREATE TABLE mc_contentitem_tg (
  TGID int(11) NOT NULL auto_increment,
  TGTitle varchar(150) NOT NULL,
  TGText1 text,
  TGText2 text,
  TGText3 text,
  TGImage varchar(150) NOT NULL,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY (TGID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_tg_image (
  TGIID int(11) NOT NULL AUTO_INCREMENT,
  TGITitle varchar(150) DEFAULT NULL,
  TGIImage varchar(150) NOT NULL,
  TGIPosition int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  PRIMARY KEY (TGIID),
  UNIQUE KEY FK_CIID_BIPosition_UN (FK_CIID, TGIPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_tg_image_tags (
  FK_TGIID int(11) NOT NULL,
  FK_TAID int(11) NOT NULL,
  TGITPosition int(11) NOT NULL,
  PRIMARY KEY (FK_TGIID, FK_TAID),
  KEY FK_TGIID (FK_TGIID),
  KEY FK_TAID (FK_TAID)
) ENGINE=MyISAM;

INSERT INTO `mc_contenttype` (`CTID`, `CTClass`, `CTActive`, `CTPosition`) VALUES ('34', 'ContentItemTG', '0', '21');

CREATE TABLE mc_module_tag (
  TAID int(11) NOT NULL auto_increment,
  TATitle varchar(150) NOT NULL,
  TAPosition int(11) NOT NULL,
  FK_TAGID int(11) NOT NULL,
  PRIMARY KEY  (TAID),
  KEY FK_TAGID (FK_TAGID)
) ENGINE=MyISAM;

CREATE TABLE mc_module_tag_global (
  TAGID int(11) NOT NULL auto_increment,
  TAGTitle varchar(150) NOT NULL,
  TAGText text NOT NULL,
  TAGPosition int(11) NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (TAGID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`) VALUES ('27', 'tag', 'ModuleTag', '0', '10', '0');

/******************************************************************************/
/*                   3*Titel/Text/Bild bei ContentItemQS                      */
/******************************************************************************/

ALTER TABLE mc_contentitem_qs 
ADD QTitle2 varchar(255) NOT NULL AFTER QTitle1,
ADD QTitle3 varchar(255) NOT NULL AFTER QTitle2,
ADD QImage2 varchar(150) NOT NULL AFTER QImage1,
ADD QImage3 varchar(150) NOT NULL AFTER QImage2,
CHANGE QTitle QTitle1 varchar(255) NOT NULL,
CHANGE QImage QImage1 varchar(150) NOT NULL;

/******************************************************************************/
/*                Erweiterung Suche um Dateien und Bilder                     */
/******************************************************************************/

ALTER TABLE mc_contentitem_words
ADD WImageCount int(11) NOT NULL DEFAULT '0' AFTER WDownloadCount;

/******************************************************************************/
/*                DoubleNestedLoginNav erstellen                              */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend`
(`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`, `MActiveUser`)
VALUES (41, 'doublenestedloginnavlevel1', 'ModuleDoubleNestedLoginNavLevel1', 0, 0, 0, 0, 0);

/******************************************************************************/
/*                          Multiple Sessions für FE User                     */
/******************************************************************************/

ALTER TABLE mc_frontend_user
ADD FUAllowMultipleSessions tinyint(1) NOT NULL default '0';

CREATE TABLE mc_frontend_user_sessions (
  FUSSID varchar(50) NOT NULL,
  FUSLastAction datetime NOT NULL default '0000-00-00 00:00:00',
  FK_UID int(11) NOT NULL default '0',
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM;

/******************************************************************************/
/*       ModuleUserManagement - Aktivieren aller Module nicht möglich         */
/******************************************************************************/

ALTER TABLE mc_user_rights CHANGE UModules UModules TEXT NULL DEFAULT NULL;

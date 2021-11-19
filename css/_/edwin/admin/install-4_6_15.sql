-- Adminer 4.7.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS mc_blocked_users;
CREATE TABLE mc_blocked_users (
  BTime int(11) NOT NULL DEFAULT '0',
  BSection varchar(20) NOT NULL DEFAULT '',
  BIP varchar(15) NOT NULL DEFAULT '',
  KEY BIP (BIP)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_cache_simple;
CREATE TABLE mc_cache_simple (
  ID bigint(20) NOT NULL AUTO_INCREMENT,
  DataId varchar(255) NOT NULL DEFAULT '' COMMENT 'The id of the cached dataset.',
  DataType varchar(255) NOT NULL DEFAULT '' COMMENT 'The type of cached dataset',
  Data longtext COMMENT 'The cached data.',
  ExpireDateTime datetime DEFAULT NULL COMMENT 'The datetime the dataset expires.',
  CreateDateTime datetime DEFAULT NULL,
  ChangeDateTime datetime DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY DataId (DataId(250)),
  KEY DataType (DataType(250))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign;
CREATE TABLE mc_campaign (
  CGID int(11) NOT NULL AUTO_INCREMENT,
  CGName varchar(150) NOT NULL DEFAULT '',
  CGPosition int(11) NOT NULL DEFAULT '0',
  CGStatus tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 Active; 2 Archived; 3 Deleted;',
  FK_CGTID int(11) DEFAULT NULL,
  FK_SID int(11) DEFAULT NULL,
  FK_CGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGID),
  KEY FK_CGTID (FK_CGTID),
  KEY FK_SID (FK_SID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM AUTO_INCREMENT=1002 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_campaign (CGID, CGName, CGPosition, CGStatus, FK_CGTID, FK_SID, FK_CGID) VALUES
(1001, 'Kontaktformular', 1, 1, 1, 1, 0);

DROP TABLE IF EXISTS mc_campaign_attached;
CREATE TABLE mc_campaign_attached (
  CGAID int(11) NOT NULL AUTO_INCREMENT,
  CGAAdditionalDataOrigin varchar(100) NOT NULL DEFAULT '',
  CGARecipients varchar(255) NOT NULL DEFAULT '',
  FK_CGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGAID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_competing_company;
CREATE TABLE mc_campaign_competing_company (
  CGCCID int(11) NOT NULL AUTO_INCREMENT,
  CGCCValue varchar(150) NOT NULL DEFAULT '',
  CGCCPosition tinyint(2) NOT NULL DEFAULT '0',
  FK_CGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGCCID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_contentitem;
CREATE TABLE mc_campaign_contentitem (
  CGCID int(11) NOT NULL AUTO_INCREMENT,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_CGID int(11) NOT NULL DEFAULT '0',
  CGCCampaignRecipient varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (CGCID),
  KEY FK_CIID (FK_CIID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_data;
CREATE TABLE mc_campaign_data (
  CGDID int(11) NOT NULL AUTO_INCREMENT,
  CGDType tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 Text; 2 Textarea; 3 Combobox; 4 Checkbox; 5 Checkboxgroup; 6 Radiobutton; 7 Upload;',
  CGDName text,
  CGDValue text,
  CGDRequired tinyint(1) NOT NULL DEFAULT '0',
  CGDDependency int(11) DEFAULT NULL,
  CGDPosition int(11) NOT NULL DEFAULT '0',
  CGDValidate tinyint(2) NOT NULL DEFAULT '0' COMMENT '1 Number; 2 Mail; 3 Date; 4 Birthday; ',
  CGDPredefined tinyint(2) NOT NULL DEFAULT '0' COMMENT '1 Country; 2 Date;',
  CGDPrechecked varchar(50) NOT NULL DEFAULT '',
  CGDMinLength smallint(4) NOT NULL DEFAULT '0',
  CGDMaxLength smallint(4) NOT NULL DEFAULT '0',
  CGDMinValue smallint(4) NOT NULL DEFAULT '0',
  CGDMaxValue mediumint(4) NOT NULL DEFAULT '0',
  CGDFiletypes varchar(128) NOT NULL DEFAULT '',
  CGDFilesize int(11) NOT NULL DEFAULT '0',
  CGDDisabled tinyint(1) NOT NULL DEFAULT '0',
  CGDClientData tinyint(1) NOT NULL DEFAULT '0',
  FK_CGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGDID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM AUTO_INCREMENT=1001141 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_campaign_data (CGDID, CGDType, CGDName, CGDValue, CGDRequired, CGDDependency, CGDPosition, CGDValidate, CGDPredefined, CGDPrechecked, CGDMinLength, CGDMaxLength, CGDMinValue, CGDMaxValue, CGDFiletypes, CGDFilesize, CGDDisabled, CGDClientData, FK_CGID) VALUES
(1001016, 4, 'Ich bestätige, dass ich die Datenschutzbedingungen gelesen und verstanden habe und stimme diesen zu. Meine Einwilligung kann ich jederzeit per Email an office@kunde.at widerrufen.', '', 1, NULL, 16, 0, 0, '', 0, 0, 0, 0, '', 0, 0, 1, 1001),
(1001015, 1, 'Titel nachstehend', '', 0, NULL, 15, 0, 0, '', 0, 50, 0, 0, '', 0, 0, 1, 1001),
(1001014, 4, 'Ich bin einverstanden, dass diese auf meine Person und Firma bezogenen Daten von {Kunde} gespeichert und verarbeitet werden. {Kunde} wird diese Daten nicht an Dritte weitergeben und ausschließlich dazu nutzen, um mich über aktuelle Informationen über {Kunde} auf dem Laufenden zu halten. Ich kann mein Einverständnis gegenüber {Kunde} jederzeit postalisch oder per E-Mail widerrufen.', '', 0, NULL, 14, 0, 0, '', 0, 0, 0, 0, '', 0, 0, 1, 1001),
(1001013, 1, 'E-Mail Adresse', '', 1, NULL, 13, 2, 0, '', 0, 0, 0, 0, '', 0, 0, 1, 1001),
(1001012, 1, 'Telefonnummer', '', 1, NULL, 12, 0, 0, '', 0, 0, 0, 0, '', 0, 0, 1, 1001),
(1001011, 1, 'Straße', '', 1, NULL, 11, 0, 0, '', 0, 150, 0, 0, '', 0, 0, 1, 1001),
(1001010, 1, 'Ort', '', 1, NULL, 10, 0, 0, '', 0, 150, 0, 0, '', 0, 0, 1, 1001),
(1001009, 1, 'PLZ', '', 1, NULL, 9, 1, 0, '', 0, 0, 0, 0, '', 0, 0, 1, 1001),
(1001006, 1, 'Nachname', '', 1, NULL, 6, 0, 0, '', 2, 150, 0, 0, '', 0, 0, 1, 1001),
(1001008, 3, 'Land', '', 1, NULL, 8, 0, 1, '', 0, 0, 0, 0, '', 0, 0, 1, 1001),
(1001005, 1, 'Vorname', '', 1, NULL, 5, 0, 0, '', 0, 150, 0, 0, '', 0, 0, 1, 1001),
(1001004, 1, 'Titel vorangestellt', '', 0, NULL, 4, 0, 0, '', 0, 50, 0, 0, '', 0, 0, 1, 1001),
(1001003, 3, 'Anrede', 'Frau$Herr$Firma', 1, NULL, 3, 0, 0, '', 0, 0, 0, 0, '', 0, 0, 1, 1001),
(1001110, 5, 'Wofür interessieren Sie sich?', 'Produkt 1$Produkt 2$Produkt 3$Produkt 4$Produkt 5$Produkt 6', 0, NULL, 110, 0, 0, '1$2', 0, 0, 0, 0, '', 0, 0, 0, 1001),
(1001120, 2, 'Nachricht', '', 0, NULL, 120, 0, 0, '', 0, 0, 0, 0, '', 0, 0, 0, 1001),
(1001130, 6, 'Wofür interessieren Sie sich?', 'Produkt 1$Produkt 2', 0, NULL, 130, 0, 0, '', 0, 0, 0, 0, '', 0, 0, 0, 1001),
(1001140, 7, 'Upload', '', 0, NULL, 140, 0, 0, '', 0, 0, 0, 0, 'pdf$doc$jpg$png', 0, 0, 0, 1001),
(1001017, 1, 'Mobiltelefonnummer', '', 0, NULL, 17, 0, 0, '', 0, 0, 0, 0, '', 0, 0, 1, 1001);

DROP TABLE IF EXISTS mc_campaign_lead;
CREATE TABLE mc_campaign_lead (
  CGLID int(11) NOT NULL AUTO_INCREMENT,
  CGLDocumentsEMail tinyint(1) NOT NULL DEFAULT '0',
  CGLDocumentsPost tinyint(1) NOT NULL DEFAULT '0',
  CGLAppointment tinyint(1) NOT NULL DEFAULT '0',
  CGLDataOrigin varchar(255) NOT NULL DEFAULT '',
  CGLDeleted tinyint(1) NOT NULL DEFAULT '0',
  FK_CID int(11) NOT NULL DEFAULT '0',
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FK_CGSID int(11) NOT NULL DEFAULT '0',
  FK_CGID int(11) DEFAULT NULL,
  FK_CGCCID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGLID),
  KEY FK_CID (FK_CID),
  KEY FK_FUID (FK_FUID),
  KEY FK_CGSID (FK_CGSID),
  KEY FK_CGID (FK_CGID),
  KEY FK_CGCCID (FK_CGCCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_lead_appointment;
CREATE TABLE mc_campaign_lead_appointment (
  CGLAID int(11) NOT NULL AUTO_INCREMENT,
  CGLACreateDateTime datetime DEFAULT NULL,
  CGLAChangeDateTime datetime DEFAULT NULL,
  CGLADateTime datetime DEFAULT NULL,
  CGLATitle varchar(255) NOT NULL DEFAULT '',
  CGLAText text,
  CGLAStatus enum('Open','Finished') NOT NULL DEFAULT 'Open',
  FK_UID int(11) NOT NULL DEFAULT '0',
  FK_FUID_FrontendUser int(11) NOT NULL DEFAULT '0',
  FK_FUID_FinishedBy_FrontendUser int(11) NOT NULL DEFAULT '0',
  FK_CGLID int(11) NOT NULL DEFAULT '0',
  FK_CGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGLAID),
  KEY FK_UID (FK_UID),
  KEY FK_CGLID (FK_CGLID),
  KEY FK_CGID (FK_CGID),
  KEY FK_FUID_FrontendUser (FK_FUID_FrontendUser),
  KEY FK_FUID_FinishedBy_FrontendUser (FK_FUID_FinishedBy_FrontendUser)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_lead_data;
CREATE TABLE mc_campaign_lead_data (
  FK_CGLID int(11) NOT NULL DEFAULT '0',
  FK_CGDID int(11) NOT NULL DEFAULT '0',
  CGLDValue text,
  KEY FK_CGLID (FK_CGLID),
  KEY FK_CGDID (FK_CGDID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_lead_manipulated_log;
CREATE TABLE mc_campaign_lead_manipulated_log (
  CGLMLID int(11) NOT NULL AUTO_INCREMENT,
  CGLMLDateTime datetime DEFAULT NULL,
  FK_CGLID int(11) DEFAULT NULL,
  FK_UID int(11) DEFAULT NULL,
  FK_FUID_FrontendUser int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGLMLID),
  KEY FK_CGLID (FK_CGLID),
  KEY FK_UID (FK_UID),
  KEY FK_FUID_FrontendUser (FK_FUID_FrontendUser)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_lead_status;
CREATE TABLE mc_campaign_lead_status (
  CGLSID int(11) NOT NULL AUTO_INCREMENT,
  FK_CGLID int(11) NOT NULL DEFAULT '0',
  FK_CGID int(11) NOT NULL DEFAULT '0',
  CGLSDateTime datetime DEFAULT NULL,
  CGLSText text,
  CGLSUserName varchar(255) NOT NULL DEFAULT '',
  FK_CGSID int(11) NOT NULL DEFAULT '1',
  FK_UID int(11) NOT NULL DEFAULT '0',
  FK_FUID_FrontendUser int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGLSID),
  KEY FK_CGLID (FK_CGLID),
  KEY FK_CGID (FK_CGID),
  KEY FK_CGSID (FK_CGSID),
  KEY FK_UID (FK_UID),
  KEY FK_FUID_FrontendUser (FK_FUID_FrontendUser)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_campaign_status;
CREATE TABLE mc_campaign_status (
  CGSID int(11) NOT NULL AUTO_INCREMENT,
  CGSName varchar(150) NOT NULL DEFAULT '',
  CGSPosition int(11) NOT NULL DEFAULT '0',
  FK_CGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGSID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_campaign_status (CGSID, CGSName, CGSPosition, FK_CGID) VALUES
(1, 'Bearbeitung offen', 1, 0),
(2, 'Kontakt aufgenommen', 2, 0),
(3, 'Kontakt abgeschlossen', 3, 0);

DROP TABLE IF EXISTS mc_campaign_type;
CREATE TABLE mc_campaign_type (
  CGTID int(11) NOT NULL AUTO_INCREMENT,
  CGTName varchar(150) NOT NULL DEFAULT '',
  CGTPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CGTID)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_campaign_type (CGTID, CGTName, CGTPosition, FK_SID) VALUES
(1, 'Diverse', 1, 1);

DROP TABLE IF EXISTS mc_centralfile;
CREATE TABLE mc_centralfile (
  CFID int(11) NOT NULL AUTO_INCREMENT,
  CFTitle varchar(150) NOT NULL DEFAULT '',
  CFFile varchar(150) NOT NULL DEFAULT '',
  CFCreated datetime DEFAULT NULL,
  CFModified datetime DEFAULT NULL,
  CFShowAlways tinyint(1) NOT NULL DEFAULT '0',
  CFProtected tinyint(1) NOT NULL DEFAULT '0',
  CFSize int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  FK_IDID_IssuuDocument int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CFID),
  KEY FK_SID (FK_SID),
  KEY FK_IDID_IssuuDocument (FK_IDID_IssuuDocument)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_client;
CREATE TABLE mc_client (
  CID int(11) NOT NULL AUTO_INCREMENT,
  CCompany varchar(150) NOT NULL DEFAULT '',
  CPosition varchar(150) NOT NULL DEFAULT '',
  FK_FID int(11) NOT NULL DEFAULT '0',
  CTitlePre varchar(50) NOT NULL DEFAULT '',
  CFirstname varchar(150) NOT NULL DEFAULT '',
  CLastName varchar(150) NOT NULL DEFAULT '',
  CBirthday date DEFAULT NULL,
  CCountry int(11) NOT NULL DEFAULT '0',
  CZIP varchar(6) NOT NULL DEFAULT '',
  CCity varchar(150) NOT NULL DEFAULT '',
  CAddress varchar(150) NOT NULL DEFAULT '',
  CPhone varchar(50) NOT NULL DEFAULT '',
  CEmail varchar(100) NOT NULL DEFAULT '',
  CNewsletter tinyint(1) NOT NULL DEFAULT '0',
  CTitlePost varchar(50) NOT NULL DEFAULT '',
  CDataPrivacyConsent tinyint(1) NOT NULL DEFAULT '0',
  CMobilePhone varchar(50) NOT NULL DEFAULT '',
  CNewsletterOptInToken varchar(255) NOT NULL DEFAULT '',
  CNewsletterOptInSuccessDateTime datetime DEFAULT NULL,
  CNewsletterConfirmedRecipient tinyint(1) NOT NULL DEFAULT '0',
  CCreateDateTime datetime DEFAULT NULL,
  CChangeDateTime datetime DEFAULT NULL,
  FK_UID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CID),
  KEY FK_SID (FK_SID),
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_client_actions;
CREATE TABLE mc_client_actions (
  CAAID int(11) NOT NULL AUTO_INCREMENT,
  FK_CID int(11) NOT NULL DEFAULT '0',
  CADateTime datetime DEFAULT NULL,
  CAAction varchar(50) NOT NULL DEFAULT '',
  CAActionID int(11) NOT NULL DEFAULT '0',
  CAActionText text,
  PRIMARY KEY (CAAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_client_uploads;
CREATE TABLE mc_client_uploads (
  CUID int(11) NOT NULL AUTO_INCREMENT,
  FK_CID int(11) NOT NULL DEFAULT '0',
  CUCreateDateTime datetime DEFAULT NULL,
  CUFile varchar(150) NOT NULL DEFAULT '',
  CUViewed tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (CUID),
  KEY FK_CID (FK_CID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_comments;
CREATE TABLE mc_comments (
  CID int(11) NOT NULL AUTO_INCREMENT,
  CTitle varchar(150) DEFAULT NULL,
  CShortText text,
  CText text,
  CAuthor varchar(150) DEFAULT NULL,
  CEmail varchar(150) DEFAULT NULL,
  FK_CIID int(11) DEFAULT NULL,
  FK_UID int(11) DEFAULT NULL,
  FK_CID int(11) NOT NULL DEFAULT '0',
  FK_FUID int(11) DEFAULT NULL,
  CCreateDateTime datetime DEFAULT NULL,
  CChangeDateTime datetime DEFAULT NULL,
  CChangedBy int(11) DEFAULT NULL,
  CPublished tinyint(1) NOT NULL DEFAULT '0',
  CCanceled tinyint(1) NOT NULL DEFAULT '0',
  CDeleted tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (CID),
  KEY FK_CIID (FK_CIID),
  KEY FK_UID (FK_UID),
  KEY FK_CID (FK_CID),
  KEY FK_FUID (FK_FUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentabstract;
CREATE TABLE mc_contentabstract (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  CShortText text,
  CShortTextManual text,
  CImage varchar(150) DEFAULT NULL,
  CLockImage tinyint(4) NOT NULL DEFAULT '0',
  CImage2 varchar(150) DEFAULT NULL,
  CLockImage2 tinyint(1) DEFAULT '0',
  CShortTextBlog text,
  CImageBlog varchar(150) NOT NULL DEFAULT '',
  CAdditionalImage varchar(255) NOT NULL DEFAULT '',
  CAdditionalText text,
  PRIMARY KEY (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_contentabstract (FK_CIID, CShortText, CShortTextManual, CImage, CLockImage, CImage2, CLockImage2, CShortTextBlog, CImageBlog, CAdditionalImage, CAdditionalText) VALUES
(1, NULL, NULL, NULL, 0, NULL, 0, NULL, '', '', ''),
(2, NULL, NULL, NULL, 0, NULL, 0, NULL, '', '', ''),
(3, NULL, NULL, NULL, 0, NULL, 0, NULL, '', '', ''),
(4, NULL, NULL, NULL, 0, NULL, 0, NULL, '', '', ''),
(5, NULL, NULL, NULL, 0, NULL, 0, NULL, '', '', '');

DROP TABLE IF EXISTS mc_contentitem;
CREATE TABLE mc_contentitem (
  CIID int(11) NOT NULL AUTO_INCREMENT,
  CIIdentifier varchar(150) DEFAULT NULL,
  CTitle varchar(150) DEFAULT NULL,
  CPosition int(11) NOT NULL DEFAULT '0',
  CShowFromDate datetime DEFAULT NULL,
  CShowUntilDate datetime DEFAULT NULL,
  CType tinyint(4) NOT NULL DEFAULT '0',
  FK_CTID int(11) DEFAULT NULL,
  FK_SID int(11) DEFAULT NULL,
  FK_CIID int(11) DEFAULT NULL,
  CTree enum('main','footer','hidden','login','pages','user') NOT NULL DEFAULT 'main',
  CCreateDateTime datetime DEFAULT NULL,
  CChangeDateTime datetime DEFAULT NULL,
  CPositionLocked tinyint(1) NOT NULL DEFAULT '0',
  CContentLocked tinyint(1) NOT NULL DEFAULT '0',
  CDisabled tinyint(4) NOT NULL DEFAULT '0',
  CDisabledLocked tinyint(1) NOT NULL DEFAULT '0',
  CHasContent tinyint(1) NOT NULL DEFAULT '0',
  CShare tinyint(1) NOT NULL DEFAULT '0',
  CBlog tinyint(1) NOT NULL DEFAULT '0',
  FK_FUID int(11) DEFAULT NULL,
  CTaggable tinyint(1) NOT NULL DEFAULT '0',
  CAdditionalImageLevel tinyint(4) NOT NULL DEFAULT '0',
  CAdditionalTextLevel tinyint(4) NOT NULL DEFAULT '0',
  CMobile tinyint(4) NOT NULL DEFAULT '1',
  CSEOTitle varchar(255) NOT NULL DEFAULT '',
  CSEODescription text,
  CSEOKeywords varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (CIID),
  UNIQUE KEY FK_SID_CIIdentifier_UN (FK_SID,CIIdentifier),
  UNIQUE KEY FK_CIID_CPosition_UN (FK_CIID,CPosition),
  KEY FK_SID (FK_SID),
  KEY FK_CIID (FK_CIID),
  KEY FK_FUID (FK_FUID)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_contentitem (CIID, CIIdentifier, CTitle, CPosition, CShowFromDate, CShowUntilDate, CType, FK_CTID, FK_SID, FK_CIID, CTree, CCreateDateTime, CChangeDateTime, CPositionLocked, CContentLocked, CDisabled, CDisabledLocked, CHasContent, CShare, CBlog, FK_FUID, CTaggable, CAdditionalImageLevel, CAdditionalTextLevel, CMobile, CSEOTitle, CSEODescription, CSEOKeywords) VALUES
(1, '', 'Startseite', 0, NULL, NULL, 0, NULL, 1, NULL, 'main', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, 0, 1, '', '', ''),
(2, NULL, '(Footer)', 0, NULL, NULL, 0, NULL, 1, NULL, 'footer', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, 0, 1, '', '', ''),
(3, NULL, '(Hidden)', 0, NULL, NULL, 0, NULL, 1, NULL, 'hidden', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, 0, 1, '', '', ''),
(4, NULL, 'Login', 0, NULL, NULL, 0, NULL, 1, NULL, 'login', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, 0, 1, '', '', ''),
(5, NULL, 'Landing Pages', 0, NULL, NULL, 0, NULL, 1, NULL, 'pages', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, NULL, 0, 0, 0, 1, '', '', '');

DROP TABLE IF EXISTS mc_contentitem_be;
CREATE TABLE mc_contentitem_be (
  BID int(11) NOT NULL AUTO_INCREMENT,
  BTitle varchar(150) NOT NULL DEFAULT '',
  BText text,
  BImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (BID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_bg;
CREATE TABLE mc_contentitem_bg (
  GID int(11) NOT NULL AUTO_INCREMENT,
  GTitle1 varchar(150) NOT NULL DEFAULT '',
  GTitle2 varchar(150) NOT NULL DEFAULT '',
  GTitle3 varchar(150) NOT NULL DEFAULT '',
  GText1 text,
  GText2 text,
  GText3 text,
  GImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (GID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_bg_image;
CREATE TABLE mc_contentitem_bg_image (
  BIID int(11) NOT NULL AUTO_INCREMENT,
  BITitle varchar(150) DEFAULT '',
  BIText text,
  BIImage varchar(150) NOT NULL DEFAULT '',
  BIImageTitle varchar(150) DEFAULT '',
  BIPosition int(11) NOT NULL DEFAULT '0',
  BICreateDateTime datetime DEFAULT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (BIID),
  UNIQUE KEY FK_CIID_BIPosition_UN (FK_CIID,BIPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_bi;
CREATE TABLE mc_contentitem_bi (
  BID int(11) NOT NULL AUTO_INCREMENT,
  BTitle varchar(150) NOT NULL DEFAULT '',
  BImage1 varchar(150) NOT NULL DEFAULT '',
  BImage2 varchar(150) NOT NULL DEFAULT '',
  BText text,
  BNumber int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (BID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ca;
CREATE TABLE mc_contentitem_ca (
  CAID int(11) NOT NULL AUTO_INCREMENT,
  CATitle varchar(150) NOT NULL DEFAULT '',
  CAImage1 varchar(150) NOT NULL DEFAULT '',
  CAImage2 varchar(150) NOT NULL DEFAULT '',
  CAImage3 varchar(150) NOT NULL DEFAULT '',
  CAText1 text,
  CAText2 text,
  CAText3 text,
  CAImageTitles text,
  CALink int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CAID),
  KEY FK_CIID (FK_CIID),
  KEY CALink (CALink)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ca_area;
CREATE TABLE mc_contentitem_ca_area (
  CAAID int(11) NOT NULL AUTO_INCREMENT,
  CAATitle varchar(100) NOT NULL DEFAULT '',
  CAAText text,
  CAAImage varchar(100) NOT NULL DEFAULT '',
  CAABoxType enum('large','medium','small') NOT NULL DEFAULT 'large',
  CAAPosition tinyint(4) NOT NULL DEFAULT '0',
  CAALink int(11) NOT NULL DEFAULT '0',
  CAAExtlink varchar(255) NOT NULL DEFAULT '',
  CAADisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CAAID),
  UNIQUE KEY CAAID_CAAPosition_UN (CAAID,CAAPosition),
  KEY FK_CIID (FK_CIID),
  KEY CAALink (CAALink)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ca_area_box;
CREATE TABLE mc_contentitem_ca_area_box (
  CAABID int(11) NOT NULL AUTO_INCREMENT,
  CAABTitle varchar(100) NOT NULL DEFAULT '',
  CAABText text,
  CAABImage varchar(100) NOT NULL DEFAULT '',
  CAABNoImage tinyint(4) NOT NULL DEFAULT '0',
  CAABPosition tinyint(4) NOT NULL DEFAULT '0',
  CAABLink int(11) NOT NULL DEFAULT '0',
  CAABExtlink varchar(255) NOT NULL DEFAULT '',
  CAABDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CAAID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CAABID),
  UNIQUE KEY CAABID_CAABPosition_UN (CAABID,CAABPosition),
  KEY FK_CAAID (FK_CAAID),
  KEY CAABLink (CAABLink)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cb;
CREATE TABLE mc_contentitem_cb (
  CBID int(11) NOT NULL AUTO_INCREMENT,
  CBTitle varchar(255) NOT NULL DEFAULT '',
  CBText1 text,
  CBText2 text,
  CBText3 text,
  CBImage varchar(150) NOT NULL DEFAULT '',
  CBImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CBID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cb_box;
CREATE TABLE mc_contentitem_cb_box (
  CBBID int(11) NOT NULL AUTO_INCREMENT,
  CBBTitle varchar(255) NOT NULL DEFAULT '',
  CBBText text,
  CBBImage varchar(150) NOT NULL DEFAULT '',
  CBBImageTitles text,
  CBBLink int(11) NOT NULL DEFAULT '0',
  CBBPosition int(11) NOT NULL DEFAULT '0',
  CBBDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CBBID),
  UNIQUE KEY FK_CIID_CBBPosition_UN (FK_CIID,CBBPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cb_box_biglink;
CREATE TABLE mc_contentitem_cb_box_biglink (
  BLID int(11) NOT NULL AUTO_INCREMENT,
  BLTitle varchar(255) NOT NULL DEFAULT '',
  BLText text,
  BLImage varchar(150) NOT NULL DEFAULT '',
  BLImageTitles text,
  BLLink int(11) NOT NULL DEFAULT '0',
  BLPosition int(11) NOT NULL DEFAULT '0',
  FK_CBBID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (BLID),
  UNIQUE KEY FK_CBBID_BLPosition_UN (FK_CBBID,BLPosition),
  KEY FK_CBBID (FK_CBBID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cb_box_smalllink;
CREATE TABLE mc_contentitem_cb_box_smalllink (
  SLID int(11) NOT NULL AUTO_INCREMENT,
  SLTitle varchar(255) NOT NULL DEFAULT '',
  SLLink int(11) NOT NULL DEFAULT '0',
  SLPosition int(11) NOT NULL DEFAULT '0',
  FK_CBBID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SLID),
  UNIQUE KEY FK_CBBID_SLPosition_UN (FK_CBBID,SLPosition),
  KEY FK_CIID (FK_CBBID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cm;
CREATE TABLE mc_contentitem_cm (
  CMID int(11) NOT NULL AUTO_INCREMENT,
  CMTitle1 varchar(150) NOT NULL DEFAULT '',
  CMTitle2 varchar(150) NOT NULL DEFAULT '',
  CMTitle3 varchar(150) NOT NULL DEFAULT '',
  CMImage1 varchar(150) NOT NULL DEFAULT '',
  CMImage2 varchar(150) NOT NULL DEFAULT '',
  CMImage3 varchar(150) NOT NULL DEFAULT '',
  CMImageTitles text,
  CMText1 text,
  CMText2 text,
  CMText3 text,
  FK_CGID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CMID),
  KEY FK_CID (FK_CIID),
  KEY FK_CGID (FK_CGID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp;
CREATE TABLE mc_contentitem_cp (
  CPID int(11) NOT NULL AUTO_INCREMENT,
  CPTitle1 varchar(150) NOT NULL DEFAULT '',
  CPTitle2 varchar(150) NOT NULL DEFAULT '',
  CPTitle3 varchar(150) NOT NULL DEFAULT '',
  CPText1 text,
  CPText2 text,
  CPText3 text,
  CPImage1 varchar(150) NOT NULL DEFAULT '',
  CPImage2 varchar(150) NOT NULL DEFAULT '',
  CPImage3 varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_cartsetting;
CREATE TABLE mc_contentitem_cp_cartsetting (
  CPCID int(11) NOT NULL AUTO_INCREMENT,
  CPCTitle varchar(50) NOT NULL DEFAULT '',
  CPCText text,
  CPCPrice float NOT NULL DEFAULT '0',
  CPCPosition tinyint(2) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPCID),
  UNIQUE KEY FK_SID_CPCPosition_UN (FK_SID,CPCPosition),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_info;
CREATE TABLE mc_contentitem_cp_info (
  CPIID int(11) NOT NULL AUTO_INCREMENT,
  CPIName varchar(150) NOT NULL DEFAULT '',
  CPIType enum('checkbox','text') NOT NULL DEFAULT 'checkbox',
  CPIPosition tinyint(4) NOT NULL DEFAULT '0',
  CPIDeleted tinyint(1) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPIID),
  UNIQUE KEY FK_SID_CPIPosition_UN (FK_SID,CPIPosition),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_order;
CREATE TABLE mc_contentitem_cp_order (
  CPOID int(11) NOT NULL AUTO_INCREMENT,
  CPOCreateDateTime datetime DEFAULT NULL,
  CPOChangeDateTime datetime DEFAULT NULL,
  CPOTotalPrice double NOT NULL DEFAULT '0',
  CPOTotalTax double NOT NULL DEFAULT '0',
  CPOTotalPriceWithoutTax double NOT NULL DEFAULT '0',
  CPOSubTotalPrice double NOT NULL DEFAULT '0',
  CPOSubTotalTax double NOT NULL DEFAULT '0',
  CPOSubTotalPriceWithoutTax double NOT NULL DEFAULT '0',
  CPOTransactionID varchar(255) NOT NULL DEFAULT '',
  CPOTransactionNumber varchar(50) NOT NULL DEFAULT '',
  CPOTransactionNumberDay int(11) NOT NULL DEFAULT '0',
  CPOTransactionStatus tinyint(4) NOT NULL DEFAULT '0',
  CPOTransactionSessionData text,
  CPOStatus tinyint(4) NOT NULL DEFAULT '0',
  CPOShippingCost double NOT NULL DEFAULT '0',
  CPOShippingCostWithoutTax double NOT NULL DEFAULT '0',
  FK_CPSID int(11) NOT NULL DEFAULT '0',
  CPOPaymentCost double NOT NULL DEFAULT '0',
  CPOPaymentCostWithoutTax double NOT NULL DEFAULT '0',
  FK_CYID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPOID),
  KEY FK_CPSID (FK_CPSID),
  KEY FK_CYID (FK_CYID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_order_cartsetting;
CREATE TABLE mc_contentitem_cp_order_cartsetting (
  CPOCTitle varchar(150) NOT NULL DEFAULT '',
  CPOCPosition int(11) NOT NULL DEFAULT '0',
  CPOCQuantity int(11) NOT NULL DEFAULT '0',
  CPOCSum double NOT NULL DEFAULT '0',
  CPOCUnitPrice double NOT NULL DEFAULT '0',
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  FK_CPCID int(11) NOT NULL DEFAULT '0',
  KEY FK_CPCID (FK_CPCID),
  KEY FK_CPOID (FK_CPOID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_order_customer;
CREATE TABLE mc_contentitem_cp_order_customer (
  CPOCID int(11) NOT NULL AUTO_INCREMENT,
  CPOCCompany varchar(150) NOT NULL DEFAULT '',
  CPOCPosition varchar(150) NOT NULL DEFAULT '',
  FK_FID int(11) NOT NULL DEFAULT '0',
  CPOCTitle varchar(50) NOT NULL DEFAULT '',
  CPOCFirstname varchar(150) NOT NULL DEFAULT '',
  CPOCLastName varchar(150) NOT NULL DEFAULT '',
  CPOCBirthday date DEFAULT NULL,
  CPOCCountry int(11) NOT NULL DEFAULT '0',
  CPOCZIP varchar(6) NOT NULL DEFAULT '',
  CPOCCity varchar(150) NOT NULL DEFAULT '',
  CPOCAddress varchar(150) NOT NULL DEFAULT '',
  CPOCPhone varchar(50) NOT NULL DEFAULT '',
  CPOCEmail varchar(100) NOT NULL DEFAULT '',
  CPOCText1 varchar(255) NOT NULL DEFAULT '',
  CPOCText2 varchar(255) NOT NULL DEFAULT '',
  CPOCText3 varchar(255) NOT NULL DEFAULT '',
  CPOCText4 varchar(255) NOT NULL DEFAULT '',
  CPOCText5 varchar(255) NOT NULL DEFAULT '',
  CPOCCreateDateTime datetime DEFAULT NULL,
  CPOCChangeDateTime datetime DEFAULT NULL,
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPOCID),
  KEY FK_FUID (FK_FUID),
  KEY FK_CPOID (FK_CPOID),
  KEY FK_FID (FK_FID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_order_info;
CREATE TABLE mc_contentitem_cp_order_info (
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  FK_CPIID int(11) NOT NULL DEFAULT '0',
  CPOIValue varchar(150) NOT NULL,
  KEY FK_CPOID (FK_CPOID),
  KEY FK_CPIID (FK_CPIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_order_item;
CREATE TABLE mc_contentitem_cp_order_item (
  CPOIID int(11) NOT NULL AUTO_INCREMENT,
  CPOITitle varchar(150) NOT NULL DEFAULT '',
  CPOINumber varchar(50) NOT NULL DEFAULT '',
  CPOIPosition int(11) NOT NULL DEFAULT '0',
  CPOIQuantity int(11) NOT NULL DEFAULT '0',
  CPOISum double NOT NULL DEFAULT '0',
  CPOITax double NOT NULL DEFAULT '0',
  CPOIUnitPrice double NOT NULL DEFAULT '0',
  CPOIProductPrice double NOT NULL DEFAULT '0',
  CPOITaxRate tinyint(4) NOT NULL DEFAULT '0',
  CPOITaxRatePercentage int(11) NOT NULL DEFAULT '0',
  FK_PPPID int(11) NOT NULL DEFAULT '0',
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPOIID),
  KEY FK_PPPID (FK_PPPID),
  KEY FK_CPOID (FK_CPOID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_order_item_option;
CREATE TABLE mc_contentitem_cp_order_item_option (
  FK_CPOIID int(11) NOT NULL DEFAULT '0',
  CPOIOName varchar(150) NOT NULL DEFAULT '',
  CPOIOPrice double NOT NULL DEFAULT '0',
  CPOIOPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_OPID int(11) NOT NULL DEFAULT '0',
  KEY FK_CPOIID (FK_CPOIID),
  KEY FK_OPID (FK_OPID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_order_shipping_address;
CREATE TABLE mc_contentitem_cp_order_shipping_address (
  CPOSID int(11) NOT NULL AUTO_INCREMENT,
  CPOSCompany varchar(150) NOT NULL DEFAULT '',
  CPOSPosition varchar(150) NOT NULL DEFAULT '',
  CPOSFoa varchar(25) NOT NULL DEFAULT '',
  CPOSTitle varchar(50) NOT NULL DEFAULT '',
  CPOSFirstname varchar(150) NOT NULL DEFAULT '',
  CPOSLastName varchar(150) NOT NULL DEFAULT '',
  CPOSBirthday date DEFAULT NULL,
  CPOSCountry int(11) NOT NULL DEFAULT '0',
  CPOSZIP varchar(6) NOT NULL DEFAULT '',
  CPOSCity varchar(150) NOT NULL DEFAULT '',
  CPOSAddress varchar(150) NOT NULL DEFAULT '',
  CPOSPhone varchar(50) NOT NULL DEFAULT '',
  CPOSEmail varchar(100) NOT NULL DEFAULT '',
  CPOSText1 varchar(255) NOT NULL DEFAULT '',
  CPOSText2 varchar(255) NOT NULL DEFAULT '',
  CPOSText3 varchar(255) NOT NULL DEFAULT '',
  CPOSText4 varchar(255) NOT NULL DEFAULT '',
  CPOSText5 varchar(255) NOT NULL DEFAULT '',
  CPOSCreateDateTime datetime DEFAULT NULL,
  CPOSChangeDateTime datetime DEFAULT NULL,
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FK_CPOID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPOSID),
  KEY FK_FUID (FK_FUID),
  KEY FK_CPOID (FK_CPOID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_payment_type;
CREATE TABLE mc_contentitem_cp_payment_type (
  CYID int(11) NOT NULL AUTO_INCREMENT,
  CYName varchar(150) NOT NULL DEFAULT '',
  CYClass varchar(50) NOT NULL DEFAULT '',
  CYText text,
  CYCosts float NOT NULL DEFAULT '0',
  CYPosition int(11) NOT NULL DEFAULT '0',
  CYNoPayment tinyint(1) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CYID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_payment_type_country;
CREATE TABLE mc_contentitem_cp_payment_type_country (
  FK_CYID int(11) NOT NULL DEFAULT '0',
  FK_COID int(11) NOT NULL DEFAULT '0',
  CPYCPrice float NOT NULL DEFAULT '0',
  KEY FK_CYID (FK_CYID),
  KEY FK_COID (FK_COID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_preferences;
CREATE TABLE mc_contentitem_cp_preferences (
  CPPID int(11) NOT NULL AUTO_INCREMENT,
  CPPName varchar(50) NOT NULL DEFAULT '',
  CPPValue varchar(25) NOT NULL DEFAULT '',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPPID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_contentitem_cp_preferences (CPPID, CPPName, CPPValue, FK_SID) VALUES
(1, 'cp_shipping_costs_maximum', '190', 1),
(2, 'cp_shipping_costs_free', '750', 1),
(3, 'cp_shipping_costs_percentage', '10', 1);

DROP TABLE IF EXISTS mc_contentitem_cp_shipment_mode;
CREATE TABLE mc_contentitem_cp_shipment_mode (
  CPSID int(11) NOT NULL AUTO_INCREMENT,
  CPSName varchar(150) NOT NULL DEFAULT '',
  CPSPrice float NOT NULL DEFAULT '0',
  CPSPosition int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CPSID),
  UNIQUE KEY FK_SID_CPSPosition_UN (FK_SID,CPSPosition),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cp_shipment_mode_country;
CREATE TABLE mc_contentitem_cp_shipment_mode_country (
  FK_CPSID int(11) NOT NULL DEFAULT '0',
  FK_COID int(11) NOT NULL DEFAULT '0',
  CPSCPrice float NOT NULL DEFAULT '0',
  KEY FK_CPSID (FK_CPSID),
  KEY FK_COID (FK_COID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cx;
CREATE TABLE mc_contentitem_cx (
  CXID int(11) NOT NULL AUTO_INCREMENT,
  CXTitle1 varchar(150) NOT NULL DEFAULT '',
  CXTitle2 varchar(150) NOT NULL DEFAULT '',
  CXTitle3 varchar(150) NOT NULL DEFAULT '',
  CXImage1 varchar(150) NOT NULL DEFAULT '',
  CXImage2 varchar(150) NOT NULL DEFAULT '',
  CXImage3 varchar(150) NOT NULL DEFAULT '',
  CXImageTitles text,
  CXText1 text,
  CXText2 text,
  CXText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CXID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cx_area;
CREATE TABLE mc_contentitem_cx_area (
  CXAID int(11) NOT NULL AUTO_INCREMENT,
  CXAIdentifier varchar(255) NOT NULL DEFAULT '',
  CXAPosition int(11) NOT NULL DEFAULT '0',
  CXADisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CXAID),
  UNIQUE KEY FK_CIID_CXAPosition_UN (FK_CIID,CXAPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_cx_area_element;
CREATE TABLE mc_contentitem_cx_area_element (
  CXAEID int(11) NOT NULL AUTO_INCREMENT,
  CXAEIdentifier varchar(255) NOT NULL DEFAULT '',
  CXAEType varchar(255) NOT NULL DEFAULT '',
  CXAEContent text,
  CXAEElementableID int(11) NOT NULL DEFAULT '0',
  CXAEElementableType varchar(255) NOT NULL DEFAULT '',
  CXAEPosition int(11) NOT NULL DEFAULT '0',
  CXAEDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CXAID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CXAEID),
  KEY FK_CIID (FK_CIID),
  KEY CXAEPosition (CXAEPosition)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_dl;
CREATE TABLE mc_contentitem_dl (
  DLID int(11) NOT NULL AUTO_INCREMENT,
  DLTitle varchar(150) NOT NULL DEFAULT '',
  DLImage varchar(150) NOT NULL DEFAULT '',
  DLImageTitles text,
  DLText1 text,
  DLText2 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (DLID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_dl_area;
CREATE TABLE mc_contentitem_dl_area (
  DAID int(11) NOT NULL AUTO_INCREMENT,
  DATitle varchar(150) NOT NULL DEFAULT '',
  DAImage varchar(150) NOT NULL DEFAULT '',
  DAImageTitles text,
  DAText text,
  DAPosition int(11) NOT NULL DEFAULT '0',
  DADisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (DAID),
  UNIQUE KEY FK_CIID_DAPosition_UN (FK_CIID,DAPosition),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_dl_area_file;
CREATE TABLE mc_contentitem_dl_area_file (
  DFID int(11) NOT NULL AUTO_INCREMENT,
  DFTitle varchar(150) NOT NULL DEFAULT '',
  DFFile varchar(150) DEFAULT NULL,
  DFCreated datetime DEFAULT NULL,
  DFModified datetime DEFAULT NULL,
  DFPosition int(11) NOT NULL DEFAULT '0',
  DFSize int(11) NOT NULL DEFAULT '0',
  FK_CFID int(11) DEFAULT NULL,
  FK_DAID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (DFID),
  UNIQUE KEY FK_DAID_DFPosition_UN (FK_DAID,DFPosition),
  KEY FK_CFID (FK_CFID),
  KEY FK_DAID (FK_DAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ec;
CREATE TABLE mc_contentitem_ec (
  ECID int(11) NOT NULL AUTO_INCREMENT,
  ECRecipient varchar(150) NOT NULL DEFAULT '',
  ECTitle varchar(150) NOT NULL DEFAULT '',
  ECText1 text,
  ECText2 text,
  ECText3 text,
  ECImage varchar(150) NOT NULL DEFAULT '',
  ECSettingABCLinks tinyint(4) NOT NULL DEFAULT '0',
  ECSettingLocationAddress tinyint(4) NOT NULL DEFAULT '1' COMMENT '1-Hidden 2-ShowOnce 3-PerEmployee',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_ETID int(11) NOT NULL DEFAULT '0',
  FK_EDID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (ECID),
  KEY FK_CID (FK_CIID),
  KEY FK_ETID (FK_ETID),
  KEY FK_EDID (FK_EDID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_es;
CREATE TABLE mc_contentitem_es (
  EID int(11) NOT NULL AUTO_INCREMENT,
  ETitle1 varchar(150) NOT NULL DEFAULT '',
  ETitle2 varchar(150) NOT NULL DEFAULT '',
  ETitle3 varchar(255) NOT NULL DEFAULT '',
  EText1 text,
  EText2 text,
  EText3 text,
  EImage1 varchar(150) NOT NULL DEFAULT '',
  EImage2 varchar(150) NOT NULL DEFAULT '',
  EImage3 varchar(150) NOT NULL DEFAULT '',
  EImageTitles text,
  EProperties text,
  EExt tinyint(4) NOT NULL DEFAULT '0',
  EFrameHeight smallint(6) DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_fq;
CREATE TABLE mc_contentitem_fq (
  FQID int(11) NOT NULL AUTO_INCREMENT,
  FQTitle1 varchar(255) NOT NULL DEFAULT '',
  FQTitle2 varchar(255) NOT NULL DEFAULT '',
  FQTitle3 varchar(255) NOT NULL DEFAULT '',
  FQText1 text,
  FQText2 text,
  FQText3 text,
  FQImage1 varchar(150) NOT NULL DEFAULT '',
  FQImage2 varchar(150) DEFAULT '',
  FQImage3 varchar(150) NOT NULL DEFAULT '',
  FQImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FQID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_fq_question;
CREATE TABLE mc_contentitem_fq_question (
  FQQID int(11) NOT NULL AUTO_INCREMENT,
  FQQTitle varchar(255) NOT NULL DEFAULT '',
  FQQText text,
  FQQImage varchar(150) NOT NULL DEFAULT '',
  FQQImageTitles text,
  FQQPosition int(11) NOT NULL DEFAULT '0',
  FQQDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FQQID),
  UNIQUE KEY FK_CIID_FQQPosition_UN (FK_CIID,FQQPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ib;
CREATE TABLE mc_contentitem_ib (
  IID int(11) NOT NULL AUTO_INCREMENT,
  ITitle varchar(150) NOT NULL DEFAULT '',
  IText text,
  IImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (IID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ip;
CREATE TABLE mc_contentitem_ip (
  IID int(11) NOT NULL AUTO_INCREMENT,
  ITitle varchar(150) NOT NULL DEFAULT '',
  IText text,
  IImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (IID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_log;
CREATE TABLE mc_contentitem_log (
  LID int(11) NOT NULL AUTO_INCREMENT,
  LDateTime datetime DEFAULT NULL,
  LType varchar(255) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  CIIdentifier varchar(255) NOT NULL DEFAULT '',
  FK_UID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (LID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_login;
CREATE TABLE mc_contentitem_login (
  LID int(11) NOT NULL AUTO_INCREMENT,
  LTitle1 varchar(150) NOT NULL DEFAULT '',
  LTitle2 varchar(150) NOT NULL DEFAULT '',
  LTitle3 varchar(150) NOT NULL DEFAULT '',
  LTitle4 varchar(150) NOT NULL DEFAULT '',
  LTitle5 varchar(150) NOT NULL DEFAULT '',
  LTitle6 varchar(150) NOT NULL DEFAULT '',
  LTitle7 varchar(150) NOT NULL DEFAULT '',
  LTitle8 varchar(150) NOT NULL DEFAULT '',
  LTitle9 varchar(150) NOT NULL DEFAULT '',
  LImage1 varchar(150) NOT NULL DEFAULT '',
  LImage2 varchar(150) NOT NULL DEFAULT '',
  LImage3 varchar(150) NOT NULL DEFAULT '',
  LImage4 varchar(150) NOT NULL DEFAULT '',
  LImage5 varchar(150) NOT NULL DEFAULT '',
  LImage6 varchar(150) NOT NULL DEFAULT '',
  LImage7 varchar(150) NOT NULL DEFAULT '',
  LImage8 varchar(150) NOT NULL DEFAULT '',
  LImage9 varchar(150) NOT NULL DEFAULT '',
  LText1 text,
  LText2 text,
  LText3 text,
  LText4 text,
  LText5 text,
  LText6 text,
  LText7 text,
  LText8 text,
  LText9 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (LID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ls;
CREATE TABLE mc_contentitem_ls (
  SID int(11) NOT NULL AUTO_INCREMENT,
  STitle1 varchar(150) NOT NULL DEFAULT '',
  STitle2 varchar(150) NOT NULL DEFAULT '',
  SText1 text,
  SText2 text,
  SImage1 varchar(150) NOT NULL DEFAULT '',
  SImage2 varchar(255) NOT NULL DEFAULT '',
  SImageTitles text,
  FK_LID int(11) NOT NULL DEFAULT '0',
  FK_YID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_mb;
CREATE TABLE mc_contentitem_mb (
  MBID int(11) NOT NULL AUTO_INCREMENT,
  MBTitle1 varchar(150) NOT NULL DEFAULT '',
  MBTitle2 varchar(150) NOT NULL DEFAULT '',
  MBTitle3 varchar(150) NOT NULL DEFAULT '',
  MBImage1 varchar(150) NOT NULL DEFAULT '',
  MBImage2 varchar(150) NOT NULL DEFAULT '',
  MBImage3 varchar(150) NOT NULL DEFAULT '',
  MBImageTitles text,
  MBText1 text,
  MBText2 text,
  MBText3 text,
  MBCategories varchar(255) DEFAULT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (MBID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_nl;
CREATE TABLE mc_contentitem_nl (
  NLID int(11) NOT NULL AUTO_INCREMENT,
  NLTitle1 varchar(150) NOT NULL DEFAULT '',
  NLTitle2 varchar(150) NOT NULL DEFAULT '',
  NLTitle3 varchar(150) NOT NULL DEFAULT '',
  NLText1 text,
  NLText2 text,
  NLText3 text,
  NLImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (NLID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pa;
CREATE TABLE mc_contentitem_pa (
  PID int(11) NOT NULL AUTO_INCREMENT,
  PTitle1 varchar(150) NOT NULL DEFAULT '',
  PTitle2 varchar(150) NOT NULL DEFAULT '',
  PTitle3 varchar(150) NOT NULL DEFAULT '',
  PText1 text,
  PText2 text,
  PText3 text,
  PImage1 varchar(150) NOT NULL DEFAULT '',
  PImage2 varchar(150) NOT NULL DEFAULT '',
  PImage3 varchar(150) NOT NULL DEFAULT '',
  PImageTitles text,
  FK_AVID text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pb;
CREATE TABLE mc_contentitem_pb (
  PBID int(11) NOT NULL AUTO_INCREMENT,
  PBTitle varchar(150) NOT NULL DEFAULT '',
  PBText text,
  PBImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PBID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pi;
CREATE TABLE mc_contentitem_pi (
  PID int(11) NOT NULL AUTO_INCREMENT,
  PTitle1 varchar(150) NOT NULL DEFAULT '',
  PTitle2 varchar(150) NOT NULL DEFAULT '',
  PTitle3 varchar(150) NOT NULL DEFAULT '',
  PText1 text,
  PText2 text,
  PText3 text,
  PImage1 varchar(150) NOT NULL DEFAULT '',
  PImage2 varchar(150) NOT NULL DEFAULT '',
  PImage3 varchar(150) NOT NULL DEFAULT '',
  PImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_po;
CREATE TABLE mc_contentitem_po (
  PID int(11) NOT NULL AUTO_INCREMENT,
  PTitle varchar(150) NOT NULL DEFAULT '',
  PText1 text,
  PText2 text,
  PText3 text,
  PImage1 varchar(150) NOT NULL DEFAULT '',
  PImage2 varchar(150) NOT NULL DEFAULT '',
  PImage3 varchar(150) NOT NULL DEFAULT '',
  PImageTitles text,
  PPrice float NOT NULL DEFAULT '0',
  PNumber varchar(50) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pp;
CREATE TABLE mc_contentitem_pp (
  PPID int(11) NOT NULL AUTO_INCREMENT,
  PPTitle1 varchar(255) NOT NULL DEFAULT '',
  PPTitle2 varchar(255) NOT NULL DEFAULT '',
  PPTitle3 varchar(255) NOT NULL DEFAULT '',
  PPText1 text,
  PPText2 text,
  PPText3 text,
  PPImage1 varchar(150) NOT NULL DEFAULT '',
  PPImage2 varchar(150) NOT NULL DEFAULT '',
  PPImage3 varchar(150) NOT NULL DEFAULT '',
  PPImage4 varchar(150) NOT NULL DEFAULT '',
  PPImage5 varchar(150) NOT NULL DEFAULT '',
  PPImage6 varchar(150) NOT NULL DEFAULT '',
  PPImage7 varchar(150) NOT NULL DEFAULT '',
  PPImage8 varchar(150) NOT NULL DEFAULT '',
  PPImageTitles text,
  PPPrice float NOT NULL DEFAULT '0',
  PPCasePacks tinyint(4) NOT NULL DEFAULT '1',
  PPShippingCosts float NOT NULL DEFAULT '0',
  FK_PPPID_Cheapest int(11) NOT NULL DEFAULT '0',
  PPTaxRate tinyint(4) NOT NULL DEFAULT '1',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PPID),
  KEY FK_CIID (FK_CIID),
  KEY FK_PPPID_Cheapest (FK_PPPID_Cheapest)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pp_attribute_global;
CREATE TABLE mc_contentitem_pp_attribute_global (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_AID int(11) NOT NULL DEFAULT '0',
  PPAPosition tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_CIID_PPAPosition_UN (FK_CIID,PPAPosition),
  KEY FK_CIID (FK_CIID),
  KEY FK_AID (FK_AID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pp_cartsetting;
CREATE TABLE mc_contentitem_pp_cartsetting (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_CPCID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_CIID,FK_CPCID),
  KEY FK_CIID (FK_CIID),
  KEY FK_CPCID (FK_CPCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pp_option;
CREATE TABLE mc_contentitem_pp_option (
  PPOID int(11) NOT NULL AUTO_INCREMENT,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_OPID int(11) NOT NULL DEFAULT '0',
  PPOPrice float NOT NULL DEFAULT '0',
  PPOPosition tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (PPOID),
  UNIQUE KEY FK_CIID_OPID_UN (FK_CIID,FK_OPID),
  UNIQUE KEY FK_CIID_PPOPosition_UN (FK_CIID,PPOPosition),
  KEY FK_CIID (FK_CIID),
  KEY FK_OPID (FK_OPID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pp_option_global;
CREATE TABLE mc_contentitem_pp_option_global (
  OPID int(11) NOT NULL AUTO_INCREMENT,
  OPCode varchar(10) NOT NULL DEFAULT '',
  OPName varchar(150) NOT NULL DEFAULT '',
  OPText text,
  OPImage varchar(150) NOT NULL DEFAULT '',
  OPPrice float NOT NULL DEFAULT '0',
  OPProduct tinyint(1) NOT NULL DEFAULT '1',
  OPPosition int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (OPID),
  UNIQUE KEY FK_SID_OPPosition_UN (OPPosition,FK_SID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pp_product;
CREATE TABLE mc_contentitem_pp_product (
  PPPID int(11) NOT NULL AUTO_INCREMENT,
  PPPTitle varchar(255) NOT NULL DEFAULT '',
  PPPText text,
  PPPImage1 varchar(150) NOT NULL DEFAULT '',
  PPPImage2 varchar(150) NOT NULL DEFAULT '',
  PPPImage3 varchar(150) NOT NULL DEFAULT '',
  PPPImage4 varchar(150) NOT NULL DEFAULT '',
  PPPImage5 varchar(150) NOT NULL DEFAULT '',
  PPPImage6 varchar(150) NOT NULL DEFAULT '',
  PPPImageTitles text,
  PPPPosition int(11) NOT NULL DEFAULT '0',
  PPPPrice float NOT NULL DEFAULT '0',
  PPPNumber varchar(50) NOT NULL DEFAULT '',
  PPPDisabled tinyint(1) NOT NULL DEFAULT '0',
  PPPShowOnLevel tinyint(4) NOT NULL DEFAULT '0',
  PPPAdditionalData text,
  PPPCasePacks tinyint(4) NOT NULL DEFAULT '0',
  PPPShippingCosts float NOT NULL DEFAULT '0',
  PPPTaxRate tinyint(4) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PPPID),
  UNIQUE KEY FK_CIID_PPPPosition_UN (FK_CIID,PPPPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pp_product_attribute;
CREATE TABLE mc_contentitem_pp_product_attribute (
  FK_PPPID int(11) NOT NULL DEFAULT '0',
  FK_AVID int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_PPPID_AVID_UN (FK_PPPID,FK_AVID),
  KEY FK_PPPID (FK_PPPID),
  KEY FK_AVID (FK_AVID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_pt;
CREATE TABLE mc_contentitem_pt (
  PID int(11) NOT NULL AUTO_INCREMENT,
  PTitle1 varchar(150) NOT NULL DEFAULT '',
  PTitle2 varchar(150) NOT NULL DEFAULT '',
  PTitle3 varchar(255) NOT NULL DEFAULT '',
  PText1 text,
  PText2 text,
  PText3 text,
  PImage1 varchar(150) NOT NULL DEFAULT '',
  PImage2 varchar(150) NOT NULL DEFAULT '',
  PImage3 varchar(150) NOT NULL DEFAULT '',
  PImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_qp;
CREATE TABLE mc_contentitem_qp (
  QPID int(11) NOT NULL AUTO_INCREMENT,
  QPTitle1 varchar(255) NOT NULL DEFAULT '',
  QPTitle2 varchar(255) NOT NULL DEFAULT '',
  QPTitle3 varchar(255) NOT NULL DEFAULT '',
  QPText1 text,
  QPText2 text,
  QPText3 text,
  QPImage1 varchar(150) NOT NULL DEFAULT '',
  QPImage2 varchar(150) NOT NULL DEFAULT '',
  QPImage3 varchar(150) NOT NULL DEFAULT '',
  QPImage4 varchar(150) NOT NULL DEFAULT '',
  QPImage5 varchar(150) NOT NULL DEFAULT '',
  QPImage6 varchar(150) NOT NULL DEFAULT '',
  QPImage7 varchar(150) NOT NULL DEFAULT '',
  QPImage8 varchar(150) NOT NULL DEFAULT '',
  QPImage9 varchar(150) NOT NULL DEFAULT '',
  QPImage10 varchar(150) NOT NULL DEFAULT '',
  QPImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (QPID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_qp_statement;
CREATE TABLE mc_contentitem_qp_statement (
  QPSID int(11) NOT NULL AUTO_INCREMENT,
  QPSTitle1 varchar(255) NOT NULL DEFAULT '',
  QPSTitle2 varchar(255) NOT NULL DEFAULT '',
  QPSTitle3 varchar(255) NOT NULL DEFAULT '',
  QPSTitle4 varchar(255) NOT NULL DEFAULT '',
  QPSText1 text,
  QPSText2 text,
  QPSText3 text,
  QPSText4 text,
  QPSImage1 varchar(150) NOT NULL DEFAULT '',
  QPSImage2 varchar(150) NOT NULL DEFAULT '',
  QPSImage3 varchar(150) NOT NULL DEFAULT '',
  QPSImage4 varchar(150) NOT NULL DEFAULT '',
  QPSImage5 varchar(150) NOT NULL DEFAULT '',
  QPSImage6 varchar(150) NOT NULL DEFAULT '',
  QPSImage7 varchar(150) NOT NULL DEFAULT '',
  QPSImage8 varchar(150) NOT NULL DEFAULT '',
  QPSImage9 varchar(150) NOT NULL DEFAULT '',
  QPSImage10 varchar(150) NOT NULL DEFAULT '',
  QPSImage11 varchar(150) NOT NULL DEFAULT '',
  QPSImageTitles text,
  QPSPosition int(11) NOT NULL DEFAULT '0',
  QPSDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (QPSID),
  UNIQUE KEY FK_CIID_QPSPosition_UN (FK_CIID,QPSPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_qs;
CREATE TABLE mc_contentitem_qs (
  QID int(11) NOT NULL AUTO_INCREMENT,
  QTitle1 varchar(255) NOT NULL DEFAULT '',
  QTitle2 varchar(255) NOT NULL DEFAULT '',
  QTitle3 varchar(255) NOT NULL DEFAULT '',
  QText1 text,
  QText2 text,
  QText3 text,
  QImage1 varchar(150) NOT NULL DEFAULT '',
  QImage2 varchar(150) NOT NULL DEFAULT '',
  QImage3 varchar(150) NOT NULL DEFAULT '',
  QImageTitles text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (QID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_qs_statement;
CREATE TABLE mc_contentitem_qs_statement (
  QSID int(11) NOT NULL AUTO_INCREMENT,
  QSTitle varchar(255) NOT NULL DEFAULT '',
  QSText text,
  QSImage varchar(150) NOT NULL DEFAULT '',
  QSImageTitles text,
  QSPosition int(11) NOT NULL DEFAULT '0',
  QSDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (QSID),
  UNIQUE KEY FK_CIID_QSPosition_UN (FK_CIID,QSPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_rl;
CREATE TABLE mc_contentitem_rl (
  RLID int(11) NOT NULL AUTO_INCREMENT,
  RLTitle1 varchar(150) NOT NULL DEFAULT '',
  RLTitle2 varchar(150) NOT NULL DEFAULT '',
  RLTitle3 varchar(150) NOT NULL DEFAULT '',
  RLImage1 varchar(150) NOT NULL DEFAULT '',
  RLImage2 varchar(150) NOT NULL DEFAULT '',
  RLImage3 varchar(150) NOT NULL DEFAULT '',
  RLImageTitles text,
  RLText1 text,
  RLText2 text,
  RLText3 text,
  RLTplType tinyint(4) NOT NULL DEFAULT '0',
  FK_RCID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (RLID),
  KEY FK_CID (FK_CIID),
  KEY FK_RCID (FK_RCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_rs;
CREATE TABLE mc_contentitem_rs (
  RSID int(11) NOT NULL AUTO_INCREMENT,
  RSTitle1 varchar(150) NOT NULL DEFAULT '',
  RSTitle2 varchar(150) NOT NULL DEFAULT '',
  RSTitle3 varchar(150) NOT NULL DEFAULT '',
  RSImage1 varchar(150) NOT NULL DEFAULT '',
  RSImage2 varchar(150) NOT NULL DEFAULT '',
  RSImage3 varchar(150) NOT NULL DEFAULT '',
  RSImageTitles text,
  RSText1 text,
  RSText2 text,
  RSText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (RSID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_sc;
CREATE TABLE mc_contentitem_sc (
  SID int(11) NOT NULL AUTO_INCREMENT,
  STitle1 varchar(150) NOT NULL DEFAULT '',
  STitle2 varchar(150) NOT NULL DEFAULT '',
  STitle3 varchar(150) NOT NULL DEFAULT '',
  SText1 text,
  SText2 text,
  SText3 text,
  SImage1 varchar(150) NOT NULL DEFAULT '',
  SImage2 varchar(150) NOT NULL DEFAULT '',
  SImage3 varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_sc_order;
CREATE TABLE mc_contentitem_sc_order (
  SOID int(11) NOT NULL AUTO_INCREMENT,
  SOCreateDateTime datetime DEFAULT NULL,
  SOChangeDateTime datetime DEFAULT NULL,
  SOTotalPrice double NOT NULL DEFAULT '0',
  SOTotalTax double NOT NULL DEFAULT '0',
  SOTotalPriceWithoutTax double NOT NULL DEFAULT '0',
  SOTransactionID varchar(255) NOT NULL DEFAULT '',
  SOTransactionNumber varchar(50) NOT NULL DEFAULT '',
  SOTransactionNumberDay int(11) NOT NULL DEFAULT '0',
  SOTransactionStatus tinyint(4) NOT NULL DEFAULT '0',
  SOStatus tinyint(4) NOT NULL DEFAULT '0',
  SOPaymentType tinyint(4) NOT NULL DEFAULT '0',
  SOShippingCost double NOT NULL DEFAULT '0',
  SOShippingDiscount double NOT NULL DEFAULT '0',
  SOShippingInsurance double NOT NULL DEFAULT '0',
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FK_CID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SOID),
  KEY FK_FUID (FK_FUID),
  KEY FK_CID (FK_CID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_sc_order_item;
CREATE TABLE mc_contentitem_sc_order_item (
  SOIID int(11) NOT NULL AUTO_INCREMENT,
  SOITitle varchar(150) NOT NULL DEFAULT '',
  SOINumber varchar(50) NOT NULL DEFAULT '',
  SOIPosition int(11) NOT NULL DEFAULT '0',
  SOIQuantity int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_SOID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SOIID),
  KEY FK_CIID (FK_CIID),
  KEY FK_SOID (FK_SOID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_sc_shipment_mode;
CREATE TABLE mc_contentitem_sc_shipment_mode (
  SMID int(11) NOT NULL AUTO_INCREMENT,
  SMName varchar(150) NOT NULL DEFAULT '',
  SMPrice float NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SMID),
  KEY FK_CID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_sc_shipment_mode_country;
CREATE TABLE mc_contentitem_sc_shipment_mode_country (
  FK_SMID int(11) NOT NULL DEFAULT '0',
  FK_COID int(11) NOT NULL DEFAULT '0',
  KEY FK_SMID (FK_SMID),
  KEY FK_COID (FK_COID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_sd;
CREATE TABLE mc_contentitem_sd (
  SDID int(11) NOT NULL AUTO_INCREMENT,
  SDTitle1 varchar(150) NOT NULL DEFAULT '',
  SDTitle2 varchar(150) NOT NULL DEFAULT '',
  SDTitle3 varchar(150) NOT NULL DEFAULT '',
  SDImage1 varchar(150) NOT NULL DEFAULT '',
  SDImage2 varchar(150) NOT NULL DEFAULT '',
  SDImage3 varchar(150) NOT NULL DEFAULT '',
  SDImageTitles text,
  SDText1 text,
  SDText2 text,
  SDText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SDID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_se;
CREATE TABLE mc_contentitem_se (
  SID int(11) NOT NULL AUTO_INCREMENT,
  STitle varchar(150) NOT NULL DEFAULT '',
  SText text,
  SImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_sp;
CREATE TABLE mc_contentitem_sp (
  PID int(11) NOT NULL AUTO_INCREMENT,
  PName varchar(150) DEFAULT NULL,
  PText1 text,
  PText2 text,
  PText3 text,
  PImage1 varchar(150) NOT NULL DEFAULT '',
  PImage2 varchar(150) NOT NULL DEFAULT '',
  PImage3 varchar(150) NOT NULL DEFAULT '',
  PImageTitles text,
  PShortDescription varchar(150) NOT NULL DEFAULT '',
  PNick varchar(100) NOT NULL DEFAULT '',
  PBirthday date DEFAULT NULL,
  PHeight varchar(50) NOT NULL DEFAULT '',
  PCountry varchar(50) NOT NULL DEFAULT '',
  PNumber tinyint(4) NOT NULL DEFAULT '0',
  PPosition varchar(100) NOT NULL DEFAULT '',
  PHobbies text,
  PFamilyStatus varchar(100) NOT NULL DEFAULT '',
  PHistory text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_TID int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (PID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_st;
CREATE TABLE mc_contentitem_st (
  STID int(11) NOT NULL AUTO_INCREMENT,
  STTitle1 varchar(150) NOT NULL DEFAULT '',
  STTitle2 varchar(150) NOT NULL DEFAULT '',
  STTitle3 varchar(150) NOT NULL DEFAULT '',
  STImage1 varchar(150) NOT NULL DEFAULT '',
  STImage2 varchar(150) NOT NULL DEFAULT '',
  STImage3 varchar(150) NOT NULL DEFAULT '',
  STImageTitles text,
  STText1 text,
  STText2 text,
  STText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (STID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_su;
CREATE TABLE mc_contentitem_su (
  SID int(11) NOT NULL AUTO_INCREMENT,
  STitle1 varchar(150) NOT NULL DEFAULT '',
  STitle2 varchar(150) NOT NULL DEFAULT '',
  STitle3 varchar(150) NOT NULL DEFAULT '',
  SText1 text,
  SText2 text,
  SText3 text,
  SImage1 varchar(150) NOT NULL DEFAULT '',
  SImage2 varchar(150) NOT NULL DEFAULT '',
  SImage3 varchar(150) NOT NULL DEFAULT '',
  SImageTitles text,
  FK_SID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_tag;
CREATE TABLE mc_contentitem_tag (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_TAID int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_CIID_FK_TAID (FK_CIID,FK_TAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_tg;
CREATE TABLE mc_contentitem_tg (
  TGID int(11) NOT NULL AUTO_INCREMENT,
  TGTitle varchar(150) NOT NULL DEFAULT '',
  TGText1 text,
  TGText2 text,
  TGText3 text,
  TGImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TGID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_tg_image;
CREATE TABLE mc_contentitem_tg_image (
  TGIID int(11) NOT NULL AUTO_INCREMENT,
  TGITitle varchar(150) NOT NULL DEFAULT '',
  TGIText text,
  TGIImage varchar(150) NOT NULL DEFAULT '',
  TGIImageTitle varchar(150) NOT NULL DEFAULT '',
  TGIPosition int(11) NOT NULL DEFAULT '0',
  TGICreateDateTime datetime DEFAULT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TGIID),
  UNIQUE KEY FK_CIID_BIPosition_UN (FK_CIID,TGIPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_tg_image_tags;
CREATE TABLE mc_contentitem_tg_image_tags (
  FK_TGIID int(11) NOT NULL DEFAULT '0',
  FK_TAID int(11) NOT NULL DEFAULT '0',
  TGITPosition int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_TGIID,FK_TAID),
  KEY FK_TGIID (FK_TGIID),
  KEY FK_TAID (FK_TAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ti;
CREATE TABLE mc_contentitem_ti (
  TID int(11) NOT NULL AUTO_INCREMENT,
  TTitle1 varchar(150) NOT NULL DEFAULT '',
  TTitle2 varchar(150) NOT NULL DEFAULT '',
  TTitle3 varchar(150) NOT NULL DEFAULT '',
  TImage1 varchar(150) NOT NULL DEFAULT '',
  TImage2 varchar(150) NOT NULL DEFAULT '',
  TImage3 varchar(150) NOT NULL DEFAULT '',
  TImageTitles text,
  TText1 text,
  TText2 text,
  TText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_to;
CREATE TABLE mc_contentitem_to (
  TID int(11) NOT NULL AUTO_INCREMENT,
  TTitle1 varchar(150) NOT NULL DEFAULT '',
  TTitle2 varchar(150) NOT NULL DEFAULT '',
  TTitle3 varchar(150) NOT NULL DEFAULT '',
  TText1 text,
  TText2 text,
  TText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ts;
CREATE TABLE mc_contentitem_ts (
  TID int(11) NOT NULL AUTO_INCREMENT,
  TTitle1 varchar(150) NOT NULL DEFAULT '',
  TTitle2 varchar(150) NOT NULL DEFAULT '',
  TTitle3 varchar(150) NOT NULL DEFAULT '',
  TImage1 varchar(150) NOT NULL DEFAULT '',
  TImage2 varchar(150) NOT NULL DEFAULT '',
  TImage3 varchar(150) NOT NULL DEFAULT '',
  TImageTitles text,
  TText1 text,
  TText2 text,
  TText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ts_block;
CREATE TABLE mc_contentitem_ts_block (
  TBID int(11) NOT NULL AUTO_INCREMENT,
  TBTitle varchar(255) NOT NULL DEFAULT '',
  TBText text,
  TBImage varchar(150) NOT NULL DEFAULT '',
  TBImageTitles text,
  TBPosition tinyint(4) NOT NULL DEFAULT '0',
  TBDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TBID),
  UNIQUE KEY FK_CIID_TBPosition_UN (FK_CIID,TBPosition)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_ts_block_link;
CREATE TABLE mc_contentitem_ts_block_link (
  TLID int(11) NOT NULL AUTO_INCREMENT,
  TLTitle varchar(150) NOT NULL DEFAULT '',
  TLLink int(11) NOT NULL DEFAULT '0',
  TLPosition int(11) NOT NULL DEFAULT '0',
  FK_TBID int(11) DEFAULT '0',
  PRIMARY KEY (TLID),
  UNIQUE KEY FK_TBID_TLPosition_UN (FK_TBID,TLPosition),
  KEY FK_TBID (FK_TBID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_va;
CREATE TABLE mc_contentitem_va (
  VID int(11) NOT NULL AUTO_INCREMENT,
  VTitle varchar(150) NOT NULL DEFAULT '',
  VText text,
  VImage varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (VID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_va_attributes;
CREATE TABLE mc_contentitem_va_attributes (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_AVID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_CIID,FK_AVID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_vc;
CREATE TABLE mc_contentitem_vc (
  VID int(11) NOT NULL AUTO_INCREMENT,
  VTitle1 varchar(150) NOT NULL DEFAULT '',
  VTitle2 varchar(150) NOT NULL DEFAULT '',
  VTitle3 varchar(150) NOT NULL DEFAULT '',
  VText1 text,
  VText2 text,
  VText3 text,
  VImage1 varchar(150) NOT NULL DEFAULT '',
  VImage2 varchar(150) NOT NULL DEFAULT '',
  VImage3 varchar(150) NOT NULL DEFAULT '',
  VImage4 varchar(150) NOT NULL DEFAULT '',
  VImageTitles text,
  VVideoType1 varchar(50) NOT NULL DEFAULT '',
  VVideo1 varchar(200) NOT NULL DEFAULT '',
  VVideoType2 varchar(50) NOT NULL DEFAULT '',
  VVideo2 varchar(200) NOT NULL DEFAULT '',
  VVideoType3 varchar(50) NOT NULL DEFAULT '',
  VVideo3 varchar(200) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (VID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_vd;
CREATE TABLE mc_contentitem_vd (
  VID int(11) NOT NULL AUTO_INCREMENT,
  VDocumentId varchar(100) NOT NULL DEFAULT '',
  VDocumentName varchar(50) NOT NULL DEFAULT '',
  VDocumentTitle varchar(100) NOT NULL DEFAULT '',
  VDocumentDescription varchar(255) NOT NULL DEFAULT '',
  VTitle1 varchar(150) NOT NULL DEFAULT '',
  VTitle2 varchar(150) NOT NULL DEFAULT '',
  VTitle3 varchar(150) NOT NULL DEFAULT '',
  VText1 text,
  VText2 text,
  VText3 text,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (VID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_words;
CREATE TABLE mc_contentitem_words (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  WWord varchar(191) NOT NULL DEFAULT '',
  WContentTitleCount int(11) NOT NULL DEFAULT '0',
  WTitleCount int(11) NOT NULL DEFAULT '0',
  WTextCount int(11) NOT NULL DEFAULT '0',
  WDownloadCount int(11) NOT NULL DEFAULT '0',
  WImageCount int(11) NOT NULL DEFAULT '0',
  KEY Words (FK_CIID,WWord),
  FULLTEXT KEY WWord (WWord)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_words_filelink;
CREATE TABLE mc_contentitem_words_filelink (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  WFFile varchar(150) DEFAULT NULL,
  WFTextCount int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_CIID_WFFile_UN (FK_CIID,WFFile)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_words_internallink;
CREATE TABLE mc_contentitem_words_internallink (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_CIID_Link int(11) DEFAULT NULL,
  UNIQUE KEY FK_CIID_FK_CIID_Link_UN (FK_CIID,FK_CIID_Link)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_xs;
CREATE TABLE mc_contentitem_xs (
  XSID int(11) NOT NULL AUTO_INCREMENT,
  XSUrl varchar(150) NOT NULL DEFAULT '',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (XSID),
  UNIQUE KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contentitem_xu;
CREATE TABLE mc_contentitem_xu (
  XUID int(11) NOT NULL AUTO_INCREMENT,
  XUUrl varchar(150) NOT NULL DEFAULT '',
  XULink int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (XUID),
  UNIQUE KEY FK_CIID (FK_CIID),
  KEY XULink (XULink)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_contenttype;
CREATE TABLE mc_contenttype (
  CTID int(11) NOT NULL AUTO_INCREMENT,
  CTClass varchar(150) NOT NULL DEFAULT '',
  CTActive tinyint(4) NOT NULL DEFAULT '1',
  CTPosition int(11) NOT NULL DEFAULT '0',
  FK_CTID int(11) NOT NULL DEFAULT '0',
  CTTemplate tinyint(4) NOT NULL DEFAULT '0',
  CTPageType tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (CTID),
  KEY FK_CTID (FK_CTID)
) ENGINE=MyISAM AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition, FK_CTID, CTTemplate, CTPageType) VALUES
(1, 'ContentItemTI', 0, 100, 0, 0, 1),
(3, 'ContentItemIB', 1, 1, 0, 0, 90),
(2, 'ContentItemPI', 0, 105, 0, 0, 1),
(4, 'ContentItemTO', 0, 101, 0, 0, 1),
(6, 'ContentItemTS', 0, 115, 0, 0, 1),
(7, 'ContentItemBI', 0, 116, 0, 0, 1),
(9, 'ContentItemEC', 0, 126, 0, 0, 1),
(10, 'ContentItemNL', 0, 127, 0, 0, 1),
(11, 'ContentItemBG', 0, 112, 0, 0, 1),
(12, 'ContentItemSE', 0, 135, 0, 0, 1),
(13, 'ContentItemPT', 0, 106, 0, 0, 1),
(14, 'ContentItemDL', 0, 120, 0, 0, 1),
(16, 'ContentItemQS', 1, 117, 0, 0, 1),
(15, 'ContentItemPO', 0, 109, 0, 0, 1),
(17, 'ContentItemPA', 0, 107, 0, 0, 1),
(19, 'ContentItemSC', 0, 136, 0, 0, 1),
(20, 'ContentItemLS', 0, 118, 0, 0, 1),
(21, 'ContentItemLL', 0, 139, 0, 0, 1),
(22, 'ContentItemSP', 0, 110, 0, 0, 1),
(24, 'ContentItemES', 0, 119, 0, 0, 1),
(27, 'ContentItemVC', 0, 121, 0, 0, 1),
(28, 'ContentItemCB', 0, 122, 0, 0, 1),
(29, 'ContentItemXS', 0, 138, 0, 0, 1),
(30, 'ContentItemXU', 0, 137, 0, 0, 1),
(31, 'ContentItemMO', 0, 140, 0, 0, 1),
(32, 'ContentItemLogin', 0, 141, 0, 0, 1),
(75, 'ContentItemLO', 1, 3, 0, 0, 90),
(76, 'ContentItemArchive', 0, 5, 0, 0, 90),
(77, 'ContentItemIP', 0, 2, 0, 0, 90),
(78, 'ContentItemLP', 0, 4, 0, 0, 90),
(79, 'ContentItemVA', 0, 6, 0, 0, 90),
(80, 'ContentItemBE', 0, 7, 0, 0, 90),
(33, 'ContentItemST', 0, 142, 0, 0, 1),
(34, 'ContentItemTG', 0, 111, 0, 0, 1),
(35, 'ContentItemRS', 0, 143, 0, 0, 1),
(36, 'ContentItemFQ', 0, 144, 0, 0, 1),
(38, 'ContentItemVD', 0, 146, 0, 0, 1),
(39, 'ContentItemCA', 0, 147, 0, 0, 1),
(42, 'ContentItemPP', 0, 150, 0, 0, 1),
(43, 'ContentItemCP', 0, 151, 0, 0, 1),
(44, 'ContentItemMB', 0, 152, 0, 0, 1),
(45, 'ContentItemQP', 0, 153, 0, 0, 1),
(55, 'ContentItemCM', 0, 154, 0, 0, 1),
(46, 'ContentItemRL', 0, 155, 0, 0, 1),
(81, 'ContentItemPB', 0, 8, 0, 0, 90),
(47, 'ContentItemSD', 0, 157, 0, 0, 1),
(56, 'ContentItemCX', 0, 158, 0, 0, 1);

DROP TABLE IF EXISTS mc_country;
CREATE TABLE mc_country (
  COID int(11) NOT NULL AUTO_INCREMENT,
  COName varchar(150) NOT NULL DEFAULT '',
  COSymbol varchar(10) NOT NULL DEFAULT '',
  COCode int(11) NOT NULL DEFAULT '0',
  COPosition int(11) NOT NULL DEFAULT '0',
  COActive tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (COID)
) ENGINE=MyISAM AUTO_INCREMENT=245 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_country (COID, COName, COSymbol, COCode, COPosition, COActive) VALUES
(1, 'Österreich', 'AT', 43, 501, 1),
(2, 'Deutschland', 'DE', 49, 502, 1),
(3, 'Schweiz', 'CH', 41, 503, 1),
(4, 'Frankreich', 'FR', 33, 504, 0),
(5, 'Italien', 'IT', 39, 505, 0),
(6, 'Niederlande', 'NL', 31, 506, 0),
(7, 'Polen', 'PL', 48, 507, 0),
(8, 'Portugal', 'PT', 351, 508, 0),
(9, 'Afghanistan', 'AF', 93, 509, 0),
(10, 'Albanien', 'AL', 355, 510, 0),
(11, 'Algerien', 'DZ', 213, 511, 0),
(12, 'Amerikanisch-Samoa', 'AS', 1, 512, 0),
(13, 'Andorra', 'AD', 376, 513, 0),
(14, 'Angola', 'AO', 244, 514, 0),
(15, 'Anguilla', 'AI', 1, 515, 0),
(16, 'Antarktis', 'AQ', 672, 516, 0),
(17, 'Antigua und Barbuda', 'AG', 1, 517, 0),
(18, 'Argentinien', 'AR', 54, 518, 0),
(19, 'Armenien', 'AM', 374, 519, 0),
(20, 'Aruba', 'AW', 297, 520, 0),
(21, 'Australien', 'AU', 61, 521, 0),
(22, 'Aserbaidschan', 'AZ', 994, 522, 0),
(23, 'Bahamas', 'BS', 1, 523, 0),
(24, 'Bahrain', 'BH', 973, 524, 0),
(25, 'Bangladesch', 'BD', 880, 525, 0),
(26, 'Barbados', 'BB', 1, 526, 0),
(27, 'Weißrussland', 'BY', 375, 527, 0),
(28, 'Belgien', 'BE', 32, 528, 0),
(29, 'Belize', 'BZ', 501, 529, 0),
(30, 'Benin', 'BJ', 229, 530, 0),
(31, 'Bermuda', 'BM', 1, 531, 0),
(32, 'Bhutan', 'BT', 975, 532, 0),
(33, 'Bolivien', 'BO', 591, 533, 0),
(34, 'Bosnien und Herzegowina', 'BA', 387, 534, 0),
(35, 'Botswana', 'BW', 267, 535, 0),
(36, 'Bouvetinsel', 'BV', 0, 536, 0),
(37, 'Brasilien', 'BR', 55, 537, 0),
(38, 'Britisches Territorium im Indischen Ozean', 'IO', 246, 538, 0),
(39, 'Brunei Darussalam', 'BN', 673, 539, 0),
(40, 'Bulgarien', 'BG', 359, 540, 0),
(41, 'Burkina Faso', 'BF', 226, 541, 0),
(42, 'Burundi', 'BI', 257, 542, 0),
(43, 'Kambodscha', 'KH', 855, 543, 0),
(44, 'Kamerun', 'CM', 237, 544, 0),
(45, 'Canada', 'CA', 1, 545, 0),
(46, 'Kap Verde', 'CV', 238, 546, 0),
(47, 'Kaimaninseln', 'KY', 1, 547, 0),
(48, 'Zentralafrikanische Republik', 'CF', 236, 548, 0),
(49, 'Tschad', 'TD', 235, 549, 0),
(50, 'Chile', 'CL', 56, 550, 0),
(51, 'China', 'CN', 86, 551, 0),
(52, 'Weihnachtsinsel', 'CX', 61, 552, 0),
(53, 'Kokosinsel (Keeling)', 'CC', 61, 553, 0),
(54, 'Kolumbien', 'CO', 57, 554, 0),
(55, 'Komoren', 'KM', 269, 555, 0),
(56, 'Kongo', 'CG', 242, 556, 0),
(57, 'Dem. Rep. Kongo', 'CD', 243, 557, 0),
(58, 'Cookinseln', 'CK', 682, 558, 0),
(59, 'Costa Rica', 'CR', 506, 559, 0),
(60, 'Elfenbeinküste', 'CI', 225, 560, 0),
(61, 'Kroatien', 'HR', 385, 561, 0),
(62, 'Kuba', 'CU', 53, 562, 0),
(63, 'Zypern', 'CY', 357, 563, 0),
(64, 'Tschechien', 'CZ', 420, 564, 0),
(65, 'Dänemark', 'DK', 45, 565, 0),
(66, 'Dschibuti', 'DJ', 253, 566, 0),
(67, 'Dominica', 'DM', 1, 567, 0),
(68, 'Dominikanische Republik', 'DO', 1, 568, 0),
(69, 'Ecuador', 'EC', 593, 569, 0),
(70, 'Ägypten', 'EG', 20, 570, 0),
(71, 'El Salvador', 'SV', 503, 571, 0),
(72, 'Äquatorialguinea', 'GQ', 240, 572, 0),
(73, 'Eritrea', 'ER', 291, 573, 0),
(74, 'Estland', 'EE', 372, 574, 0),
(75, 'Äthiopien', 'ET', 251, 575, 0),
(76, 'Falklandinseln', 'FK', 500, 576, 0),
(77, 'Färöer', 'FO', 298, 577, 0),
(78, 'Fidschi', 'FJ', 679, 578, 0),
(79, 'Finnland', 'FI', 358, 579, 0),
(80, 'Französisch-Guayana', 'GF', 594, 580, 0),
(81, 'Französisch-Polynesien', 'PF', 689, 581, 0),
(82, 'Französische Süd- und Antarktisgebiete', 'TF', 0, 582, 0),
(83, 'Gabun', 'GA', 241, 583, 0),
(84, 'Gambia', 'GM', 220, 584, 0),
(85, 'Georgien', 'GE', 995, 585, 0),
(86, 'Ghana', 'GH', 233, 586, 0),
(87, 'Gibraltar', 'GI', 350, 587, 0),
(88, 'Griechenland', 'GR', 30, 588, 0),
(89, 'Grönland', 'GL', 299, 589, 0),
(90, 'Grenada', 'GD', 1, 590, 0),
(91, 'Guadeloupe', 'GP', 590, 591, 0),
(92, 'Guam', 'GU', 1, 592, 0),
(93, 'Guatemala', 'GT', 502, 593, 0),
(94, 'Guernsey', 'GG', 44, 594, 0),
(95, 'Guinea', 'GN', 224, 595, 0),
(96, 'Guinea Bissau', 'GW', 245, 596, 0),
(97, 'Guyana', 'GY', 592, 597, 0),
(98, 'Haiti', 'HT', 509, 598, 0),
(99, 'Heard und McDonaldinseln', 'HM', 0, 599, 0),
(100, 'Vatikanstadt', 'VA', 379, 600, 0),
(101, 'Honduras', 'HN', 504, 601, 0),
(102, 'Hongkong', 'HK', 852, 602, 0),
(103, 'Ungarn', 'HU', 36, 603, 0),
(104, 'Island', 'IS', 354, 604, 0),
(105, 'Indien', 'IN', 91, 605, 0),
(106, 'Indonesien', 'ID', 62, 606, 0),
(107, 'Iran, ISLAMIC REP.', 'IR', 98, 607, 0),
(108, 'Irak', 'IQ', 964, 608, 0),
(109, 'Irland', 'IE', 353, 609, 0),
(110, 'Isle of Man', 'IM', 44, 610, 0),
(111, 'Israel', 'IL', 972, 611, 0),
(112, 'Jamaika', 'JM', 1, 612, 0),
(113, 'Japan', 'JP', 81, 613, 0),
(114, 'Jersey', 'JE', 44, 614, 0),
(115, 'Jordan', 'JO', 962, 615, 0),
(116, 'Kasachstan', 'KZ', 7, 616, 0),
(117, 'Kenia', 'KE', 254, 617, 0),
(118, 'Kiribati', 'KI', 686, 618, 0),
(119, 'Nordkorea', 'KP', 850, 619, 0),
(120, 'Süd Korea', 'KR', 82, 620, 0),
(121, 'Kuwait', 'KW', 965, 621, 0),
(122, 'Kirgisistan', 'KG', 996, 622, 0),
(123, 'Laos', 'LA', 856, 623, 0),
(124, 'Lettland', 'LV', 371, 624, 0),
(125, 'Libanon', 'LB', 961, 625, 0),
(126, 'Lesotho', 'LS', 266, 626, 0),
(127, 'Liberia', 'LR', 231, 627, 0),
(128, 'Libyen', 'LY', 218, 628, 0),
(129, 'Liechtenstein', 'LI', 423, 629, 0),
(130, 'Litauen', 'LT', 370, 630, 0),
(131, 'Luxemburg', 'LU', 352, 631, 0),
(132, 'Macao', 'MO', 853, 632, 0),
(133, 'Mazedonien', 'MK', 389, 633, 0),
(134, 'Madagaskar', 'MG', 261, 634, 0),
(135, 'Malawi', 'MW', 265, 635, 0),
(136, 'Malaysia', 'MY', 60, 636, 0),
(137, 'Malediven', 'MV', 960, 637, 0),
(138, 'Mali', 'ML', 223, 638, 0),
(139, 'Malta', 'MT', 356, 639, 0),
(140, 'Marshallinseln', 'MH', 692, 640, 0),
(141, 'Martinique', 'MQ', 0, 641, 0),
(142, 'Mauretanien', 'MR', 222, 642, 0),
(143, 'Mauritius', 'MU', 230, 643, 0),
(144, 'Mayotte', 'YT', 262, 644, 0),
(145, 'Mexiko', 'MX', 52, 645, 0),
(146, 'Mikronesien', 'FM', 691, 646, 0),
(147, 'Moldawien, REP.', 'MD', 373, 647, 0),
(148, 'Monaco', 'MC', 377, 648, 0),
(149, 'Mongolei', 'MN', 976, 649, 0),
(150, 'Montenegro', 'ME', 382, 650, 0),
(151, 'Montserrat', 'MS', 1, 651, 0),
(152, 'Marokko', 'MA', 212, 652, 0),
(153, 'Mosambik', 'MZ', 258, 653, 0),
(154, 'Myanmar', 'MM', 95, 654, 0),
(155, 'Namibia', 'NA', 264, 655, 0),
(156, 'Nauru', 'NR', 674, 656, 0),
(157, 'Nepal', 'NP', 977, 657, 0),
(158, 'Niederländische Antillen', 'AN', 599, 658, 0),
(159, 'Neukaledonien', 'NC', 687, 659, 0),
(160, 'Neuseeland', 'NZ', 64, 660, 0),
(161, 'Nicaragua', 'NI', 505, 661, 0),
(162, 'Niger', 'NE', 227, 662, 0),
(163, 'Nigeria', 'NG', 234, 663, 0),
(164, 'Niue', 'NU', 683, 664, 0),
(165, 'Norfolkinsel', 'NF', 672, 665, 0),
(166, 'Nördliche Marianen', 'MP', 1, 666, 0),
(167, 'Norwegen', 'NO', 47, 667, 0),
(168, 'Oman', 'OM', 968, 668, 0),
(169, 'Pakistan', 'PK', 92, 669, 0),
(170, 'Palau', 'PW', 680, 670, 0),
(171, 'Palästina', 'PS', 970, 671, 0),
(172, 'Panama', 'PA', 507, 672, 0),
(173, 'Papua-Neuguinea', 'PG', 675, 673, 0),
(174, 'Paraguay', 'PY', 595, 674, 0),
(175, 'Peru', 'PE', 51, 675, 0),
(176, 'Philippinen', 'PH', 63, 676, 0),
(177, 'Pitcairninseln', 'PN', 64, 677, 0),
(178, 'Puerto Rico', 'PR', 1, 678, 0),
(179, 'Katar', 'QA', 974, 679, 0),
(180, 'Réunion', 'RE', 0, 680, 0),
(181, 'Rumänien', 'RO', 40, 681, 0),
(182, 'Russland', 'RU', 7, 682, 0),
(183, 'Ruanda', 'RW', 250, 683, 0),
(184, 'St. Helena', 'SH', 290, 684, 0),
(185, 'St. Kitts und Nevis', 'KN', 1, 685, 0),
(186, 'St. Lucia', 'LC', 1, 686, 0),
(187, 'Saint-Pierre und Miquelon', 'PM', 508, 687, 0),
(188, 'Saint-Vincent', 'VC', 1, 688, 0),
(189, 'Samoa', 'WS', 685, 689, 0),
(190, 'San Marino', 'SM', 378, 690, 0),
(191, 'São Tomé und Príncipe', 'ST', 239, 691, 0),
(192, 'Saudi-Arabien', 'SA', 966, 692, 0),
(193, 'Senegal', 'SN', 221, 693, 0),
(194, 'Serbien', 'RS', 381, 694, 0),
(195, 'Seychellen', 'SC', 248, 695, 0),
(196, 'Sierra Leone', 'SL', 232, 696, 0),
(197, 'Singapur', 'SG', 65, 697, 0),
(198, 'Slowakei', 'SK', 421, 698, 0),
(199, 'Slowenien', 'SI', 386, 699, 0),
(200, 'Salomonen', 'SB', 677, 700, 0),
(201, 'Somalia', 'SO', 252, 701, 0),
(202, 'Südafrika', 'ZA', 27, 702, 0),
(203, 'Südgeorgien und die Südlichen Sandwichinseln', 'GS', 500, 703, 0),
(204, 'Spanien', 'ES', 34, 704, 0),
(205, 'Sri Lanka', 'LK', 94, 705, 0),
(206, 'Sudan', 'SD', 249, 706, 0),
(207, 'Suriname', 'SR', 597, 707, 0),
(208, 'Svalbard und Jan Mayen', 'SJ', 4779, 708, 0),
(209, 'Swasiland', 'SZ', 268, 709, 0),
(210, 'Schweden', 'SE', 46, 710, 0),
(211, 'Syrien', 'SY', 963, 711, 0),
(212, 'Taiwan', 'TW', 886, 712, 0),
(213, 'Tadschikistan', 'TJ', 992, 713, 0),
(214, 'Tansania', 'TZ', 255, 714, 0),
(215, 'Thailand', 'TH', 66, 715, 0),
(216, 'Timor', 'TL', 670, 716, 0),
(217, 'Togo', 'TG', 228, 717, 0),
(218, 'Tokelau', 'TK', 690, 718, 0),
(219, 'Tonga', 'TO', 676, 719, 0),
(220, 'Trinidad und Tobago', 'TT', 1, 720, 0),
(221, 'Tunesien', 'TN', 216, 721, 0),
(222, 'Türkei', 'TR', 90, 722, 0),
(223, 'Turkmenistan', 'TM', 993, 723, 0),
(224, 'Turks- und Caicosinseln', 'TC', 1, 724, 0),
(225, 'Tuvalu', 'TV', 688, 725, 0),
(226, 'Uganda', 'UG', 256, 726, 0),
(227, 'Ukraine', 'UA', 380, 727, 0),
(228, 'Vereinigte Arabische Emirate', 'AE', 971, 728, 0),
(229, 'Großbritannien', 'GB', 44, 729, 0),
(230, 'USA', 'US', 1, 730, 0),
(231, 'USA-Inseln', 'UM', 0, 731, 0),
(232, 'Uruguay', 'UY', 598, 732, 0),
(233, 'Usbekistan', 'UZ', 998, 733, 0),
(234, 'Vanuatu', 'VU', 678, 734, 0),
(235, 'Venezuela', 'VE', 58, 735, 0),
(236, 'Vietnam', 'VN', 84, 736, 0),
(237, 'Virgin Islands (British)', 'VG', 1, 737, 0),
(238, 'Virgin Islands (USA)', 'VI', 1, 738, 0),
(239, 'Wallis und Futuna', 'WF', 681, 739, 0),
(240, 'Westsahara', 'EH', 212, 740, 0),
(241, 'Jemen', 'YE', 967, 741, 0),
(242, 'Sambia', 'ZM', 260, 742, 0),
(243, 'Simbabwe', 'ZW', 263, 743, 0),
(244, 'Kosovo', 'XK', 381, 744, 0);

DROP TABLE IF EXISTS mc_country_contenttype;
CREATE TABLE mc_country_contenttype (
  FK_COID int(11) NOT NULL DEFAULT '0',
  FK_CTID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  COCName varchar(150) NOT NULL DEFAULT '',
  COCPosition int(11) NOT NULL DEFAULT '0',
  COCActive tinyint(1) NOT NULL DEFAULT '0',
  KEY FK_COID (FK_COID),
  KEY FK_CTID (FK_CTID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_cron_log;
CREATE TABLE mc_cron_log (
  FK_CID int(11) NOT NULL DEFAULT '0',
  CDateTime datetime DEFAULT NULL,
  CText text,
  CStatus tinyint(1) NOT NULL DEFAULT '0',
  KEY FK_CID (FK_CID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_download_log;
CREATE TABLE mc_download_log (
  DLID int(11) NOT NULL AUTO_INCREMENT,
  DLDatetime datetime DEFAULT NULL,
  DLFile varchar(150) NOT NULL DEFAULT '',
  DLFiletypeType varchar(150) NOT NULL DEFAULT '',
  DLFileableId int(11) NOT NULL DEFAULT '0',
  FK_FUID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (DLID),
  KEY DLFileableId (DLFileableId),
  KEY FK_FUID (FK_FUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_extended_data;
CREATE TABLE mc_extended_data (
  ID int(11) NOT NULL AUTO_INCREMENT,
  Type varchar(150) NOT NULL DEFAULT '',
  Identifier varchar(150) NOT NULL DEFAULT '',
  Value text NOT NULL,
  ExtendableType varchar(150) NOT NULL DEFAULT '',
  ExtendableId int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (ID),
  KEY Extendable (ExtendableType,ExtendableId),
  KEY ExtendableType (ExtendableType),
  KEY ExtendableId (ExtendableId)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_externallink;
CREATE TABLE mc_externallink (
  ELID int(11) NOT NULL AUTO_INCREMENT,
  ELTitle varchar(150) NOT NULL DEFAULT '',
  ELUrl varchar(150) NOT NULL DEFAULT '',
  ELPosition int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (ELID),
  UNIQUE KEY FK_CIID_ELPosition_UN (FK_CIID,ELPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_file;
CREATE TABLE mc_file (
  FID int(11) NOT NULL AUTO_INCREMENT,
  FTitle varchar(150) NOT NULL DEFAULT '',
  FFile varchar(150) DEFAULT NULL,
  FCreated datetime DEFAULT NULL,
  FModified datetime DEFAULT NULL,
  FPosition int(11) NOT NULL DEFAULT '0',
  FSize int(11) NOT NULL DEFAULT '0',
  FK_CFID int(11) DEFAULT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FID),
  UNIQUE KEY FK_CIID_FPosition_UN (FK_CIID,FPosition),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user;
CREATE TABLE mc_frontend_user (
  FUID int(11) NOT NULL AUTO_INCREMENT,
  FUSID varchar(255) NOT NULL DEFAULT '',
  FUCompany varchar(150) NOT NULL DEFAULT '',
  FK_FUCID_Company int(11) NOT NULL DEFAULT '0',
  FUPosition varchar(150) NOT NULL DEFAULT '',
  FK_FID int(11) NOT NULL DEFAULT '0',
  FUTitle varchar(50) NOT NULL DEFAULT '',
  FUFirstname varchar(150) NOT NULL DEFAULT '',
  FUMiddlename varchar(255) NOT NULL DEFAULT '',
  FULastname varchar(150) NOT NULL DEFAULT '',
  FUNick varchar(50) NOT NULL DEFAULT '',
  FUPW varchar(50) NOT NULL DEFAULT '',
  FUBirthday date DEFAULT NULL,
  FUCountry int(11) NOT NULL DEFAULT '0',
  FUZIP varchar(6) NOT NULL DEFAULT '',
  FUCity varchar(150) NOT NULL DEFAULT '',
  FUAddress varchar(150) NOT NULL DEFAULT '',
  FUPhone varchar(50) NOT NULL DEFAULT '',
  FUMobilePhone varchar(50) NOT NULL DEFAULT '',
  FUFax varchar(50) NOT NULL DEFAULT '',
  FUEmail varchar(100) NOT NULL DEFAULT '',
  FUNewsletter tinyint(1) NOT NULL DEFAULT '0',
  FUUID varchar(25) NOT NULL DEFAULT '',
  FUDepartment varchar(255) NOT NULL DEFAULT '',
  FUCreateDateTime datetime DEFAULT NULL,
  FUChangeDateTime datetime DEFAULT NULL,
  FUAllowMultipleSessions tinyint(1) NOT NULL DEFAULT '0',
  FULastLogin datetime DEFAULT NULL,
  FUCountLogins int(11) NOT NULL DEFAULT '0',
  FUShowProfile tinyint(1) NOT NULL DEFAULT '0',
  FUActivationCode varchar(50) NOT NULL DEFAULT '',
  FUDeleted tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FUID),
  KEY FK_FUCID_Company (FK_FUCID_Company)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_company;
CREATE TABLE mc_frontend_user_company (
  FUCID int(11) NOT NULL AUTO_INCREMENT,
  FUCName varchar(255) NOT NULL DEFAULT '',
  FUCStreet varchar(255) NOT NULL DEFAULT '',
  FUCPostalCode varchar(255) NOT NULL DEFAULT '',
  FUCCity varchar(255) NOT NULL DEFAULT '',
  FK_CID_Country int(11) NOT NULL DEFAULT '0',
  FUCPhone varchar(255) NOT NULL DEFAULT '',
  FUCFax varchar(255) NOT NULL DEFAULT '',
  FUCEmail varchar(255) NOT NULL DEFAULT '',
  FUCWeb varchar(255) NOT NULL DEFAULT '',
  FUCNotes varchar(255) NOT NULL DEFAULT '',
  FUCType varchar(255) NOT NULL DEFAULT '',
  FUCImage varchar(255) NOT NULL DEFAULT '',
  FUCVatNumber varchar(255) NOT NULL DEFAULT '',
  FUCCreateDatetime datetime DEFAULT NULL,
  FUCChangeDatetime datetime DEFAULT NULL,
  FK_FUCAID_Area int(11) NOT NULL DEFAULT '0',
  FUCDeleted tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FUCID),
  KEY FK_CID_Country (FK_CID_Country),
  KEY FK_FUCAID_Area (FK_FUCAID_Area)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_company_area;
CREATE TABLE mc_frontend_user_company_area (
  FUCAID int(11) NOT NULL AUTO_INCREMENT,
  FUCAName varchar(255) NOT NULL DEFAULT '',
  FK_FUCAID_Parent int(11) NOT NULL DEFAULT '0',
  FUCADeleted tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FUCAID),
  KEY FK_FUCAID_Parent (FK_FUCAID_Parent)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_group;
CREATE TABLE mc_frontend_user_group (
  FUGID int(11) NOT NULL AUTO_INCREMENT,
  FUGName varchar(50) NOT NULL DEFAULT '',
  FUGDescription varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (FUGID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_group_pages;
CREATE TABLE mc_frontend_user_group_pages (
  FK_FUGID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_FUGID_CIID_UN (FK_FUGID,FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_group_sites;
CREATE TABLE mc_frontend_user_group_sites (
  FK_FUGID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_FUGID_SID_UN (FK_FUGID,FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_history_download;
CREATE TABLE mc_frontend_user_history_download (
  FK_FUID int(11) NOT NULL,
  FUHDDatetime datetime DEFAULT NULL,
  FUHDFile varchar(150) NOT NULL DEFAULT '',
  KEY FK_FUID (FK_FUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_history_login;
CREATE TABLE mc_frontend_user_history_login (
  FK_FUID int(11) NOT NULL DEFAULT '0',
  FUHLDatetime datetime DEFAULT NULL,
  KEY FK_FUID (FK_FUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_log;
CREATE TABLE mc_frontend_user_log (
  FK_FUID int(11) NOT NULL,
  FUSID varchar(50) NOT NULL DEFAULT '',
  FUCompany varchar(150) NOT NULL DEFAULT '',
  FK_FUCID_Company int(11) NOT NULL DEFAULT '0',
  FUPosition varchar(150) NOT NULL DEFAULT '',
  FK_FID int(11) NOT NULL DEFAULT '0',
  FUTitle varchar(50) NOT NULL DEFAULT '',
  FUFirstname varchar(150) NOT NULL DEFAULT '',
  FUMiddlename varchar(255) NOT NULL DEFAULT '',
  FULastname varchar(150) NOT NULL DEFAULT '',
  FUNick varchar(50) NOT NULL DEFAULT '',
  FUPW varchar(50) NOT NULL DEFAULT '',
  FUBirthday date DEFAULT NULL,
  FUCountry int(11) NOT NULL DEFAULT '0',
  FUZIP varchar(6) NOT NULL DEFAULT '',
  FUCity varchar(150) NOT NULL DEFAULT '',
  FUAddress varchar(150) NOT NULL DEFAULT '',
  FUPhone varchar(50) NOT NULL DEFAULT '',
  FUMobilePhone varchar(50) NOT NULL DEFAULT '',
  FUEmail varchar(100) NOT NULL,
  FUNewsletter tinyint(1) NOT NULL DEFAULT '1',
  FUUID varchar(25) NOT NULL DEFAULT '',
  FUFax varchar(50) NOT NULL DEFAULT '',
  FUDepartment varchar(255) NOT NULL DEFAULT '',
  FUCreateDateTime datetime DEFAULT NULL,
  FUChangeDateTime datetime DEFAULT NULL,
  FUAllowMultipleSessions tinyint(1) NOT NULL DEFAULT '0',
  FULastLogin datetime DEFAULT NULL,
  FUCountLogins int(11) NOT NULL DEFAULT '0',
  FUShowProfile tinyint(1) NOT NULL DEFAULT '0',
  FUActivationCode varchar(50) NOT NULL DEFAULT '',
  FUDeleted tinyint(1) NOT NULL DEFAULT '0',
  FULogDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  FK_UID_User int(11) NOT NULL DEFAULT '0',
  FK_UID_FrontendUser int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_FUID,FK_UID_User,FK_UID_FrontendUser,FULogDateTime),
  KEY FK_FUCID_Company (FK_FUCID_Company)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_rights;
CREATE TABLE mc_frontend_user_rights (
  FK_FUID int(11) NOT NULL,
  FK_FUGID int(11) NOT NULL,
  UNIQUE KEY FK_FUID_FUGID_UN (FK_FUID,FK_FUGID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_frontend_user_sessions;
CREATE TABLE mc_frontend_user_sessions (
  FUSSID varchar(255) NOT NULL DEFAULT '',
  FUSLastAction datetime DEFAULT NULL,
  FK_UID int(11) NOT NULL DEFAULT '0',
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_internallink;
CREATE TABLE mc_internallink (
  ILID int(11) NOT NULL AUTO_INCREMENT,
  ILTitle varchar(150) NOT NULL DEFAULT '',
  ILPosition int(11) NOT NULL DEFAULT '0',
  FK_CIID_Link int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (ILID),
  UNIQUE KEY FK_CIID_ILPosition_UN (FK_CIID,ILPosition),
  KEY FK_CIID (FK_CIID),
  KEY FK_CIID_Link (FK_CIID_Link)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_issuu_document;
CREATE TABLE mc_issuu_document (
  IDID int(11) NOT NULL AUTO_INCREMENT,
  IDDocumentId varchar(100) NOT NULL DEFAULT '' COMMENT 'Generated by Issuu',
  IDUsername varchar(255) NOT NULL DEFAULT '',
  IDName varchar(50) NOT NULL DEFAULT '' COMMENT 'Must be unique (http://issuu.com/<IDUsername>/docs/<IDName>)',
  IDTitle varchar(100) NOT NULL DEFAULT '',
  IDState varchar(1) NOT NULL DEFAULT '' COMMENT 'A-Active P-Processing F-Failure',
  IDErrorCode smallint(6) NOT NULL DEFAULT '0',
  IDCreateDateTime datetime DEFAULT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (IDID),
  UNIQUE KEY IDDocumentId_UN (IDDocumentId),
  UNIQUE KEY IDName_UN (IDName),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_log_simple;
CREATE TABLE mc_log_simple (
  ID bigint(20) NOT NULL AUTO_INCREMENT,
  Level varchar(10) NOT NULL DEFAULT '' COMMENT 'PSR-3 Log Levels: emergency, alert, critical, error, warning, notice, info, debug',
  DateTime datetime DEFAULT NULL,
  Identifier varchar(100) NOT NULL DEFAULT '' COMMENT 'Use this column to define some kind of categorization based on i.e. log origin',
  User varchar(255) NOT NULL DEFAULT '' COMMENT 'user ID, IP address, ...',
  Data mediumtext COMMENT 'The data to log: serialized, plaintext, json, ...',
  DataType varchar(100) NOT NULL DEFAULT '' COMMENT 'The log type, to identify the type of this log entry, i.e. CronResult, Mail, ApiErrorResponse, ApplicationConfigurationError, ...',
  PRIMARY KEY (ID),
  KEY Level (Level),
  KEY Identifier (Identifier),
  KEY DataType (DataType)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_moduletype_backend;
CREATE TABLE mc_moduletype_backend (
  MID int(11) NOT NULL AUTO_INCREMENT,
  MShortname varchar(50) NOT NULL DEFAULT '',
  MClass varchar(50) NOT NULL DEFAULT '',
  MActive tinyint(4) NOT NULL DEFAULT '1',
  MPosition int(11) NOT NULL DEFAULT '0',
  MRequired tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (MID),
  KEY MShortname (MShortname)
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) VALUES
(1, 'attribute', 'ModuleAttribute', 0, 2, 0),
(3, 'bugtracking', 'ModuleBugtracking', 1, 0, 1),
(4, 'cmsindex', 'ModuleCmsindex', 1, 0, 1),
(5, 'downloadticker', 'ModuleDownloadTicker', 0, 7, 0),
(6, 'employee', 'ModuleEmployee', 0, 16, 0),
(7, 'exportdata', 'ModuleExportData', 0, 11, 0),
(8, 'infoticker', 'ModuleInfoTicker', 0, 9, 0),
(9, 'leaguemanager', 'ModuleLeaguemanager', 0, 4, 0),
(10, 'mediamanagement', 'ModuleMediaManagement', 1, 15, 0),
(11, 'newsletter', 'ModuleNewsletter', 0, 20, 0),
(12, 'newsticker', 'ModuleNewsTicker', 0, 21, 0),
(14, 'sidebox', 'ModuleSideBox', 0, 26, 0),
(15, 'siteindex', 'ModuleSiteindex', 1, 0, 0),
(17, 'usermgmt', 'ModuleUserManagement', 1, 3, 0),
(18, 'edwinlinkstinymce', 'ModuleEdwinLinksTinyMCE', 1, 0, 1),
(19, 'imagecustomdata', 'ModuleImageCustomData', 1, 0, 1),
(20, 'frontendusermgmt', 'ModuleFrontendUserManagement', 0, 5, 0),
(21, 'blog', 'ModuleBlog', 0, 0, 0),
(22, 'blogmgmt', 'ModuleBlogManagement', 0, 12, 0),
(23, 'share', 'ModuleShare', 0, 0, 0),
(24, 'rssfeed', 'ModuleRssFeed', 0, 24, 0),
(25, 'announcement', 'ModuleAnnouncement', 0, 1, 0),
(26, 'frontendusertree', 'ModuleFrontendUserTree', 0, 0, 0),
(27, 'tag', 'ModuleTag', 0, 28, 0),
(28, 'reseller', 'ModuleReseller', 0, 23, 0),
(29, 'structurelinks', 'ModuleStructureLinks', 0, 0, 0),
(30, 'ordermgmt', 'ModuleOrderManagement', 0, 6, 0),
(31, 'treemgmtall', 'ModuleTreeManagementAll', 1, 0, 0),
(32, 'treemgmtleafonly', 'ModuleTreeManagementLeafOnly', 1, 0, 0),
(33, 'shopplusmgmt', 'ModuleShopPlusManagement', 0, 25, 0),
(34, 'customtext', 'ModuleCustomText', 1, 29, 0),
(35, 'leadmgmt', 'ModuleLeadManagement', 0, 13, 0),
(36, 'leadmgmtall', 'ModuleLeadManagementAll', 0, 0, 0),
(37, 'form', 'ModuleForm', 0, 0, 0),
(38, 'medialibrary', 'ModuleMultimediaLibrary', 0, 17, 0),
(39, 'tagcloud', 'ModuleTagcloud', 0, 27, 0),
(40, 'taglevel', 'ModuleTagLevel', 0, 0, 0),
(41, 'news', 'ModuleNews', 0, 18, 0),
(42, 'search', 'ModuleSearch', 1, 0, 1),
(43, 'copy', 'ModuleCopy', 1, 0, 0),
(44, 'additionaltextlevel', 'ModuleAdditionalTextLevel', 0, 0, 0),
(45, 'additionaltext', 'ModuleAdditionalText', 0, 0, 0),
(46, 'additionalimagelevel', 'ModuleAdditionalImageLevel', 0, 0, 0),
(47, 'additionalimage', 'ModuleAdditionalImage', 0, 0, 0),
(48, 'htmlcreator', 'ModuleHtmlCreator', 0, 19, 0),
(50, 'globalareamgmt', 'ModuleGlobalAreaManagement', 0, 8, 0),
(51, 'mobileswitch', 'ModuleMobileSwitch', 0, 0, 0),
(52, 'lookupmgmt', 'ModuleLookupManagement', 0, 14, 0),
(53, 'seomgmt', 'ModuleSeoManagement', 0, 0, 0),
(54, 'copytosite', 'ModuleCopyToSite', 0, 10, 0),
(55, 'popup', 'ModulePopUpManagement', 0, 22, 0),
(56, 'admin', 'ModuleAdmin', 1, 64, 0);

DROP TABLE IF EXISTS mc_moduletype_frontend;
CREATE TABLE mc_moduletype_frontend (
  MID int(11) NOT NULL AUTO_INCREMENT,
  MShortname varchar(50) NOT NULL DEFAULT '',
  MClass varchar(50) NOT NULL DEFAULT '',
  MActive tinyint(4) NOT NULL DEFAULT '1',
  MActiveMinimalMode tinyint(1) NOT NULL DEFAULT '0',
  MActiveLogin tinyint(1) NOT NULL DEFAULT '0',
  MActiveLandingPages tinyint(1) NOT NULL DEFAULT '0',
  MActiveUser tinyint(1) NOT NULL DEFAULT '0',
  MAvailableOnSites varchar(1000) NOT NULL DEFAULT '0',
  PRIMARY KEY (MID),
  KEY MShortname (MShortname)
) ENGINE=MyISAM AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser, MAvailableOnSites) VALUES
(1, 'doublenestednavlevel2', 'ModuleDoubleNestedNavLevel2', 0, 0, 0, 0, 0, '0'),
(2, 'doublenestednavlevel3', 'ModuleDoubleNestedNavLevel3', 0, 0, 0, 0, 0, '0'),
(3, 'downloadticker', 'ModuleDownloadTicker', 0, 0, 0, 0, 0, '0'),
(11, 'infoticker', 'ModuleInfoTicker', 0, 0, 0, 0, 0, '0'),
(12, 'languageswitch', 'ModuleLanguageSwitch', 0, 0, 0, 0, 0, '0'),
(13, 'leaguemanagerchartsocceraustria', 'ModuleLeaguemanagerChartSoccerAustria', 0, 0, 0, 0, 0, '0'),
(14, 'leaguemanagerchartbasketballaustria', 'ModuleLeaguemanagerChartBasketballAustria', 0, 0, 0, 0, 0, '0'),
(15, 'leaguemanagercountdown', 'ModuleLeaguemanagerCountdown', 0, 0, 0, 0, 0, '0'),
(16, 'nestednavlevel2', 'ModuleNestedNavLevel2', 0, 0, 0, 0, 0, '0'),
(17, 'nestednavlevel3', 'ModuleNestedNavLevel3', 0, 0, 0, 0, 0, '0'),
(18, 'newsticker', 'ModuleNewsTicker', 0, 0, 0, 0, 0, '0'),
(19, 'recommend', 'ModuleRecommend', 0, 0, 0, 0, 0, '0'),
(20, 'sccart', 'ModuleSCCart', 0, 0, 0, 0, 0, '0'),
(21, 'sidebox', 'ModuleSideBox', 0, 0, 0, 0, 0, '0'),
(22, 'sitesmenu', 'ModuleSitesMenu', 0, 0, 0, 0, 0, '0'),
(23, 'login', 'ModuleLogin', 0, 0, 0, 0, 0, '0'),
(24, 'imagefolder', 'ModuleImageFolder', 0, 0, 0, 0, 0, '0'),
(25, 'blog', 'ModuleBlog', 0, 0, 0, 0, 0, '0'),
(26, 'blogrecentcomments', 'ModuleBlogRecentComments', 0, 0, 0, 0, 0, '0'),
(33, 'leaguemanagerchartsocceraustriaeastleague', 'ModuleLeaguemanagerChartSoccerAustriaEastLeague', 0, 0, 0, 0, 0, '0'),
(34, 'leaguemanagerchartsocceraustriayouthleague', 'ModuleLeaguemanagerChartSoccerAustriaYouthLeague', 0, 0, 0, 0, 0, '0'),
(37, 'announcement', 'ModuleAnnouncement', 0, 0, 0, 0, 0, '0'),
(38, 'nestedlandingnavigation', 'ModuleNestedLandingNavigation', 0, 0, 0, 0, 0, '0'),
(39, 'nestednavlevel1', 'ModuleNestedNavLevel1', 0, 0, 0, 0, 0, '0'),
(40, 'sitemapnavmain', 'ModuleSitemapNavMain', 0, 0, 0, 0, 0, '0'),
(41, 'doublenestedloginnavlevel1', 'ModuleDoubleNestedLoginNavLevel1', 0, 0, 0, 0, 0, '0'),
(43, 'globalsidebox', 'ModuleGlobalSideBox', 0, 0, 0, 0, 0, '0'),
(42, 'geodetection', 'ModuleGeoDetection', 0, 0, 0, 0, 0, '0'),
(44, 'allsitesmenu', 'ModuleAllSitesMenu', 0, 0, 0, 0, 0, '0'),
(65, 'mobilebuttons', 'ModuleMobileButtons', 0, 0, 0, 0, 0, '0'),
(45, 'triplenestedloginnavlevel3', 'ModuleTripleNestedLoginNavLevel3', 0, 0, 0, 0, 0, '0'),
(46, 'feedteaser', 'ModuleFeedTeaser', 0, 0, 0, 0, 0, '0'),
(30, 'recentimages', 'ModuleRecentImages', 0, 0, 0, 0, 0, '0'),
(27, 'recentcontent', 'ModuleRecentContent', 0, 0, 0, 0, 0, '0'),
(28, 'recentblogentries', 'ModuleRecentBlogEntries', 0, 0, 0, 0, 0, '0'),
(29, 'recentdownloads', 'ModuleRecentDownloads', 0, 0, 0, 0, 0, '0'),
(47, 'newsletter', 'ModuleNewsletter', 0, 0, 0, 0, 0, '0'),
(48, 'nestedfooternavlevel1', 'ModuleNestedFooterNavLevel1', 0, 0, 0, 0, 0, '0'),
(49, 'cpcart', 'ModuleCPCart', 0, 0, 0, 0, 0, '0'),
(50, 'imagemap', 'ModuleImageMap', 0, 0, 0, 0, 0, '0'),
(51, 'form', 'ModuleForm', 0, 0, 0, 0, 0, '0'),
(52, 'mediasidebox', 'ModuleMultimediaSidebox', 0, 0, 0, 0, 0, '0'),
(54, 'languagedetection', 'ModuleLanguageDetection', 0, 0, 0, 0, 0, '0'),
(55, 'tagcloud', 'ModuleTagcloud', 0, 0, 0, 0, 0, '0'),
(56, 'customtext', 'ModuleCustomText', 1, 0, 0, 0, 0, '0'),
(57, 'employeebox', 'ModuleEmployeeBox', 0, 0, 0, 0, 0, '0'),
(58, 'news', 'ModuleNews', 0, 0, 0, 0, 0, '0'),
(59, 'breadcrumb', 'ModuleBreadcrumb', 1, 0, 0, 0, 0, '0'),
(60, 'facebooklikebox', 'ModuleFacebookLikebox', 0, 0, 0, 0, 0, '0'),
(61, 'twitterwidget', 'ModuleTwitterWidget', 0, 0, 0, 0, 0, '0'),
(62, 'recentcontentlist', 'ModuleRecentContentList', 0, 0, 0, 0, 0, '0'),
(63, 'productboxleveltagfilter', 'ModuleProductBoxLevelTagFilter', 0, 0, 0, 0, 0, '0'),
(64, 'globalarea', 'ModuleGlobalArea', 0, 0, 0, 0, 0, '0'),
(66, 'sitemapnavmainmobile', 'ModuleSitemapNavMainMobile', 1, 0, 0, 0, 0, '0'),
(67, 'popup', 'ModulePopUp', 0, 0, 0, 0, 0, '0'),
(69, 'sitemapnavfooter', 'ModuleSitemapNavFooter', 0, 0, 0, 0, 0, '0'),
(70, 'productboxteaser', 'ModuleProductBoxTeaser', 0, 0, 0, 0, 0, '0');

DROP TABLE IF EXISTS mc_module_announcement;
CREATE TABLE mc_module_announcement (
  AID int(11) NOT NULL AUTO_INCREMENT,
  ATitle varchar(255) NOT NULL DEFAULT '',
  ADateTime datetime DEFAULT NULL,
  AText text,
  APosition int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (AID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_attribute;
CREATE TABLE mc_module_attribute (
  AVID int(11) NOT NULL AUTO_INCREMENT,
  AVTitle varchar(150) NOT NULL DEFAULT '',
  AVText text,
  AVImage varchar(150) DEFAULT NULL,
  AVPosition int(11) NOT NULL DEFAULT '0',
  FK_AID int(11) NOT NULL DEFAULT '0',
  AVCode varchar(10) NOT NULL DEFAULT '',
  FK_ALID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (AVID),
  KEY FK_ALID (FK_ALID),
  KEY FK_AID (FK_AID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_attribute_global;
CREATE TABLE mc_module_attribute_global (
  AID int(11) NOT NULL AUTO_INCREMENT,
  ATitle varchar(150) NOT NULL DEFAULT '',
  AText text,
  APosition int(11) NOT NULL DEFAULT '0',
  AImages tinyint(1) NOT NULL DEFAULT '0',
  AIdentifier varchar(191) DEFAULT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  FK_CTID int(11) NOT NULL DEFAULT '0',
  FK_AGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (AID),
  UNIQUE KEY AIdentifier (AIdentifier),
  KEY FK_CTID (FK_CTID),
  KEY FK_AGID (FK_AGID)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_module_attribute_global (AID, ATitle, AText, APosition, AImages, AIdentifier, FK_SID, FK_CTID, FK_AGID) VALUES
(1, 'Mitarbeiter Standorte', '', 0, 0, 'en_location', 1, 0, 0),
(2, 'Mitarbeiter Abteilungen', '', 0, 0, 'ep_department', 1, 0, 0);

DROP TABLE IF EXISTS mc_module_attribute_global_link_group;
CREATE TABLE mc_module_attribute_global_link_group (
  AGID int(11) NOT NULL AUTO_INCREMENT,
  AGPosition tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (AGID),
  UNIQUE KEY AGID_AGPosition_UN (AGID,AGPosition)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_attribute_link_group;
CREATE TABLE mc_module_attribute_link_group (
  ALID int(11) NOT NULL AUTO_INCREMENT,
  ALPosition tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (ALID),
  UNIQUE KEY ALID_AGPosition_UN (ALID,ALPosition)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_customtext;
CREATE TABLE mc_module_customtext (
  CTID int(11) NOT NULL AUTO_INCREMENT,
  CTTitle varchar(150) NOT NULL DEFAULT '',
  CTText text,
  CTName varchar(150) NOT NULL DEFAULT '',
  CTDescription text,
  CTTemplateVariables varchar(255) NOT NULL DEFAULT '',
  CTPosition tinyint(4) NOT NULL DEFAULT '0',
  CTHtml tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  FK_CTCID_CustomtextCategory int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CTID),
  KEY FK_SID (FK_SID),
  KEY FK_CTCID_CustomtextCategory (FK_CTCID_CustomtextCategory)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_module_customtext (CTID, CTTitle, CTText, CTName, CTDescription, CTTemplateVariables, CTPosition, CTHtml, FK_SID, FK_CTCID_CustomtextCategory) VALUES
(11, '', '<b>Q2E Contentdesign</b> | Daniel Gran-Straße 48 | 3100 St. Pölten <br /> T +43 (0) 2742/27441-0 | F +43 (0) 2742/27441-90 | <a href=\"mailto:office@q2e.at\">office@q2e.at</a>', 'Text im Kopfbereich', 'Hier k&ouml;nnen Sie einen Text f&uuml;r den Kopfbereich der Webseite angeben ( z.B. Ihre Kontaktdaten ).<br/>', 'c_site_special_text_header', 1, 1, 1, 0),
(12, '', '<b>Q2E Contentdesign </b> | Daniel Gran-Straße 48 | 3100 St. Pölten <br /> T +43 (0) 2742/27441-0 | F +43 (0) 2742/27441-90 | <a href=\"mailto:office@q2e.at\">office@q2e.at</a>', 'Text in der Fußzeile', 'Hier k&ouml;nnen Sie einen Text f&uuml;r die Fu&szlig;zeile der Webseite angeben ( z.B. Ihre Kontaktdaten ).<br/>', 'c_site_special_text_footer', 2, 1, 1, 0);

DROP TABLE IF EXISTS mc_module_customtext_category;
CREATE TABLE mc_module_customtext_category (
  CTCID int(11) NOT NULL,
  CTCName varchar(255) NOT NULL DEFAULT '',
  CTCPosition int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CTCID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_downloadticker;
CREATE TABLE mc_module_downloadticker (
  DTID int(11) NOT NULL AUTO_INCREMENT,
  DTDownloadTitle varchar(150) DEFAULT NULL,
  DTLinkTitle varchar(150) DEFAULT NULL,
  DTPosition int(11) NOT NULL DEFAULT '0',
  FK_FID int(11) DEFAULT NULL,
  FK_DFID int(11) DEFAULT NULL,
  FK_CFID int(11) DEFAULT NULL,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (DTID),
  UNIQUE KEY FK_SID_DTPosition_UN (FK_SID,DTPosition),
  KEY FK_FID (FK_FID),
  KEY FK_DFID (FK_DFID),
  KEY FK_CFID (FK_CFID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_employee;
CREATE TABLE mc_module_employee (
  EID int(11) NOT NULL AUTO_INCREMENT,
  ETitle1 varchar(150) DEFAULT NULL,
  ETitle2 varchar(150) DEFAULT NULL,
  ETitle3 varchar(150) DEFAULT NULL,
  EText1 text,
  EText2 text,
  EText3 text,
  EImage1 varchar(150) DEFAULT NULL,
  EImage2 varchar(150) DEFAULT NULL,
  EImage3 varchar(150) DEFAULT NULL,
  EPosition int(11) NOT NULL DEFAULT '0',
  ENoRandom tinyint(4) NOT NULL DEFAULT '0',
  EUrl varchar(255) NOT NULL DEFAULT '',
  EFirstname varchar(150) NOT NULL DEFAULT '',
  ELastname varchar(150) NOT NULL DEFAULT '',
  EStaffNumber varchar(150) NOT NULL DEFAULT '',
  FK_FID int(11) NOT NULL DEFAULT '0',
  ETitle varchar(50) NOT NULL DEFAULT '',
  ECompany varchar(150) NOT NULL DEFAULT '',
  EInitials varchar(10) NOT NULL DEFAULT '',
  ECountry int(11) NOT NULL DEFAULT '0',
  EZIP varchar(6) NOT NULL DEFAULT '',
  ECity varchar(150) NOT NULL DEFAULT '',
  EAddress varchar(150) NOT NULL DEFAULT '',
  EPhone varchar(50) NOT NULL DEFAULT '',
  EPhoneDirectDial varchar(50) NOT NULL DEFAULT '',
  EFax varchar(50) NOT NULL DEFAULT '',
  EFaxDirectDial varchar(50) NOT NULL DEFAULT '',
  EMobilePhone varchar(50) NOT NULL DEFAULT '',
  EMobilePhoneDirectDial varchar(50) NOT NULL DEFAULT '',
  EEmail varchar(150) NOT NULL DEFAULT '',
  ERoom varchar(50) NOT NULL DEFAULT '',
  EJobTitle varchar(150) NOT NULL DEFAULT '',
  EFunction varchar(150) NOT NULL DEFAULT '',
  ESpecialism varchar(150) NOT NULL DEFAULT '',
  EHourlyRate double NOT NULL DEFAULT '0',
  FK_CGAID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EID),
  KEY FK_CGAID (FK_CGAID),
  KEY FK_CIID (FK_CIID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_employee_assignment;
CREATE TABLE mc_module_employee_assignment (
  FK_EID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  EABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_EID,FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_employee_attribute;
CREATE TABLE mc_module_employee_attribute (
  FK_EID int(11) NOT NULL DEFAULT '0',
  FK_AVID int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_EID_FK_AVID (FK_EID,FK_AVID),
  KEY FK_EID (FK_EID),
  KEY FK_AVID (FK_AVID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_globalareamgmt_assignment;
CREATE TABLE mc_module_globalareamgmt_assignment (
  FK_GAID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  GAABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_GAID,FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_global_area;
CREATE TABLE mc_module_global_area (
  GAID int(11) NOT NULL AUTO_INCREMENT,
  GATitle varchar(100) NOT NULL DEFAULT '',
  GAText text,
  GAImage varchar(100) NOT NULL DEFAULT '',
  GABoxType enum('large','medium','small') NOT NULL DEFAULT 'large',
  GAPosition tinyint(4) NOT NULL,
  GADisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  GAExtlink varchar(255) NOT NULL DEFAULT '',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (GAID),
  UNIQUE KEY FK_SID_GAPosition_UN (FK_SID,GAPosition)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_global_area_box;
CREATE TABLE mc_module_global_area_box (
  GABID int(11) NOT NULL AUTO_INCREMENT,
  GABTitle varchar(100) NOT NULL DEFAULT '',
  GABText text,
  GABImage varchar(100) NOT NULL DEFAULT '',
  GABNoImage tinyint(4) NOT NULL DEFAULT '0',
  GABNoText tinyint(4) NOT NULL DEFAULT '0',
  GABPosition tinyint(4) NOT NULL DEFAULT '0',
  GABPositionLocked tinyint(1) NOT NULL DEFAULT '0',
  GABDisabled tinyint(1) NOT NULL DEFAULT '0',
  GABShowFromDateTime datetime DEFAULT NULL,
  GABShowUntilDateTime datetime DEFAULT NULL,
  FK_CIID int(11) DEFAULT NULL,
  GABExtlink varchar(255) NOT NULL DEFAULT '',
  FK_GAID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (GABID),
  UNIQUE KEY FK_GAID_GABPosition_UN (FK_GAID,GABPosition),
  KEY FK_GAID (FK_GAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_html_creator;
CREATE TABLE mc_module_html_creator (
  HCID int(11) NOT NULL AUTO_INCREMENT,
  HCTitle1 varchar(255) NOT NULL DEFAULT '',
  HCTitle2 varchar(255) NOT NULL DEFAULT '',
  HCTitle3 varchar(255) NOT NULL DEFAULT '',
  HCText1 text,
  HCText2 text,
  HCText3 text,
  HCImage1 varchar(150) NOT NULL DEFAULT '',
  HCImage2 varchar(150) NOT NULL DEFAULT '',
  HCImage3 varchar(150) NOT NULL DEFAULT '',
  HCUrl varchar(255) NOT NULL DEFAULT '',
  HCCreateDateTime datetime DEFAULT NULL,
  HCChangeDateTime datetime DEFAULT NULL,
  HCDeleted tinyint(1) NOT NULL DEFAULT '0',
  HCTemplate varchar(100) NOT NULL DEFAULT '',
  HCCopiedFromID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (HCID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_html_creator_box;
CREATE TABLE mc_module_html_creator_box (
  HCBID int(11) NOT NULL AUTO_INCREMENT,
  HCBTitle varchar(255) NOT NULL DEFAULT '',
  HCBText text,
  HCBImage varchar(150) NOT NULL DEFAULT '',
  HCBUrl varchar(255) NOT NULL DEFAULT '',
  HCBTemplate varchar(255) NOT NULL DEFAULT '',
  HCBPosition int(11) NOT NULL DEFAULT '0',
  HCBDeleted tinyint(1) NOT NULL DEFAULT '0',
  FK_HCID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (HCBID),
  KEY FK_HCID (FK_HCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_html_creator_export_log;
CREATE TABLE mc_module_html_creator_export_log (
  HCELID int(11) NOT NULL AUTO_INCREMENT,
  HCELDateTime datetime DEFAULT NULL,
  FK_UID int(11) NOT NULL DEFAULT '0',
  FK_HCID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (HCELID),
  KEY FK_HCID (FK_HCID),
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_infoticker;
CREATE TABLE mc_module_infoticker (
  IID int(11) NOT NULL AUTO_INCREMENT,
  IText text,
  IRotationTime int(11) NOT NULL DEFAULT '0',
  IRandom tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (IID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_leaguemanager_game;
CREATE TABLE mc_module_leaguemanager_game (
  GID int(11) NOT NULL AUTO_INCREMENT,
  GDateTime datetime DEFAULT NULL,
  GTeamHome int(11) DEFAULT NULL,
  GTeamHomeScore int(11) NOT NULL DEFAULT '0',
  GTeamHomeScorePart1 tinyint(4) DEFAULT NULL,
  GTeamHomeScorePart2 tinyint(4) DEFAULT NULL,
  GTeamHomeScorePart3 tinyint(4) DEFAULT NULL,
  GTeamHomeScorePart4 tinyint(4) DEFAULT NULL,
  GTeamHomeScorePart5 tinyint(4) DEFAULT NULL,
  GTeamHomeLineup text,
  GTeamGuest int(11) DEFAULT NULL,
  GTeamGuestScore int(11) NOT NULL DEFAULT '0',
  GTeamGuestScorePart1 tinyint(4) DEFAULT NULL,
  GTeamGuestScorePart2 tinyint(4) DEFAULT NULL,
  GTeamGuestScorePart3 tinyint(4) DEFAULT NULL,
  GTeamGuestScorePart4 tinyint(4) DEFAULT NULL,
  GTeamGuestScorePart5 tinyint(4) DEFAULT NULL,
  GTeamGuestLineup text,
  GReport text,
  GScorer varchar(100) DEFAULT NULL,
  GText1 text,
  GText2 text,
  GText3 text,
  GImage1 varchar(150) DEFAULT NULL,
  GImage2 varchar(150) DEFAULT NULL,
  GImage3 varchar(150) DEFAULT NULL,
  GStatus tinyint(4) NOT NULL DEFAULT '1',
  GDeleted tinyint(4) NOT NULL DEFAULT '0',
  FK_YID int(11) NOT NULL DEFAULT '0',
  FK_LID int(11) NOT NULL DEFAULT '1',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (GID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_leaguemanager_game_ticker;
CREATE TABLE mc_module_leaguemanager_game_ticker (
  TID int(11) NOT NULL AUTO_INCREMENT,
  TMinute varchar(10) DEFAULT NULL,
  TImage varchar(100) DEFAULT NULL,
  TText text,
  TDeleted tinyint(4) NOT NULL DEFAULT '0',
  FK_GID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_leaguemanager_league;
CREATE TABLE mc_module_leaguemanager_league (
  LID int(11) NOT NULL AUTO_INCREMENT,
  LName varchar(150) NOT NULL DEFAULT '',
  LShortname varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (LID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_leaguemanager_team;
CREATE TABLE mc_module_leaguemanager_team (
  TID int(11) NOT NULL AUTO_INCREMENT,
  TName varchar(255) DEFAULT NULL,
  TShortName varchar(20) DEFAULT NULL,
  TImage1 varchar(150) DEFAULT NULL,
  TLocation varchar(150) DEFAULT NULL,
  TDeleted tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_leaguemanager_year;
CREATE TABLE mc_module_leaguemanager_year (
  YID int(11) NOT NULL AUTO_INCREMENT,
  YStartDate date DEFAULT NULL,
  YEndDate date DEFAULT NULL,
  YName varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (YID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_medialibrary;
CREATE TABLE mc_module_medialibrary (
  MID int(11) NOT NULL AUTO_INCREMENT,
  MTitle1 varchar(150) DEFAULT NULL,
  MTitle2 varchar(150) DEFAULT NULL,
  MTitle3 varchar(150) DEFAULT NULL,
  MText1 text,
  MText2 text,
  MText3 text,
  MImage1 varchar(150) DEFAULT NULL,
  MImage2 varchar(150) DEFAULT NULL,
  MImage3 varchar(150) DEFAULT NULL,
  MImage4 varchar(150) NOT NULL DEFAULT '',
  MImage5 varchar(150) NOT NULL DEFAULT '',
  MImage6 varchar(150) NOT NULL DEFAULT '',
  MVideoType1 varchar(50) NOT NULL DEFAULT '',
  MVideoPublishedDate1 datetime DEFAULT NULL,
  MVideoThumbnail1 varchar(150) NOT NULL DEFAULT '',
  MVideoDuration1 int(11) DEFAULT NULL,
  MVideo1 varchar(200) NOT NULL DEFAULT '',
  MVideoType2 varchar(50) NOT NULL DEFAULT '',
  MVideoPublishedDate2 datetime DEFAULT NULL,
  MVideoThumbnail2 varchar(150) NOT NULL DEFAULT '',
  MVideoDuration2 int(11) DEFAULT NULL,
  MVideo2 varchar(200) NOT NULL DEFAULT '',
  MVideoType3 varchar(50) NOT NULL DEFAULT '',
  MVideoPublishedDate3 datetime DEFAULT NULL,
  MVideoThumbnail3 varchar(150) NOT NULL DEFAULT '',
  MVideoDuration3 int(11) DEFAULT NULL,
  MVideo3 varchar(200) NOT NULL DEFAULT '',
  MPosition tinyint(11) NOT NULL DEFAULT '0',
  MUrl varchar(255) NOT NULL DEFAULT '',
  MImageTitles text,
  MRandomlyShow tinyint(4) NOT NULL DEFAULT '0',
  MShowFromDateTime datetime DEFAULT NULL,
  MShowUntilDateTime datetime DEFAULT NULL,
  MDisabled tinyint(4) NOT NULL DEFAULT '0',
  MCreateDateTime datetime DEFAULT NULL,
  MChangeDateTime datetime DEFAULT NULL,
  FK_IDID int(11) NOT NULL DEFAULT '0' COMMENT 'Issuu document',
  FK_CGAID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (MID),
  KEY FK_CIID (FK_CIID),
  KEY FK_SID (FK_SID),
  KEY FK_IDID (FK_IDID),
  KEY FK_CGAID (FK_CGAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_medialibrary_assignment;
CREATE TABLE mc_module_medialibrary_assignment (
  FK_MID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  MABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_MID,FK_CIID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_medialibrary_category;
CREATE TABLE mc_module_medialibrary_category (
  MCID int(11) NOT NULL AUTO_INCREMENT,
  MCTitle varchar(100) NOT NULL DEFAULT '',
  MCIdentifier varchar(100) NOT NULL DEFAULT '',
  MCPosition int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (MCID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_medialibrary_category_assignment;
CREATE TABLE mc_module_medialibrary_category_assignment (
  MCAID int(11) NOT NULL AUTO_INCREMENT,
  FK_MID int(11) NOT NULL DEFAULT '0',
  FK_MCID int(11) NOT NULL DEFAULT '0',
  MCAPosition int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (MCAID),
  UNIQUE KEY FK_MID_FK_MCID (FK_MID,FK_MCID),
  UNIQUE KEY FK_MCID_MCAPosition_UN (FK_MCID,MCAPosition),
  KEY FK_MID (FK_MID),
  KEY FK_MCID (FK_MCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_news;
CREATE TABLE mc_module_news (
  NWID int(11) NOT NULL AUTO_INCREMENT,
  NWTitle varchar(255) NOT NULL DEFAULT '',
  NWText text,
  NWStartDateTime datetime DEFAULT NULL,
  NWEndDateTime datetime DEFAULT NULL,
  NWCreateDateTime datetime DEFAULT NULL,
  NWChangeDateTime datetime DEFAULT NULL,
  FK_NWCID int(11) DEFAULT NULL,
  FK_UID int(11) DEFAULT NULL,
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (NWID),
  KEY FK_NWCID (FK_NWCID),
  KEY FK_UID (FK_UID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_newsletter_export;
CREATE TABLE mc_module_newsletter_export (
  EID int(11) NOT NULL AUTO_INCREMENT,
  EDateTime datetime DEFAULT NULL,
  EName varchar(50) NOT NULL DEFAULT '',
  FK_UID int(11) NOT NULL DEFAULT '0',
  KEY EID (EID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_newsticker;
CREATE TABLE mc_module_newsticker (
  TID int(11) NOT NULL AUTO_INCREMENT,
  TTitle varchar(150) NOT NULL DEFAULT '',
  TText text,
  TImage varchar(150) NOT NULL DEFAULT '',
  TSelectedItems varchar(255) DEFAULT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_news_category;
CREATE TABLE mc_module_news_category (
  NWCID int(11) NOT NULL AUTO_INCREMENT,
  NWCTitle varchar(255) NOT NULL DEFAULT '',
  NWCIdentifier varchar(255) NOT NULL DEFAULT '',
  NWCPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (NWCID),
  UNIQUE KEY NWCID_NWCPosition_UN (NWCID,NWCPosition),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_order_export;
CREATE TABLE mc_module_order_export (
  EID int(11) NOT NULL AUTO_INCREMENT,
  EDateTime datetime DEFAULT NULL,
  FK_UID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (EID),
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_popup;
CREATE TABLE mc_module_popup (
  PUID int(11) NOT NULL AUTO_INCREMENT,
  PUTitle1 varchar(150) DEFAULT NULL,
  PUTitle2 varchar(150) DEFAULT NULL,
  PUTitle3 varchar(150) DEFAULT NULL,
  PUText1 text,
  PUText2 text,
  PUText3 text,
  PUImage1 varchar(150) DEFAULT NULL,
  PUImage2 varchar(150) DEFAULT NULL,
  PUImage3 varchar(150) DEFAULT NULL,
  PUNoRandom tinyint(4) NOT NULL DEFAULT '0',
  PUUrl varchar(255) NOT NULL DEFAULT '',
  PUDisabled tinyint(1) NOT NULL DEFAULT '0',
  PUCreateDateTime datetime DEFAULT NULL,
  PUChangeDateTime datetime DEFAULT NULL,
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_popup_assignment;
CREATE TABLE mc_module_popup_assignment (
  FK_PUID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  PUABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_PUID,FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_popup_option;
CREATE TABLE mc_module_popup_option (
  PUOID int(11) NOT NULL AUTO_INCREMENT,
  PUOKey varchar(255) DEFAULT NULL,
  PUOValue text,
  FK_PUID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (PUOID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_reseller;
CREATE TABLE mc_module_reseller (
  RID int(11) NOT NULL AUTO_INCREMENT,
  RName varchar(255) NOT NULL DEFAULT '',
  RAddress varchar(255) NOT NULL DEFAULT '',
  RPostalCode varchar(50) NOT NULL DEFAULT '',
  RCity varchar(80) NOT NULL DEFAULT '',
  RCountry varchar(80) NOT NULL DEFAULT '',
  RCallNumber varchar(100) NOT NULL DEFAULT '',
  RFax varchar(100) NOT NULL DEFAULT '',
  REmail varchar(150) NOT NULL DEFAULT '',
  RWeb varchar(255) NOT NULL DEFAULT '',
  RNotes varchar(255) NOT NULL DEFAULT '',
  RType varchar(255) NOT NULL DEFAULT '',
  RImage varchar(255) NOT NULL DEFAULT '',
  RDefault tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (RID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_reseller_areas;
CREATE TABLE mc_module_reseller_areas (
  RAID int(11) NOT NULL AUTO_INCREMENT,
  RAName varchar(255) NOT NULL DEFAULT '',
  FK_RAID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (RAID),
  KEY FK_RAID (FK_RAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_reseller_assignation;
CREATE TABLE mc_module_reseller_assignation (
  FK_RAID int(11) NOT NULL DEFAULT '0',
  FK_RID int(11) NOT NULL DEFAULT '0',
  KEY FK_RAID (FK_RAID),
  KEY FK_RID (FK_RID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_reseller_category;
CREATE TABLE mc_module_reseller_category (
  RCID int(11) NOT NULL AUTO_INCREMENT,
  RCName varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (RCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_reseller_category_assignation;
CREATE TABLE mc_module_reseller_category_assignation (
  FK_RID int(11) NOT NULL DEFAULT '0',
  FK_RCID int(11) NOT NULL DEFAULT '0',
  KEY FK_RID (FK_RID),
  KEY FK_RCID (FK_RCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_reseller_labels;
CREATE TABLE mc_module_reseller_labels (
  FK_RAID int(11) NOT NULL DEFAULT '0',
  RLLanguage varchar(255) DEFAULT NULL,
  RLLabel varchar(255) DEFAULT NULL,
  KEY FK_RAID (FK_RAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_rssfeed;
CREATE TABLE mc_module_rssfeed (
  RID int(11) NOT NULL AUTO_INCREMENT,
  RTitle varchar(150) NOT NULL DEFAULT '',
  RText text,
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (RID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_rssfeed_items;
CREATE TABLE mc_module_rssfeed_items (
  FK_SID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY FK_SID_CIID_UN (FK_SID,FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_shopplusmgmt_log;
CREATE TABLE mc_module_shopplusmgmt_log (
  LDateTime datetime DEFAULT NULL,
  LAction varchar(255) NOT NULL DEFAULT '',
  FK_UID int(11) NOT NULL DEFAULT '0',
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_sidebox;
CREATE TABLE mc_module_sidebox (
  BID int(11) NOT NULL AUTO_INCREMENT,
  BTitle1 varchar(150) DEFAULT NULL,
  BTitle2 varchar(150) DEFAULT NULL,
  BTitle3 varchar(150) DEFAULT NULL,
  BText1 text,
  BText2 text,
  BText3 text,
  BImage1 varchar(150) DEFAULT NULL,
  BImage2 varchar(150) DEFAULT NULL,
  BImage3 varchar(150) DEFAULT NULL,
  BPosition tinyint(11) NOT NULL DEFAULT '0',
  BNoRandom tinyint(4) NOT NULL DEFAULT '0',
  BUrl varchar(255) NOT NULL DEFAULT '',
  FK_CGAID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (BID),
  KEY FK_CGAID (FK_CGAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_sidebox_assignment;
CREATE TABLE mc_module_sidebox_assignment (
  FK_BID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  BABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_BID,FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_siteindex_compendium;
CREATE TABLE mc_module_siteindex_compendium (
  SIID int(11) NOT NULL AUTO_INCREMENT,
  SITitle varchar(150) NOT NULL DEFAULT '',
  SIImage1 varchar(150) NOT NULL DEFAULT '',
  SIImage2 varchar(150) NOT NULL DEFAULT '',
  SIImage3 varchar(150) NOT NULL DEFAULT '',
  SIText1 text,
  SIText2 text,
  SIText3 text,
  SIImageTitles text,
  FK_CIID int(11) DEFAULT NULL,
  SIExtlink varchar(255) NOT NULL DEFAULT '',
  FK_SID int(11) NOT NULL DEFAULT '0',
  SIType varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (SIID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_siteindex_compendium_area;
CREATE TABLE mc_module_siteindex_compendium_area (
  SAID int(11) NOT NULL AUTO_INCREMENT,
  SATitle varchar(100) NOT NULL DEFAULT '',
  SAText text,
  SAImage varchar(100) NOT NULL DEFAULT '',
  SABoxType enum('large','medium','small') NOT NULL DEFAULT 'large',
  SAPosition tinyint(4) NOT NULL DEFAULT '0',
  SADisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  SAExtlink varchar(255) NOT NULL DEFAULT '',
  FK_SID int(11) NOT NULL DEFAULT '0',
  SASiteindexType varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (SAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_siteindex_compendium_area_box;
CREATE TABLE mc_module_siteindex_compendium_area_box (
  SBID int(11) NOT NULL AUTO_INCREMENT,
  SBTitle1 varchar(100) NOT NULL DEFAULT '',
  SBTitle2 varchar(100) NOT NULL DEFAULT '',
  SBTitle3 varchar(100) NOT NULL DEFAULT '',
  SBText1 text,
  SBText2 text,
  SBText3 text,
  SBImage1 varchar(100) NOT NULL DEFAULT '',
  SBImage2 varchar(100) NOT NULL DEFAULT '',
  SBImage3 varchar(100) NOT NULL DEFAULT '',
  SBNoImage tinyint(4) NOT NULL DEFAULT '0',
  SBPosition tinyint(4) NOT NULL DEFAULT '0',
  SBPositionLocked tinyint(1) NOT NULL DEFAULT '0',
  SBDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  SBExtlink varchar(255) NOT NULL DEFAULT '',
  FK_SAID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SBID),
  UNIQUE KEY FK_SAID_SBPosition_UN (FK_SAID,SBPosition),
  KEY FK_SAID (FK_SAID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_siteindex_compendium_mobile;
CREATE TABLE mc_module_siteindex_compendium_mobile (
  SIMID int(11) NOT NULL AUTO_INCREMENT,
  SIMText text,
  SIMType varchar(150) NOT NULL DEFAULT '',
  SIMPosition int(11) NOT NULL DEFAULT '0',
  SIMActive tinyint(1) NOT NULL DEFAULT '1',
  FK_SIMID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SIMID),
  KEY FK_SIMID (FK_SIMID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_tag;
CREATE TABLE mc_module_tag (
  TAID int(11) NOT NULL AUTO_INCREMENT,
  TATitle varchar(150) NOT NULL DEFAULT '',
  TAImage1 varchar(255) NOT NULL DEFAULT '',
  TAPosition int(11) NOT NULL DEFAULT '0',
  FK_TAGID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TAID),
  KEY FK_TAGID (FK_TAGID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_tagcloud;
CREATE TABLE mc_module_tagcloud (
  TCID int(11) NOT NULL AUTO_INCREMENT,
  TCTitle varchar(255) NOT NULL DEFAULT '',
  TCSize tinyint(4) NOT NULL DEFAULT '0',
  TCInternalUrl varchar(255) NOT NULL DEFAULT '',
  TCUrl varchar(255) NOT NULL DEFAULT '',
  TCPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_CIID int(11) DEFAULT NULL,
  FK_TCCID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (TCID),
  UNIQUE KEY TCID_TCPosition_UN (TCID,TCPosition),
  KEY FK_CIID (FK_CIID),
  KEY FK_SID (FK_SID),
  KEY FK_TCCID (FK_TCCID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_module_tagcloud_category;
CREATE TABLE mc_module_tagcloud_category (
  TCCID int(11) NOT NULL AUTO_INCREMENT,
  TCCTitle1 varchar(255) NOT NULL DEFAULT '',
  TCCTitle2 varchar(255) NOT NULL DEFAULT '',
  TCCTitle3 varchar(255) NOT NULL DEFAULT '',
  TCCText1 text,
  TCCText2 text,
  TCCText3 text,
  TCCPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TCCID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_module_tagcloud_category (TCCID, TCCTitle1, TCCTitle2, TCCTitle3, TCCText1, TCCText2, TCCText3, TCCPosition, FK_SID) VALUES
(1, 'Allgemein', '', '', '', '', '', 1, 1);

DROP TABLE IF EXISTS mc_module_tag_global;
CREATE TABLE mc_module_tag_global (
  TAGID int(11) NOT NULL AUTO_INCREMENT,
  TAGTitle varchar(150) NOT NULL DEFAULT '',
  TAGText text,
  TAGPosition int(11) NOT NULL DEFAULT '0',
  TAGContent tinyint(1) NOT NULL DEFAULT '0',
  TAGNeedsImage tinyint(1) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TAGID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_site;
CREATE TABLE mc_site (
  SID int(11) NOT NULL AUTO_INCREMENT,
  STitle varchar(150) NOT NULL DEFAULT '',
  SText text,
  SImage varchar(150) DEFAULT NULL,
  SPositionLanguage int(11) DEFAULT NULL,
  FK_SID_Language int(11) DEFAULT NULL,
  SPositionPortal int(11) DEFAULT NULL,
  FK_SID_Portal int(11) DEFAULT NULL,
  SLanguage varchar(20) NOT NULL DEFAULT 'german',
  SUrlInternal varchar(128) NOT NULL DEFAULT '',
  SUrlExternal varchar(128) NOT NULL DEFAULT '',
  SPathExternal varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (SID),
  UNIQUE KEY FK_SID_Language_SPositionLanguage_UN (FK_SID_Language,SPositionLanguage),
  UNIQUE KEY FK_SID_Portal_SPositionPortal_UN (FK_SID_Portal,SPositionPortal),
  KEY FK_SID_Language (FK_SID_Language),
  KEY FK_SID_Portal (FK_SID_Portal)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_site (SID, STitle, SText, SImage, SPositionLanguage, FK_SID_Language, SPositionPortal, FK_SID_Portal, SLanguage, SUrlInternal, SUrlExternal, SPathExternal) VALUES
(1, 'KUNDE', '', NULL, 1, NULL, 1, NULL, 'german', '', '', '');

DROP TABLE IF EXISTS mc_site_scope;
CREATE TABLE mc_site_scope (
  SCID int(11) NOT NULL AUTO_INCREMENT,
  SCContentItem tinyint(4) NOT NULL DEFAULT '0',
  SCDownload tinyint(4) NOT NULL DEFAULT '0',
  FK_SID_From int(11) NOT NULL DEFAULT '0',
  FK_SID_To int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SCID),
  UNIQUE KEY FK_SID_From_FK_SID_To_UN (FK_SID_From,FK_SID_To)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_structurelink;
CREATE TABLE mc_structurelink (
  SLID int(11) NOT NULL AUTO_INCREMENT,
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_CIID_Link int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (SLID),
  UNIQUE KEY FK_CIID_Link (FK_CIID_Link),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


DROP TABLE IF EXISTS mc_user;
CREATE TABLE mc_user (
  UID int(11) NOT NULL AUTO_INCREMENT,
  USID varchar(255) NOT NULL DEFAULT '',
  UNick varchar(50) NOT NULL DEFAULT '',
  UPW varchar(50) DEFAULT NULL,
  UEmail varchar(150) NOT NULL DEFAULT '',
  ULanguage varchar(20) NOT NULL DEFAULT 'german',
  UPreferredLanguage varchar(20) NOT NULL DEFAULT 'german',
  ULastLogin datetime DEFAULT NULL,
  UCountLogins int(11) NOT NULL DEFAULT '0',
  UDeleted tinyint(4) NOT NULL DEFAULT '0',
  UFirstname varchar(150) DEFAULT NULL,
  ULastname varchar(150) DEFAULT NULL,
  UBlocked tinyint(4) NOT NULL DEFAULT '0',
  UBlockedMessage text,
  UModuleRights text,
  PRIMARY KEY (UID)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_user (UID, USID, UNick, UPW, UEmail, ULanguage, UPreferredLanguage, ULastLogin, UCountLogins, UDeleted, UFirstname, ULastname, UBlocked, UBlockedMessage, UModuleRights) VALUES
(1, '', 'hoj', 'bd8d244718374165d22d15e204f5bf77', 'joe@q2e.at', 'german', 'german', NULL, 0, 0, 'Josef', 'Hörersdorfer', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(2, '', 'maa', '6954adaa4095ec4de345bcc22aa09c79', 'anton@q2e.at', 'german', 'german', NULL, 0, 0, 'Anton', 'Mayringer', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(4, '', 'ulb', '6739d81625f1f29f365cfe4a237a80e7', 'ulmer@q2e.at', 'german', 'german', NULL, 0, 0, 'Benjamin', 'Ulmer', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(5, '', 'daa', 'c5f8036ffdd0cf7c48aec391dc39d43b', 'david@q2e.at', 'german', 'german', NULL, 0, 0, 'Andrea', 'David', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(8, '', 'met', 'e520dcb7574b8ca379d650619792a06a', 'memelauer@q2e.at', 'german', 'german', NULL, 0, 0, 'Theresa', 'Memelauer', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(3, '', 'jua', '793c496f1c860c32dcc7032e36e5bb21', 'jungwirth@q2e.at', 'german', 'german', NULL, 0, 0, 'Anton', 'Jungwirth', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(14, '', 'olc', '56b2f07091fb42d5241100b2b095fd89', 'olivier@q2e.at', 'german', 'german', NULL, 0, 0, 'Christian', 'Olivier', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(15, '', 'hae', '44667aaa611c468fbea914c09a169e81', 'hammerschmid@q2e.at', 'german', 'german', NULL, 0, 0, 'Elisabeth', 'Hammerschmid', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext'),
(16, '', 'voh', 'bab42ece4b5e5a80698eebc37197e174', 'voelker@q2e.at', 'german', 'german', NULL, 0, 0, 'Hanna', 'Völker', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtleafonly,mediamanagement,siteindex,admin,customtext'),
(17, '', 'scj', '0f3de66ca7470c5f48ae33a7c7bb608e', 'schweighofer@q2e.at', 'german', 'german', NULL, 0, 0, 'Jakob', 'Schweighofer', 0, '', 'bugtracking,cmsindex,edwinlinkstinymce,imagecustomdata,search,usermgmt,copy,treemgmtall,mediamanagement,siteindex,admin,customtext');

DROP TABLE IF EXISTS mc_user_rights;
CREATE TABLE mc_user_rights (
  URID int(11) NOT NULL AUTO_INCREMENT,
  FK_UID int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  UPaths text,
  UScope enum('main','footer','hidden','login','pages','siteindex','user') DEFAULT NULL,
  PRIMARY KEY (URID),
  KEY FK_UID (FK_UID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_user_rights (URID, FK_UID, FK_SID, UPaths, UScope) VALUES
(46, 3, 1, '', 'footer'),
(45, 3, 1, '', 'main'),
(43, 1, 1, '', 'pages'),
(42, 1, 1, '', 'login'),
(41, 1, 1, '', 'footer'),
(40, 1, 1, '', 'main'),
(35, 5, 1, '', 'footer'),
(34, 5, 1, '', 'main'),
(52, 2, 1, '', 'footer'),
(51, 2, 1, '', 'main'),
(11, 6, 1, '', 'footer'),
(12, 6, 1, '', 'main'),
(61, 4, 1, '', 'footer'),
(60, 4, 1, '', 'main'),
(55, 8, 1, '', 'footer'),
(54, 8, 1, '', 'main'),
(44, 3, 1, '', 'siteindex'),
(39, 1, 1, '', 'siteindex'),
(33, 5, 1, '', 'siteindex'),
(50, 2, 1, '', 'siteindex'),
(21, 6, 1, '', 'siteindex'),
(59, 4, 1, '', 'siteindex'),
(53, 8, 1, '', 'siteindex'),
(58, 14, 1, '', 'footer'),
(57, 14, 1, '', 'main'),
(56, 14, 1, '', 'siteindex'),
(38, 15, 1, '', 'footer'),
(37, 15, 1, '', 'main'),
(36, 15, 1, '', 'siteindex'),
(64, 16, 1, '', 'footer'),
(63, 16, 1, '', 'main'),
(62, 16, 1, '', 'siteindex'),
(65, 17, 1, '', 'siteindex'),
(66, 17, 1, '', 'main'),
(67, 17, 1, '', 'footer');

DROP TABLE IF EXISTS mc_user_rights_submodules;
CREATE TABLE mc_user_rights_submodules (
  FK_UID int(11) NOT NULL DEFAULT '0',
  URMModuleShortname varchar(50) NOT NULL DEFAULT '',
  URMSubmodules varchar(250) DEFAULT NULL,
  PRIMARY KEY (FK_UID,URMModuleShortname)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_user_rights_submodules (FK_UID, URMModuleShortname, URMSubmodules) VALUES
(1, 'usermgmt', 'main'),
(1, 'copy', 'main'),
(3, 'usermgmt', 'main'),
(3, 'copy', 'main'),
(2, 'usermgmt', 'main'),
(2, 'copy', 'main'),
(2, 'treemgmtall', 'main'),
(6, 'usermgmt', 'main'),
(6, 'copy', 'main'),
(6, 'treemgmtall', 'main'),
(5, 'usermgmt', 'main'),
(5, 'copy', 'main'),
(4, 'usermgmt', 'main'),
(4, 'copy', 'main'),
(4, 'treemgmtall', 'main'),
(1, 'siteindex', 'mobile,main'),
(1, 'mediamanagement', 'node,area,all,main'),
(2, 'siteindex', 'mobile,main'),
(6, 'mediamanagement', 'node,area,all,main'),
(5, 'mediamanagement', 'node,area,all,main'),
(8, 'usermgmt', 'main'),
(8, 'copy', 'main'),
(1, 'treemgmtall', 'main'),
(3, 'treemgmtall', 'main'),
(3, 'mediamanagement', 'node,area,all,main'),
(6, 'customtext', 'main'),
(6, 'siteindex', 'mobile,main'),
(5, 'siteindex', 'mobile,main'),
(5, 'treemgmtall', 'main'),
(4, 'mediamanagement', 'node,area,all,main'),
(8, 'mediamanagement', 'node,area,all,main'),
(8, 'treemgmtall', 'main'),
(14, 'treemgmtall', 'main'),
(14, 'usermgmt', 'main'),
(14, 'copy', 'main'),
(15, 'usermgmt', 'main'),
(15, 'copy', 'main'),
(15, 'treemgmtall', 'main'),
(5, 'admin', 'check,log,main'),
(5, 'customtext', 'main'),
(15, 'mediamanagement', 'node,area,all,main'),
(15, 'siteindex', 'main'),
(15, 'admin', 'check,log,main'),
(15, 'customtext', 'main'),
(1, 'admin', 'check,log,main'),
(1, 'customtext', 'main'),
(3, 'siteindex', 'mobile,main'),
(3, 'admin', 'check,log,main'),
(3, 'customtext', 'main'),
(2, 'mediamanagement', 'node,area,all,main'),
(2, 'admin', 'check,log,main'),
(2, 'customtext', 'main'),
(8, 'siteindex', 'mobile,main'),
(8, 'admin', 'check,log,main'),
(8, 'customtext', 'main'),
(14, 'mediamanagement', 'node,area,all,main'),
(14, 'siteindex', 'main'),
(14, 'admin', 'check,log,main'),
(14, 'customtext', 'main'),
(4, 'siteindex', 'mobile,main'),
(4, 'admin', 'check,log,main'),
(4, 'customtext', 'main'),
(16, 'usermgmt', 'main'),
(16, 'copy', 'main'),
(16, 'treemgmtleafonly', 'main'),
(16, 'mediamanagement', 'node,area,all,main'),
(16, 'siteindex', 'main'),
(16, 'admin', 'check,log,main'),
(16, 'customtext', 'main'),
(17, 'usermgmt', 'main'),
(17, 'copy', 'main'),
(17, 'treemgmtall', 'main'),
(17, 'mediamanagement', 'node,area,all,main'),
(17, 'siteindex', 'main'),
(17, 'admin', 'check,log,main'),
(17, 'customtext', 'main');

DROP TABLE IF EXISTS mc_user_uploads;
CREATE TABLE mc_user_uploads (
  FK_UID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  UUFile varchar(255) DEFAULT NULL,
  UUName varchar(150) DEFAULT NULL,
  UUTime int(11) DEFAULT NULL,
  UUType tinyint(4) DEFAULT NULL,
  KEY FK_UID (FK_UID,UUType),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


-- 2020-01-31 08:54:53
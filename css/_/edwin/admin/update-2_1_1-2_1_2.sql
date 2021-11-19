/******************************************************/
/*         Neuer Ebenentyp VA - Varianten             */
/******************************************************/

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES (79, 'ContentItemVA', 0, 6);

CREATE TABLE mc_contentitem_va (
  VID int(11) NOT NULL auto_increment,
  VTitle varchar(150) NOT NULL,
  VText text,
  VImage varchar(150) NOT NULL,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (VID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_va_attributes (
  FK_CIID int(11) NOT NULL default '0',
  FK_AVID int(11) NOT NULL default '0',
  PRIMARY KEY (FK_CIID, FK_AVID)
) ENGINE=MyISAM;

ALTER TABLE mc_contentitem_ip ADD IImage VARCHAR( 150 ) NOT NULL AFTER IText; 
ALTER TABLE mc_contentitem_ib ADD IImage VARCHAR( 150 ) NOT NULL AFTER IText;

ALTER TABLE mc_module_attribute_global ADD AVariation TINYINT (1) NOT NULL DEFAULT 0;

/******************************************************/
/*         Neues Modul ImageFolder                    */
/******************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages) VALUES
(24, 'imagefolder', 'ModuleImageFolder', 0, 0, 0, 0);
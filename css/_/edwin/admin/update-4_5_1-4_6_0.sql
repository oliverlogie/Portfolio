/******************************************************************************/
/* Neuer Inhaltstyp für flexible Inhalte mit Bereichen und Auswahl von        */
/* Vorlagen                                                                   */
/******************************************************************************/

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
  UNIQUE KEY FK_CIID_CXAPosition_UN (FK_CIID, CXAPosition),
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
  CXAEDisabled tinyint(1) NOT NULL DEFAULT '0',
  FK_CXAID int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CXAEID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition, FK_CTID, CTTemplate, CTPageType) VALUES
(56, 'ContentItemCX', 0, 158, 0, 0, 1);
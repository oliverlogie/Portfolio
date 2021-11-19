/*************************************************************/
/* Normalen Index in mc_contentitem auf Primary Key upgraden */
/*************************************************************/

ALTER TABLE mc_contentitem
DROP INDEX CIID,
ADD PRIMARY KEY (CIID); 

/*******************************************************/
/* Tabellen für ModuleSiteindexCompendium überarbeiten */
/*******************************************************/

/* Tabellen umbenennen */
RENAME TABLE mc_module_siteindex_compendium TO mc_module_siteindex_compendium_box;
RENAME TABLE mc_module_siteindex_compendium_global TO mc_module_siteindex_compendium;

/* Spalten umbenennen */
ALTER TABLE mc_module_siteindex_compendium
CHANGE SBID SIID INT(11) NOT NULL AUTO_INCREMENT,
CHANGE SBTitle SITitle VARCHAR(150) NOT NULL,
CHANGE SBImage1 SIImage1 VARCHAR(150) NOT NULL,
CHANGE SBImage2 SIImage2 VARCHAR(150) NOT NULL,
CHANGE SBImage3 SIImage3 VARCHAR(150) NOT NULL,
CHANGE SBText1 SIText1 TEXT NOT NULL,
CHANGE SBText2 SIText2 TEXT NOT NULL,
CHANGE SBText3 SIText3 TEXT NOT NULL;

/*********************************/
/* ContentItemExternal aufteilen */
/*********************************/

/* ContentType ContentItemExternal auf ContentItemXS ändern */
UPDATE mc_contenttype
SET CTID = 29,
    CTClass = 'ContentItemXS',
    CTPosition = 48
WHERE CTID = 77;

/* Tabelle mc_contentitem_xs erstellen und befüllen */
CREATE TABLE mc_contentitem_xs (
  XSID INT NOT NULL AUTO_INCREMENT,
  XSUrl VARCHAR(150) NOT NULL,
  FK_CIID INT NOT NULL,
  PRIMARY KEY(XSID),
  UNIQUE(FK_CIID)
) ENGINE = MYISAM;

INSERT INTO mc_contentitem_xs (XSUrl, FK_CIID)
SELECT CIIdentifierSub, CIID
FROM mc_contentitem
WHERE FK_CTID = 77;

/* Einträge in mc_contentitem aktualisieren */
UPDATE mc_contentitem
SET CIIdentifierSub = NULL,
    CType = 1,
    FK_CTID = 29
WHERE FK_CTID = 77;

/* ContentItemXU hinzufügen und Tabelle mc_contentitem_xu erstellen*/
INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition)
VALUES(30, 'ContentItemXU', 0, 47);

CREATE TABLE mc_contentitem_xu (
  XUID INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  XUUrl VARCHAR(150) NOT NULL,
  FK_CIID INT NOT NULL,
  UNIQUE(FK_CIID)
) ENGINE = MYISAM;

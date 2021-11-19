/*************************************/
/* FK-System bei Downloads umstellen */
/*************************************/

/* Tabelle mc_file um FKs erweitern */
ALTER TABLE mc_file ADD FK_CIID INT NULL , ADD INDEX ( FK_CIID );
ALTER TABLE mc_file ADD FK_DAID INT NULL , ADD INDEX ( FK_DAID );
/* korrekte FKs befüllen */
UPDATE mc_file SET FK_CIID = (SELECT CIID FROM mc_contentitem         WHERE CDownloads REGEXP CONCAT("(^|,)", FID, "(,|$)"));
UPDATE mc_file SET FK_DAID = (SELECT DAID FROM mc_contentitem_dl_area WHERE DAFiles    REGEXP CONCAT("(^|,)", FID, "(,|$)"));
/* falsche FKs leeren */
UPDATE mc_contentitem SET CDownloads = NULL WHERE CIID IN (SELECT DISTINCT FK_CIID FROM mc_file);
ALTER TABLE mc_contentitem_dl_area DROP DAFiles;


/******************************************/
/* FK-System bei internen Links umstellen */
/******************************************/

/* Tabelle mc_internallink um FK erweitern */
ALTER TABLE mc_internallink ADD FK_CIID     INT NULL , ADD INDEX ( FK_CIID );
ALTER TABLE mc_internallink ADD FK_CIID_TS1 INT NULL , ADD INDEX ( FK_CIID_TS1 );
ALTER TABLE mc_internallink ADD FK_CIID_TS2 INT NULL , ADD INDEX ( FK_CIID_TS2 );
/* korrekte FKs befüllen */
UPDATE mc_internallink SET FK_CIID     = (SELECT CIID    FROM mc_contentitem    WHERE CInternalLinks  REGEXP CONCAT("(^|,)", ILID, "(,|$)"));
UPDATE mc_internallink SET FK_CIID_TS1 = (SELECT FK_CIID FROM mc_contentitem_ts WHERE TInternalLinks1 REGEXP CONCAT("(^|,)", ILID, "(,|$)"));
UPDATE mc_internallink SET FK_CIID_TS2 = (SELECT FK_CIID FROM mc_contentitem_ts WHERE TInternalLinks2 REGEXP CONCAT("(^|,)", ILID, "(,|$)"));
/* falsche FKs leeren */
UPDATE mc_contentitem SET CInternalLinks = NULL WHERE CIID IN (SELECT DISTINCT FK_CIID FROM mc_internallink);
ALTER TABLE mc_contentitem_ts DROP TInternalLinks1;
ALTER TABLE mc_contentitem_ts DROP TInternalLinks2;


/******************************************/
/* FK-System bei externen Links umstellen */
/******************************************/

/* Tabelle mc_externallink um FK erweitern */
ALTER TABLE mc_externallink ADD FK_CIID INT NULL , ADD INDEX ( FK_CIID );
/* korrekte FKs befüllen */
UPDATE mc_externallink SET FK_CIID = (SELECT CIID FROM mc_contentitem WHERE CExternalLinks REGEXP CONCAT("(^|,)", ELID, "(,|$)"));
/* falsche FKs leeren */
UPDATE mc_contentitem SET CExternalLinks = NULL WHERE CIID IN (SELECT DISTINCT FK_CIID FROM mc_externallink);


/*********************************/
/* Neues Feature Mediendatenbank */
/*********************************/

/* Tabelle mc_centralfile erstellen */
DROP TABLE IF EXISTS mc_centralfile;
CREATE TABLE mc_centralfile (
CFID INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
CFTitle VARCHAR( 150 ) NOT NULL ,
CFFile VARCHAR( 150 ) NOT NULL ,
CFCreated DATETIME NOT NULL ,
FK_SID INT NOT NULL ,
KEY FK_SID (FK_SID)
) ENGINE = MYISAM;
/* Tabelle mc_centralfile_relation erstellen */
DROP TABLE IF EXISTS mc_centralfile_relation;
CREATE TABLE mc_centralfile_relation (
FK_CIID INT NOT NULL ,
FK_CFID INT NOT NULL ,
CRTitle VARCHAR( 150 ) NULL ,
CRCreated DATETIME NOT NULL ,
PRIMARY KEY ( FK_CIID , FK_CFID )
) ENGINE = MYISAM;


/************************/
/* Sortierung Downloads */
/************************/
ALTER TABLE mc_file ADD FCreated DATETIME NOT NULL AFTER FFile;
UPDATE mc_file SET FCreated = NOW();

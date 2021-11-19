/******************************************************************************/
/* Zentrale Downloads aus der Mediendatenbank auf Issuu + Blätterkataloge im  */
/* DL                                                                         */
/******************************************************************************/

ALTER TABLE mc_centralfile
ADD FK_IDID_IssuuDocument INT( 11 ) NOT NULL AFTER FK_SID,
ADD INDEX( FK_IDID_IssuuDocument );

/******************************************************************************/
/* Gruppierung bei ModuleCustomText                                           */
/******************************************************************************/

ALTER TABLE mc_module_customtext
ADD FK_CTCID_CustomtextCategory INT ( 11 ) NOT NULL AFTER FK_SID,
ADD INDEX ( FK_CTCID_CustomtextCategory );

CREATE TABLE mc_module_customtext_category (
  CTCID int(11) NOT NULL,
  CTCName varchar(255) NOT NULL,
  CTCPosition int(11) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (CTCID),
  KEY (FK_SID)
);
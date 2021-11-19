/******************************************************************************/
/*                    Neues LanguageSwitch Modul                              */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`)
VALUES(44, 'allsitesmenu', 'ModuleAllSitesMenu', 0, 0, 0, 0);

/******************************************************************************/
/*       "videochannel" Modul fehlt in EDWIN DB-Install/Update Skripten       */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode) VALUES
(36, 'videochannel', 'ModuleVideoChannel', 0, 0);

/******************************************************************************/
/*                 TripleNestedLoginNav erstellen                             */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode) VALUES
(45, 'triplenestedloginnavlevel3', 'ModuleTripleNestedLoginNavLevel3', 0, 0);

/******************************************************************************/
/*   Speicherung von im Flieﬂtext verlinkten Downloads und internen Links     */
/******************************************************************************/

CREATE TABLE IF NOT EXISTS mc_contentitem_words_internallink (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  FK_CIID_Link int(11) DEFAULT NULL,
  UNIQUE KEY FK_CIID_FK_CIID_Link_UN (FK_CIID, FK_CIID_Link)
) ENGINE=MyISAM ;

CREATE TABLE IF NOT EXISTS mc_contentitem_words_filelink (
  FK_CIID int(11) NOT NULL DEFAULT '0',
  WFFile varchar(150) DEFAULT NULL,
  UNIQUE KEY FK_CIID_WFFile_UN (FK_CIID, WFFile)
) ENGINE=MyISAM ;

/******************************************************************************/
/*              Erweiterung der Blog Ebene & Blog Navigation                  */
/******************************************************************************/

ALTER TABLE mc_user
ADD UFirstname VARCHAR( 150 ) NULL,
ADD ULastname VARCHAR( 150 ) NULL;

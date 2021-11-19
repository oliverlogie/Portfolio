/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 *
 * [/INFO]
 */

/******************************************************************************/
/* Neues Modul zum Verwalten/Anzeigen von Pop-Ups                             */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive) VALUES
(67, 'popup', 'ModulePopUp', 0);

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) VALUES
(55, 'popup', 'ModulePopUpManagement', 0, 63, 0);

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
  PUUrl varchar(255) NOT NULL,
  PUDisabled tinyint(1) NOT NULL DEFAULT '0',
  PUCreateDateTime datetime NOT NULL,
  PUChangeDateTime datetime NOT NULL,
  FK_CIID int(11) DEFAULT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (PUID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE mc_module_popup_assignment (
  FK_PUID int(11) NOT NULL,
  FK_CIID int(11) NOT NULL,
  PUABeneath tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (FK_PUID,FK_CIID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE mc_module_popup_option (
  PUOID int(11) NOT NULL AUTO_INCREMENT,
  PUOKey varchar(255) DEFAULT NULL,
  PUOValue text,
  FK_PUID int(11) NOT NULL,
  PRIMARY KEY (PUOID)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
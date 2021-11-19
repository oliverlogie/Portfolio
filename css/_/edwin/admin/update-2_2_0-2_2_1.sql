/******************************************************************************/
/*                    Benutzerrechte am Backend erweitern                     */
/******************************************************************************/

CREATE TABLE mc_user_rights_submodules (
  FK_UID int(11) NOT NULL default '0',
  URMModuleShortname varchar(50) default NULL,
  URMSubmodules varchar(250) default NULL,
  PRIMARY KEY(FK_UID, URMModuleShortname)
) ENGINE=MyISAM;
/******************************************************************************/
/*                      CoreContent Verknüpfungen                             */
/******************************************************************************/

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`) VALUES
(29, 'structurelinks', 'ModuleStructureLinks', 0, 0, 0);

CREATE TABLE `mc_structurelink` (
  SLID int(11) AUTO_INCREMENT,
  FK_CIID int(11) NOT NULL,
  FK_CIID_Link int(11) DEFAULT NULL,
  PRIMARY KEY (SLID),
  KEY FK_CIID (FK_CIID),
  UNIQUE KEY FK_CIID_Link (FK_CIID_Link)
) ENGINE=MyISAM;

/**********************************************************************************/
/* ModuleSiteindexCompendium - Zusätzliche Templates + Link im Startseitenbereich */
/**********************************************************************************/

ALTER TABLE `mc_module_siteindex_compendium_area` ADD `FK_CIID` INT NULL AFTER `SAPosition`;
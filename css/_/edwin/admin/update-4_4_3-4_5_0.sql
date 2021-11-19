/******************************************************************************/
/* Admin Modul Version 1 - Informationsseiten + Logbücher                     */
/******************************************************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired) VALUES
(56, 'admin', 'ModuleAdmin', 1, 64, 0);

/******************************************************************************/
/* Performance: Verfügbarkeit von FE Modulen einschränken                     */
/******************************************************************************/

ALTER TABLE `mc_moduletype_frontend` ADD `MAvailableOnSites` VARCHAR(1000) NOT NULL DEFAULT '0' AFTER `MActiveUser`;
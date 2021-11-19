/******************************************************************************/
/*             GlobalArea - Externe Links für Bereiche und Boxen              */
/******************************************************************************/

INSERT INTO mc_moduletype_backend (MID, MShortname, MClass, MActive, MPosition, MRequired)
VALUES ('53', 'seomgmt', 'ModuleSeoManagement', '0', '0', '0');

ALTER TABLE mc_contentitem
ADD CSEOTitle varchar(255) NOT NULL DEFAULT '' AFTER CMobile,
ADD CSEODescription text NOT NULL DEFAULT '' AFTER CSEOTitle,
ADD CSEOKeywords varchar(255) NOT NULL DEFAULT '' AFTER CSEODescription;
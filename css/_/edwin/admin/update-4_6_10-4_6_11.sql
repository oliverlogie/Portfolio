/******************************************************************************/
/* ModuleTag: Tags um optionales Bild erweitern                               */
/******************************************************************************/

ALTER TABLE mc_module_tag_global
ADD COLUMN TAGNeedsImage TINYINT(1) NOT NULL DEFAULT 0 AFTER TAGContent;

ALTER TABLE mc_module_tag
ADD COLUMN TAImage1 VARCHAR(255) NOT NULL DEFAULT '' AFTER TATitle;

/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * VD / MB / MultimediaSideboxen: Ab dieser Version gibt es einen neuen ISSUU
 * Viewer. Mit dem neuen Viewer wird die Standardlayoutvorlage (pix/issuu/...)
 * nicht mehr verwendet und wurde somit auch entfernt.
 * In den Templates ContentItemVD.tpl, ContentItemMB.tpl und
 * ModuleMultimediaSidebox.tpl muss, wenn in Verwendung, der Embedded Issuu Viewer
 * aktualisiert werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/*            ContentItemQP - 10 Bilder im allgemeinen Bereich                */
/******************************************************************************/

ALTER TABLE mc_contentitem_qp
ADD QPImage4 VARCHAR( 150 ) NOT NULL AFTER QPImage3,
ADD QPImage5 VARCHAR( 150 ) NOT NULL AFTER QPImage4,
ADD QPImage6 VARCHAR( 150 ) NOT NULL AFTER QPImage5,
ADD QPImage7 VARCHAR( 150 ) NOT NULL AFTER QPImage6,
ADD QPImage8 VARCHAR( 150 ) NOT NULL AFTER QPImage7,
ADD QPImage9 VARCHAR( 150 ) NOT NULL AFTER QPImage8,
ADD QPImage10 VARCHAR( 150 ) NOT NULL AFTER QPImage9;

/******************************************************************************/
/*            Eigenes Sitemaptemplate für mobile Navigation                   */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser) VALUES
('66', 'sitemapnavmainmobile', 'ModuleSitemapNavMainMobile', '0', '0', '0', '0', '0');
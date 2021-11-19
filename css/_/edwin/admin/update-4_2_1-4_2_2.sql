/**
 * [INFO]
 *
 * Da nun auch der *hidden* Tree am Backend verwaltbar ist, müssen folgende
 * Konfigurationsvariablen für das EDWIN CMS Backend auf alle Fälle angepasst
 * werden:
 *
 * - $_CONFIG["lo_max_levels"]
 * - $_CONFIG["lo_max_items"]
 * - $_CONFIG["lo_excluded_contenttypes"]
 * - $_CONFIG["lo_allow_imageboxes_at_level"]
 * - $_CONFIG["lo_allow_ip_at_level"]
 * - $_CONFIG["lo_allow_lp_at_level"]
 * - $_CONFIG["lo_allow_be_at_level"]
 *
 * [/INFO]
 */

/******************************************************************************/
/* Verwaltung des Hidden Trees                                                */
/******************************************************************************/

ALTER TABLE mc_user_rights CHANGE UScope
UScope ENUM('main','footer','hidden','login','pages','siteindex','user') CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

/* Primärschlüssel bei mc_user_rights wegen besseren Möglichkeiten in der Programmierung einführen */
ALTER TABLE mc_user_rights ADD URID INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (URID);

/******************************************************************************/
/* Release 4.2.2                                                              */
/******************************************************************************/

/* Inhaltsseiten automatisch beim Anlegen für mobile Webseiten aktivieren */
ALTER TABLE mc_contentitem CHANGE CMobile CMobile TINYINT(4) NOT NULL DEFAULT '1';
/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Wird beim Kundenprojekt die CleverReach Anbindung (Cronjob zum Syncen der
 * Clients) verwendet, dann ändert sich der Cronjob Aufruf von bisher:
 * manage_stuff.php?site=1&do=cleverreach_sync_clients
 * auf: edw_jobs.php?action=cleverreach_push_clients&site=1
 *
 * [/INFO]
 */

/******************************************************************************/
/* Neues Modul zum Kopieren von Inhalten zwischen Portalen / Websites         */
/******************************************************************************/

INSERT INTO mc_moduletype_backend
(MID, MShortname, MClass, MActive, MPosition, MRequired) VALUES
(54, 'copytosite', 'ModuleCopyToSite', 0, 62, 0);
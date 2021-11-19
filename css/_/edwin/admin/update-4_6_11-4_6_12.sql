/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
* [INFO]
*
* Aufgrund des Updates der Google PHP Api Client Bibliothek, wurde *composer* als
* Werkzeug im EDWIN CMS eingeführt.
*
* Verwendet das Kundenprojekt
* - ContentItemES_EXT02 (Youtube Video Listing)
*
* dann muss die Bibliothek über
*
* ```
* cd tps/includes
* composer install
* composer dump-autoload
* ```
*
* installiert werden.
*
* [/INFO]
*/

/******************************************************************************/
/* Log Simple: Identifier von 25 auf 100 Zeichen erhöhen                      */
/******************************************************************************/

ALTER TABLE `mc_log_simple`
CHANGE `Identifier` `Identifier` VARCHAR(100) NOT NULL DEFAULT ''
COMMENT 'Use this column to define some kind of categorization based on i.e. log origin';
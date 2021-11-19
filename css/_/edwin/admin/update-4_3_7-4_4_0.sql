/**
 * [INFO]
 *
 * Client Stammdaten wurden um ein Feld CMobilePhone mit Position 17 erweitert.
 * Wenn die Position 17 in einer Kampagne bereits verwendet wird, dann ist das
 * Mobiltelefon Feld am Backend im Lead Mgmt nicht verfügbar. Sollte es benötigt
 * werden, dann muss die Position des speziellen Kampagnenfeldes geändert werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/* Client um Mobiltelefon erweitern                                           */
/******************************************************************************/

ALTER TABLE mc_client ADD COLUMN CMobilePhone VARCHAR(50) NOT NULL DEFAULT '' AFTER CDataPrivacyConsent;
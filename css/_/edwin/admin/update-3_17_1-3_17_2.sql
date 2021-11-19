/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Wird beim Kundenprojekt das Umfragemodul verwendet, muss dieses wieder manuell
 * hergestellt werden. Die DELETE Statements unter "ModuleSurvey entfernen"
 * müssen dann nicht ausgeführt werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/* ModuleSurvey entfernen                                                     */
/******************************************************************************/
DELETE FROM mc_moduletype_backend WHERE MID = 16;
DELETE FROM mc_contenttype WHERE CTID = 26;

/******************************************************************************/
/* Altlasten von bereits entfernten Systemfunktionen entfernen                */
/******************************************************************************/
DROP TABLE IF EXISTS mc_module_3drack;
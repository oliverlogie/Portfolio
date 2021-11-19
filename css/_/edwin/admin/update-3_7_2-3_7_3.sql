
/******************************************************************************/
/* DB-Spalten für Sesion ID mit 50 Zeichen sind für manche                    */
/* Serverkonfigurationen zu kurz                                              */
/******************************************************************************/

ALTER  TABLE  mc_user  CHANGE  USID  USID VARCHAR(255) NOT NULL;
ALTER  TABLE  mc_frontend_user  CHANGE  FUSID  FUSID VARCHAR(255) NOT NULL;
ALTER  TABLE  mc_frontend_user_sessions  CHANGE  FUSSID  FUSSID VARCHAR(255) NOT NULL;
/******************************************************************************/
/* Nicht verwendete Flash Komponenten entfernen                               */
/******************************************************************************/

/* ModuleFlashElement* and ModuleFLashNav* */
DELETE FROM mc_moduletype_frontend WHERE MID = 4;
DELETE FROM mc_moduletype_frontend WHERE MID = 5;
DELETE FROM mc_moduletype_frontend WHERE MID = 6;
DELETE FROM mc_moduletype_frontend WHERE MID = 7;
DELETE FROM mc_moduletype_frontend WHERE MID = 8;
DELETE FROM mc_moduletype_frontend WHERE MID = 9;
DELETE FROM mc_moduletype_frontend WHERE MID = 10;
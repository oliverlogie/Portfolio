/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Backend
 * -------
 *
 * Boxen / Bereiche bei
 * - CA
 * - CB
 * - DL
 * - FQ
 * - QP
 * - QS
 * - TS
 * - Siteindex
 * haben nun auch einen klickbaren Label. Wenn die Backend Templates geändert
 * wurden, müssen die Änderungen möglicherweise manuell eingepflegt werden.
 *
 * Frontend
 * --------
 *
 * ContentItemEC: das Template ContentItemEC_list.tpl wurde gesplittet. Die
 * Mitarbeiterdaten, die in die LOOP geparst werden befinden sich im Template
 * ContentItemEC_list_employee.tpl. Beim Kundenprojekt muss der entsprechende
 * Teil des alten Templates in das neue Template eingefügt werden ( Achtung:
 * bei IFs / LOOPs wurde die IDs entfernt, da sie nicht mehr notwendig sind )
 *
 * ContentItemSE > Mitarbeitersuche: wenn für die Mitarbeitersuche ein eigenes
 * Template ContentItemSE_employees.tpl beim Kundenprojekt angelegt wurde, dann
 * muss dieses auf ContentItemSE_employees_item.tpl geändert werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/* ModuleMultimediaLibrary: Formulare an Multimediaboxen anhängen             */
/******************************************************************************/

ALTER TABLE mc_module_medialibrary
ADD FK_CGAID INT NOT NULL DEFAULT '0' AFTER FK_IDID,
ADD INDEX ( FK_CGAID );
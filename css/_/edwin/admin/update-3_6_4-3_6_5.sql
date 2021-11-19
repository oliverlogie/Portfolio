/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Backend:
 * - ModuleTagcloud: Es gibt jetzt Kategorien um mehrere Tagclouds anzulegen.
 *   Für die Seite 1 wird über ein SQL Statement eine Kategorie "Allgemein"
 *   angelegt, welcher auch alle bestehenden Tags der Seite 1 zugewiesen werden.
 *   Wenn es noch mehr Edwin Seiten gibt, dann muss manuell pro Seite eine neue
 *   Tagcloud Kategorie angelegt und bestehende Tags zugewiesen werden.
 *
 * Frontend:
 * - ModuleTagcloud:
 *   - Ein neues Template (ModuleTagcloud_categories) gibt nun alle Tagcloud
 *     Gruppen und deren Tags aus. Das Frontend Layout muss überprüft werden,
 *     normalerweise sollten keine Anpassungen notwendig sein.
 *   - Eine neue Konfiguration ermöglicht nun die Sichtbarkeit der Tagcloud(s)
 *     einzuschränken. Um das bish. Verhalten wieder herzustellen muss folgende
 *     Konfiguration gesetzt werden: $_CONFIG["tc_show_always"] = 1;
 *
 * [/INFO]
 */

/******************************************************************************/
/* ModuleTagcloud: Kategorien
/******************************************************************************/

CREATE TABLE mc_module_tagcloud_category (
  TCCID int(11) NOT NULL AUTO_INCREMENT,
  TCCTitle1 varchar(255) NOT NULL,
  TCCTitle2 varchar(255) NOT NULL,
  TCCTitle3 varchar(255) NOT NULL,
  TCCText1 text NOT NULL,
  TCCText2 text NOT NULL,
  TCCText3 text NOT NULL,
  TCCPosition tinyint(4) NOT NULL DEFAULT '0',
  FK_SID int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (TCCID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

INSERT INTO mc_module_tagcloud_category (TCCID, TCCTitle1, TCCPosition, FK_SID)
VALUES (1, 'Allgemein', 1, 1);

ALTER TABLE mc_module_tagcloud ADD FK_TCCID INT NOT NULL AFTER FK_CIID,
ADD INDEX ( FK_TCCID );

UPDATE mc_module_tagcloud SET FK_TCCID = 1 WHERE FK_SID = 1;
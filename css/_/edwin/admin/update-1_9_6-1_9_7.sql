/**********************/
/* Custom Site Scopes */
/**********************/

/* Tabelle mc_site_scope erstellen */
CREATE TABLE mc_site_scope (
SCID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
SCContentItem TINYINT NOT NULL,
SCDownload TINYINT NOT NULL,
FK_SID_From INT NOT NULL,
FK_SID_To INT NOT NULL,
UNIQUE KEY FK_SID_From_FK_SID_To_UN (FK_SID_From, FK_SID_To)
) ENGINE = MYISAM;

/******************************************/
/* L�schen von Usern �ber seperate Spalte */
/******************************************/

/* Spalte UDeleted zu Tabelle mc_user hinzuf�gen */
ALTER TABLE mc_user
ADD UDeleted TINYINT NOT NULL DEFAULT '0' AFTER UCountLogins;

/* Bereits gel�schte Benutzer migrieren */
UPDATE mc_user
SET UDeleted = 1
WHERE UPW = "-1";

/*******************************************************************/
/* Identifier bei internen Links in ModuleSurvey durch FK ersetzen */
/*******************************************************************/

/* Spalte FK_CIID NULL zu mc_module_survey_question hinzuf�gen */
ALTER TABLE mc_module_survey_question
ADD FK_CIID INT NULL AFTER QDeleted,
ADD KEY FK_CIID (FK_CIID);

/* Spalte FK_CIID in mc_module_survey_question mit Referenzen auf ContentItems bef�llen (lebende Links) */
UPDATE mc_module_survey_question
SET FK_CIID = (
  SELECT CIID
  FROM mc_contentitem
  WHERE CIIdentifier = QLink
  AND FK_SID = 1
);

/* Spalte QLink aus mc_module_survey_question entfernen */
ALTER TABLE mc_module_survey_question
DROP QLink;

/* Spalte FK_CIID NULL zu mc_module_survey_answer hinzuf�gen */
ALTER TABLE mc_module_survey_answer
ADD FK_CIID INT NULL AFTER ADeleted,
ADD KEY FK_CIID (FK_CIID);

/* Spalte FK_CIID in mc_module_survey_answer mit Referenzen auf ContentItems bef�llen (lebende Links) */
UPDATE mc_module_survey_answer
SET FK_CIID = (
  SELECT CIID
  FROM mc_contentitem
  WHERE CIIdentifier = ALink
  AND FK_SID = 1
);

/* Spalte ALink aus mc_module_survey_answer entfernen */
ALTER TABLE mc_module_survey_answer
DROP ALink;

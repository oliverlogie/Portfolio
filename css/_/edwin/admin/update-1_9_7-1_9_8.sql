/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
* [INFO]
* Die Seiten, auf denen das Modul NewsTicker angezeigt werden soll, sollten 
* zuerst gesichert und dann ins CONFIG �bertragen werden.
* 
* Der Name der erstellten Root-Knoten in der Tabelle mc_contentitem sollte 
* �berpr�ft werden. Es wurde der Default-Wert "Home" von den Language-Files 
* �bernommen.
* 
* Die Position von fr�heren SPECIAL_TOP ContentItems, die in den 
* Navigationslevel 0 �bernommen wurden, sollte �berpr�ft werden.
*
* Im den FE Templates sollte bei Verwendung von QS kontrolliert werden, 
* ob die Source Variablen enthalten sind (QSource wurde entfernt).
* [/INFO]
*/

/**********************/
/* Newsticker-Anzeige */
/**********************/

/* TShowOnPages aus Tabelle mc_module_newsticker entfernen (k�nftig �ber CONFIG) */
ALTER TABLE mc_module_newsticker
DROP TShowOnPages;

/*********************************************/
/* Root-Knoten zu Main Navigation hinzuf�gen */
/*********************************************/

/* FK_CTID NULL erlauben */
ALTER TABLE mc_contentitem
CHANGE FK_CTID FK_CTID INT(11) NULL;

/* Pro Site einen Root-Knoten f�r den Main Tree anlegen */
INSERT INTO mc_contentitem (CIIdentifier, CTitle, CPosition, CType, FK_CTID, FK_SID, FK_CIID)
SELECT '', 'Home', 0, 0, NULL, SID, NULL
FROM mc_site;
/* Den "Main Root"-Knoten f�r jede Site in einer tempor�ren Tabelle zwischenspeichern 
   (MySQL Workaround, weil das UPDATE-Target nicht gleichzeitig in einer Subquery als FROM auftauchen darf) */
CREATE TEMPORARY TABLE mc_contentitem_roots AS
SELECT CIID, FK_SID
FROM mc_contentitem
WHERE CType = 0 /* ROOT */
AND CIIdentifier = '';
/* Den "Main Root"-Knoten als Parent-Knoten der Level0-Navigation zuweisen */
UPDATE mc_contentitem a
SET FK_CIID = (
  SELECT CIID
  FROM mc_contentitem_roots b
  WHERE b.FK_SID = a.FK_SID
)
WHERE FK_CIID IS NULL
AND (
  CType != 0       /* ROOT */
  AND CType != 2   /* SPECIAL_RIGHT */
  AND CType != 10  /* HIDDEN */
  AND CType != 100 /* SPECIAL_TOP */
);

/****************************************/
/* Sperren von ContentItems erm�glichen */
/****************************************/

/* Tabelle mc_contentitem um CPositionLocked und CContentLocked erweitern */
ALTER TABLE mc_contentitem
ADD CPositionLocked TINYINT(1) NOT NULL DEFAULT 0 AFTER CChangeDateTime,
ADD CContentLocked TINYINT(1) NOT NULL DEFAULT 0 AFTER CPositionLocked;

/******************************************************************/
/* SPECIAL_TOP ContentItems in den Navigationslevel 0 verschieben */
/******************************************************************/

/* Den "Main Root"-Knoten f�r jede Site in einer tempor�ren Tabelle zwischenspeichern 
   (MySQL Workaround, weil das UPDATE-Target nicht gleichzeitig in einer Subquery als FROM auftauchen darf) */
DROP TEMPORARY TABLE IF EXISTS mc_contentitem_roots;
CREATE TEMPORARY TABLE mc_contentitem_roots AS
SELECT CIID, FK_SID
FROM mc_contentitem
WHERE CType = 0 /* ROOT */
AND CIIdentifier = '';
/* Parent bei SPECIAL_TOP ContentItems neu zuweisen */
UPDATE mc_contentitem a
SET FK_CIID = (
      SELECT CIID
      FROM mc_contentitem_roots b
      WHERE b.FK_SID = a.FK_SID
    ),
    CType = 1, /* NORMAL */
    CPositionLocked = 1,
    CContentLocked = 1
WHERE CType = 100; /* SPECIAL_TOP */

/****************************/
/* Mehrere Navigationsb�ume */
/****************************/

/* NULL in CIIdentifier erlauben */
ALTER TABLE mc_contentitem
CHANGE CIIdentifier CIIdentifier VARCHAR(150) NULL;

/* Spalte CTree hinzuf�gen */
ALTER TABLE mc_contentitem
ADD CTree ENUM('main', 'footer', 'hidden') NOT NULL DEFAULT 'main' AFTER FK_CIID;

/* Pro Site einen Root-Knoten f�r den Footer Tree anlegen */
INSERT INTO mc_contentitem (CIIdentifier, CTitle, CPosition, CType, FK_CTID, FK_SID, FK_CIID, CTree)
SELECT NULL, '(Footer)', 0, 0, NULL, SID, NULL, 'footer'
FROM mc_site;

/* Pro Site einen Root-Knoten f�r den Hidden Tree anlegen */
INSERT INTO mc_contentitem (CIIdentifier, CTitle, CPosition, CType, FK_CTID, FK_SID, FK_CIID, CTree)
SELECT NULL, '(Hidden)', 0, 0, NULL, SID, NULL, 'hidden'
FROM mc_site;

/* Die Root-Knoten f�r jede Site in einer tempor�ren Tabelle zwischenspeichern 
   (MySQL Workaround, weil das UPDATE-Target nicht gleichzeitig in einer Subquery als FROM auftauchen darf) */
DROP TEMPORARY TABLE IF EXISTS mc_contentitem_roots;
CREATE TEMPORARY TABLE mc_contentitem_roots AS
SELECT CIID, FK_SID, CTree
FROM mc_contentitem
WHERE CType = 0; /* ROOT */
/* Parent bei SPECIAL_RIGHT ContentItems neu zuweisen */
UPDATE mc_contentitem a
SET FK_CIID = (
      SELECT CIID
      FROM mc_contentitem_roots b
      WHERE b.FK_SID = a.FK_SID
      AND b.CTree = 'footer'
    ),
    CTree = 'footer',
    CType = 1 /* NORMAL */
WHERE CType = 2; /* SPECIAL_RIGHT */
/* Parent bei HIDDEN ContentItems neu zuweisen */
UPDATE mc_contentitem a
SET FK_CIID = (
      SELECT CIID
      FROM mc_contentitem_roots b
      WHERE b.FK_SID = a.FK_SID
      AND b.CTree = 'hidden'
    ),
    CTree = 'hidden',
    CType = 1 /* NORMAL */
WHERE CType = 10; /* HIDDEN */

/* Unique Index f�r CIIdentifier und FK_SID anlegen */
ALTER TABLE mc_contentitem
DROP INDEX CIIdentifier,
ADD UNIQUE INDEX FK_SID_CIIdentifier_UN (FK_SID, CIIdentifier);

/********************************************/
/* Positionen aller ContentItems neu ordnen */
/********************************************/

/* Hilfstabelle mc_contentitem_positions mit fortlaufender ID anlegen */
CREATE TEMPORARY TABLE mc_contentitem_positions (
  ID INT NOT NULL AUTO_INCREMENT,
  CIID INT NOT NULL,
  FK_CIID INT NOT NULL,
  PRIMARY KEY (ID)
);
/* Hilfstabelle mc_contentitem_positions bef�llen */
INSERT INTO mc_contentitem_positions (CIID, FK_CIID)
SELECT CIID, FK_CIID
FROM mc_contentitem ci
WHERE FK_CIID IS NOT NULL
ORDER BY FK_CIID, CPosition, CIID;
/* Hilfstabelle mc_contentitem_positions_min anlegen und bef�llen (tempor�re Tabelle darf aufgrund von MySQL-Problem nur einmal pro Abfrage verwendet werden) */
CREATE TEMPORARY TABLE mc_contentitem_positions_min
AS SELECT FK_CIID, MIN(ID) AS MIN_ID
FROM mc_contentitem_positions
GROUP BY FK_CIID;
/* Spalte CPosition bef�llen */
UPDATE mc_contentitem ci
JOIN mc_contentitem_positions cip ON ci.CIID = cip.CIID
SET CPosition = 1 + ID - (
  SELECT MIN_ID
  FROM mc_contentitem_positions_min cipm
  WHERE cipm.FK_CIID = cip.FK_CIID
);

/* UNIQUE KEY hinzuf�gen */
ALTER TABLE mc_contentitem
ADD UNIQUE KEY FK_CIID_CPosition_UN (FK_CIID, CPosition);

/********************************************/
/* Redundante Daten aus Datenbank entfernen */
/********************************************/

/* Spalte mc_contentitem_words.WTotalCount entfernen */
ALTER TABLE mc_contentitem_words
DROP WTotalCount;

/* Spalte mc_module_survey_total.STCount entfernen */
ALTER TABLE mc_module_survey_total
DROP STCount;

/* Spalte mc_module_survey_answer_total.SATCount entfernen */
ALTER TABLE mc_module_survey_answer_total
DROP SATCount; 

/* Spalte mc_site.SUrl entfernen */
ALTER TABLE mc_site
DROP SUrl;

/* Spalte mc_contentitem.CIIdentifierSub entfernen */
ALTER TABLE mc_contentitem
DROP CIIdentifierSub; 

/**************************************/
/* Source bei ContentItemQS entfernen */
/**************************************/

/* Spalte mc_contentitem_qs.CIIdentifierSub entfernen */
ALTER TABLE mc_contentitem_qs
DROP QSource;
/* Spalte mc_contentitem_qs_statement.QSSource entfernen */
ALTER TABLE mc_contentitem_qs_statement
DROP QSSource;

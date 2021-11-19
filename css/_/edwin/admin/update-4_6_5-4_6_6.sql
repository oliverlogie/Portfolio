/******************************************************************************/
/* Feature bzw. Grundfunktionalität für Alternativtexte zu Bildern bei        */
/* Inhaltstypen und Modulen                                                   */
/******************************************************************************/

DROP TABLE IF EXISTS mc_extended_data;
CREATE TABLE mc_extended_data (
  ID INT(11) NOT NULL AUTO_INCREMENT,
  Type VARCHAR(150) NOT NULL DEFAULT '',
  Identifier VARCHAR(150) NOT NULL DEFAULT '',
  Value TEXT NOT NULL,
  ExtendableType VARCHAR(150) NOT NULL DEFAULT '',
  ExtendableId INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (ID),
  INDEX Extendable (ExtendableType, ExtendableId),
  INDEX ExtendableType (ExtendableType),
  INDEX ExtendableId (ExtendableId)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

/******************************************************************************/
/* Inhaltstyp *CX* - Boxen Element implementieren                             */
/******************************************************************************/

ALTER TABLE mc_contentitem_cx_area_element
ADD CXAEPosition INT(11) NOT NULL DEFAULT '0' AFTER CXAEElementableType,
ADD INDEX (CXAEPosition);
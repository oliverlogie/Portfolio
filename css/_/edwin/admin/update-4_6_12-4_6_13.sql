/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
* [INFO]
*
* Verwendet das Kundenprojekt
* - die neue EDWIN CMS Caching Komponente \Core\Services\Caching
*
* dann muss die *psr/simple-cache* Bibliothek über
*
* ```
* cd tps/includes
* rm composer.lock
* composer install
* composer dump-autoload
* ```
*
* installiert werden.
*
* [/INFO]
*/

/******************************************************************************/
/* Feature: Einfacher Datenbank Cache                                         */
/******************************************************************************/

DROP TABLE IF EXISTS mc_cache_simple;
CREATE TABLE mc_cache_simple (
  ID bigint(20) NOT NULL AUTO_INCREMENT,
  DataId varchar(255) NOT NULL DEFAULT '' COMMENT 'The id of the cached dataset.',
  DataType varchar(255) NOT NULL DEFAULT '' COMMENT 'The type of cached dataset',
  Data longtext COMMENT 'The cached data.',
  ExpireDateTime datetime DEFAULT NULL COMMENT 'The datetime the dataset expires.',
  CreateDateTime datetime DEFAULT NULL,
  ChangeDateTime datetime DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY DataId (DataId),
  KEY DataType (DataType)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
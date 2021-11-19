/******************************************/
/*         IP Ebene, LP Ebene             */
/******************************************/

UPDATE mc_contenttype SET CTPosition = 5 WHERE CTID = 76;
UPDATE mc_contenttype SET CTPosition = 3 WHERE CTID = 75;

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES (77, 'ContentItemIP', 0, 2);
INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES (78, 'ContentItemLP', 0, 4);

CREATE TABLE mc_contentitem_ip (
  IID int(11) NOT NULL auto_increment,
  ITitle varchar(150) NOT NULL,
  IText text,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (IID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

UPDATE mc_contenttype SET CTClass = 'ContentItemLO' WHERE CTID = 75;

ALTER TABLE mc_contentitem ADD CImage2 VARCHAR(150) DEFAULT NULL AFTER CLockImage;
ALTER TABLE mc_contentitem ADD CLockImage2 TINYINT(1) DEFAULT 0 AFTER CImage2;
/******************************************/
/* Updates V1.6.0                         */
/******************************************/

CREATE TABLE IF NOT EXISTS mc_contentitem_po (
  PID int(11) NOT NULL auto_increment,
  PTitle varchar(150) NOT NULL,
  PText1 text,
  PText2 text,
  PText3 text,
  PImage1 varchar(150) NOT NULL,
  PImage2 varchar(150) NOT NULL,
  PImage3 varchar(150) NOT NULL,
  PImageTitles text,
  PPrice float NOT NULL default '0',
  PNumber varchar(50) NOT NULL,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (PID),
  KEY FK_CIID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_contentitem_im (
  IID int(11) NOT NULL auto_increment,
  ITitle varchar(150) NOT NULL,
  IText1 text,
  IText2 text,
  IText3 text,
  IImage1 varchar(150) NOT NULL,
  IImage2 varchar(150) NOT NULL,
  IImage3 varchar(150) NOT NULL,
  IImage4 varchar(150) NOT NULL,
  IImage5 varchar(150) NOT NULL,
  IImage6 varchar(150) NOT NULL,
  IImageTitles text,
  IObject varchar(100) default NULL,
  IPrice double default NULL,
  ILivingSpace double default NULL,
  IFloorSpace double default NULL,
  ISpecialPrice tinyint(4) NOT NULL default '0',
  IPurchaseDate date default NULL,
  IPriceOfSale double default NULL,
  IPrivateSale tinyint(4) NOT NULL default '0',
  IAgent varchar(100) default NULL,
  FK_EID int(11) NOT NULL default '0',
  FK_AVID text,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (IID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS mc_contentitem_nl (
  NLID int(11) NOT NULL auto_increment,
  NLTitle1 varchar(150) NOT NULL,
  NLTitle2 varchar(150) NOT NULL,
  NLTitle3 varchar(150) NOT NULL,
  NLText1 text,
  NLText2 text,
  NLText3 text,
  NLImage varchar(150) NOT NULL,
  FK_CIID int(11) NOT NULL default '0',
  PRIMARY KEY  (NLID),
  KEY FK_CID (FK_CIID)
) ENGINE=MyISAM;

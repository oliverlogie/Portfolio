/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 * Blogebene: Die Konfigurationsvariable 'c_be_datetime_format' konfiguriert nun
 * nicht mehr das Format des Kommentar Datums, sondern das Datum des Artikels.
 * Das Kommentar Datum kann mit 'c_be_comment_datetime_format' formatiert werden.
 * Wenn die Variable 'c_be_datetime_format' konfiguriert ist, sollte ihr Wert in
 * 'c_be_comment_datetime_format' eingetragen werden.
 *
 * Beim Inhaltstyp SC wird die Länderauswahl dieser Version standardmäßig aus der
 * Datenbank geladen. In der Tabelle mc_country befindet sich die 
 * Standardkonfiguration der Länder für Länderauswahllisten. Um weiterhin die
 * Konfiguration aus dem CONFIG File zu verwenden, muss die Variable 
 * $_CONFIG["m_countries_use_deprecated"] = true gesetzt werden. SQL Statements
 * zur Konfiguration der Länder finden sich in edwin/admin/config.sql
 * 
 * ModuleOnly muss kontrolliert werden:
 * Verhalten bei main_moduleonlyX.tpl vorher X=Position der Inhaltsseite, jetzt X=ID des Portals
 * Zusätzlich möglich main_moduleonlyX-Y.tpl  , wobei X=ID des Portals und Y=Position der Inhaltsseite
 * 
 * Die Funktion _parseRecentDownloads von class.ModuleDownloadTicker wurde generalisiert und befindet
 * sich jetzt in der class.Module.php. Dabei wird jetzt bei jedem Download ein User Permission Check
 * durchgefuehrt.
 * 
 * BE in Englisch: Im main.tpl am Backend wird nun die Variable 
 * {main_user_editor_language} ausgegeben. Sie enthält das Sprachkürzel für die
 * Initialisierung des TinyMCE Editors (z.Z. "de", "en"). Wird das main.tpl beim
 * Update nicht ersetzt, sollte diese Variable manuell eingefügt werden um bei
 * der Verwendung des BE in Englisch die korrekte Editor Sprache initialisiern
 * zu können.
 * 
 * Der uploads/ Ordner für den FE ContentItemCC befindet sich nun am FE unter files/
 * und wurde am BE vom Verzeichnis files entfernt.
 * 
 * Da die Tabelle contentitem_words_filelink eine neue Spalte (WFTextCount) fuer die Anzahl
 * an DL-Links in einer Inhaltsseite besitzt muss dieser Zaehler aktualisiert werden
 * mit dem Sript manage_stuff.php?site=X&do=spider_text_filelinks
 * 
 * [/INFO]
 */

/******************************************************************************/
/*               ContentItemQR (Kundenprojekt QGate)                          */
/******************************************************************************/

INSERT INTO `mc_contenttype` (`CTID`, `CTClass`, `CTActive`, `CTPosition`) 
VALUES(37, 'ContentItemQR', 0, 55);

/******************************************************************************/
/*                 Inhaltstyp VD mit Blätterfunktion (ISSUU.com)              */
/******************************************************************************/


CREATE TABLE IF NOT EXISTS `mc_contentitem_vd` (
  `VID` int(11) NOT NULL AUTO_INCREMENT,
  `VDocumentId` varchar(100) NOT NULL,
  `VDocumentName` varchar(50) NOT NULL,
  `VDocumentTitle` varchar(100) NOT NULL,
  `VDocumentDescription` varchar(255) NOT NULL,
  `VTitle1` varchar(150) NOT NULL,
  `VTitle2` varchar(150) NOT NULL,
  `VTitle3` varchar(150) NOT NULL,
  `VText1` text,
  `VText2` text,
  `VText3` text,
  `FK_CIID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`VID`),
  KEY `FK_CID` (`FK_CIID`)
) ENGINE=MyISAM;

INSERT INTO `mc_contenttype` (`CTID`, `CTClass`, `CTActive`, `CTPosition`) VALUES
(38, 'ContentItemVD', 0, 56);

/******************************************************************************/
/*                        Modul zur Anzeige von RSS-Feed Items                */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (`MID`, `MShortname`, `MClass`, `MActive`, `MActiveMinimalMode`, `MActiveLogin`, `MActiveLandingPages`, `MActiveUser`)
VALUES ('46', 'feedteaser', 'ModuleFeedTeaser', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                    SC - Warenkorb mit Zahlungsanbindung                    */
/******************************************************************************/

CREATE TABLE mc_contentitem_sc_order (
  SOID int(11) NOT NULL AUTO_INCREMENT,
  SOCreateDateTime datetime NOT NULL,
  SOChangeDateTime datetime NOT NULL,
  SOTotalPrice double NOT NULL,
  SOTotalTax double NOT NULL,
  SOTotalPriceWithoutTax double NOT NULL,
  SOTransactionID varchar(255) NOT NULL,
  SOTransactionNumber varchar(50) NOT NULL,
  SOTransactionNumberDay int(11) NOT NULL,
  SOTransactionStatus tinyint(4) NOT NULL DEFAULT '0',
  SOStatus tinyint(4) NOT NULL DEFAULT '0',
  SOPaymentType tinyint(4) NOT NULL DEFAULT '0',
  SOShippingCost double NOT NULL,
  SOShippingDiscount double NOT NULL,
  SOShippingInsurance double NOT NULL,
  FK_FUID int(11) NOT NULL,
  FK_CID int(11) NOT NULL,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (SOID),
  KEY FK_FUID (FK_FUID),
  KEY FK_CID (FK_CID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_sc_order_item (
  SOIID INT NOT NULL AUTO_INCREMENT,
  SOITitle varchar(150) NOT NULL,
  SOINumber varchar(50) NOT NULL,
  SOIPosition int(11) NOT NULL DEFAULT '0',
  SOIQuantity int(11) NOT NULL DEFAULT '0',
  FK_CIID int(11) NOT NULL,
  FK_SOID int(11) NOT NULL,
  PRIMARY KEY(SOIID),
  KEY FK_CIID (FK_CIID),
  KEY FK_SOID (FK_SOID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_sc_shipment_mode (
  SMID int(11) NOT NULL AUTO_INCREMENT,
  SMName varchar(150) NOT NULL,
  SMPrice float NOT NULL DEFAULT 0,
  FK_SID int(11) NOT NULL,
  PRIMARY KEY (SMID),
  KEY FK_CID (FK_SID)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_sc_shipment_mode_country (
  FK_SMID int(11) NOT NULL,
  FK_COID int(11) NOT NULL,
  KEY FK_SMID (FK_SMID),
  KEY FK_COID (FK_COID)
) ENGINE=MyISAM;

INSERT INTO `mc_moduletype_backend` (`MID`, `MShortname`, `MClass`, `MActive`, `MPosition`, `MRequired`)
VALUES ('30', 'ordermgmt', 'ModuleOrderManagement', '0', '53', '0');

CREATE TABLE mc_module_order_export (
  EID int(11) NOT NULL AUTO_INCREMENT,
  EDateTime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  FK_UID int(11) NOT NULL,
  PRIMARY KEY EID (EID),
  KEY FK_UID (FK_UID)
) ENGINE=MyISAM AUTO_INCREMENT=1;

/******************************************************************************/
/*                        SC - Länder von DB laden                            */
/******************************************************************************/

CREATE TABLE mc_country (
  COID int(11) NOT NULL AUTO_INCREMENT,
  COName varchar(150) NOT NULL,
  COSymbol varchar(10) NOT NULL,
  COPosition int(11) NOT NULL,
  COActive tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (COID)
) ENGINE=MyISAM;

INSERT INTO mc_country (COID, COName, COSymbol, COPosition, COActive) VALUES
(1, 'AUSTRIA', 'AT', 501, 1),
(2, 'GERMANY', 'DE', 502, 1),
(3, 'SWITZERLAND', 'CH', 503, 1),
(4, 'FRANCE', 'FR', 504, 1),
(5, 'ITALY', 'IT', 505, 1),
(6, 'NETHERLANDS', 'NL', 506, 1),
(7, 'POLAND', 'PL', 507, 1),
(8, 'PORTUGAL', 'PT', 508, 1),
(9, 'AFGHANISTAN', 'AF', 509, 1),
(10, 'ALBANIA', 'AL', 510, 1),
(11, 'ALGERIA', 'DZ', 511, 1),
(12, 'AMERICAN SAMOA', 'AS', 512, 1),
(13, 'ANDORRA', 'AD', 513, 1),
(14, 'ANGOLA', 'AO', 514, 1),
(15, 'ANGUILLA', 'AI', 515, 1),
(16, 'ANTARCTICA', 'AQ', 516, 1),
(17, 'ANTIGUA AND BARBUDA', 'AG', 517, 1),
(18, 'ARGENTINA', 'AR', 518, 1),
(19, 'ARMENIA', 'AM', 519, 1),
(20, 'ARUBA', 'AW', 520, 1),
(21, 'AUSTRALIA', 'AU', 521, 1),
(22, 'AZERBAIJAN', 'AZ', 522, 1),
(23, 'BAHAMAS', 'BS', 523, 1),
(24, 'BAHRAIN', 'BH', 524, 1),
(25, 'BANGLADESH', 'BD', 525, 1),
(26, 'BARBADOS', 'BB', 526, 1),
(27, 'BELARUS', 'BY', 527, 1),
(28, 'BELGIUM', 'BE', 528, 1),
(29, 'BELIZE', 'BZ', 529, 1),
(30, 'BENIN', 'BJ', 530, 1),
(31, 'BERMUDA', 'BM', 531, 1),
(32, 'BHUTAN', 'BT', 532, 1),
(33, 'BOLIVIA', 'BO', 533, 1),
(34, 'BOSNIA AND HERZEGOV.', 'BA', 534, 1),
(35, 'BOTSWANA', 'BW', 535, 1),
(36, 'BOUVET ISLAND', 'BV', 536, 1),
(37, 'BRAZIL', 'BR', 537, 1),
(38, 'BRITISH INDIAN OCEAN T.', 'IO', 538, 1),
(39, 'BRUNEI DARUSSALAM', 'BN', 539, 1),
(40, 'BULGARIA', 'BG', 540, 1),
(41, 'BURKINA FASO', 'BF', 541, 1),
(42, 'BURUNDI', 'BI', 542, 1),
(43, 'CAMBODIA', 'KH', 543, 1),
(44, 'CAMEROON', 'CM', 544, 1),
(45, 'CANADA', 'CA', 545, 1),
(46, 'CAPE VERDE', 'CV', 546, 1),
(47, 'CAYMAN ISLANDS', 'KY', 547, 1),
(48, 'CENTRAL AFRICAN REP.', 'CF', 548, 1),
(49, 'CHAD', 'TD', 549, 1),
(50, 'CHILE', 'CL', 550, 1),
(51, 'CHINA', 'CN', 551, 1),
(52, 'CHRISTMAS ISLAND', 'CX', 552, 1),
(53, 'COCOS (K.) ISLANDS', 'CC', 553, 1),
(54, 'COLOMBIA', 'CO', 554, 1),
(55, 'COMOROS', 'KM', 555, 1),
(56, 'CONGO', 'CG', 556, 1),
(57, 'CONGO, THE DEM. REP.', 'CD', 557, 1),
(58, 'COOK ISLANDS', 'CK', 558, 1),
(59, 'COSTA RICA', 'CR', 559, 1),
(60, 'COTE D''IVOIRE', 'CI', 560, 1),
(61, 'CROATIA', 'HR', 561, 1),
(62, 'CUBA', 'CU', 562, 1),
(63, 'CYPRUS', 'CY', 563, 1),
(64, 'CZECH REPUBLIC', 'CZ', 564, 1),
(65, 'DENMARK', 'DK', 565, 1),
(66, 'DJIBOUTI', 'DJ', 566, 1),
(67, 'DOMINICA', 'DM', 567, 1),
(68, 'DOMINICAN REPUBLIC', 'DO', 568, 1),
(69, 'ECUADOR', 'EC', 569, 1),
(70, 'EGYPT', 'EG', 570, 1),
(71, 'EL SALVADOR', 'SV', 571, 1),
(72, 'EQUATORIAL GUINEA', 'GQ', 572, 1),
(73, 'ERITREA', 'ER', 573, 1),
(74, 'ESTONIA', 'EE', 574, 1),
(75, 'ETHIOPIA', 'ET', 575, 1),
(76, 'FALKLAND ISLANDS', 'FK', 576, 1),
(77, 'FAROE ISLANDS', 'FO', 577, 1),
(78, 'FIJI', 'FJ', 578, 1),
(79, 'FINLAND', 'FI', 579, 1),
(80, 'FRENCH GUIANA', 'GF', 580, 1),
(81, 'FRENCH POLYNESIA', 'PF', 581, 1),
(82, 'FRENCH SOUTHERN T.', 'TF', 582, 1),
(83, 'GABON', 'GA', 583, 1),
(84, 'GAMBIA', 'GM', 584, 1),
(85, 'GEORGIA', 'GE', 585, 1),
(86, 'GHANA', 'GH', 586, 1),
(87, 'GIBRALTAR', 'GI', 587, 1),
(88, 'GREECE', 'GR', 588, 1),
(89, 'GREENLAND', 'GL', 589, 1),
(90, 'GRENADA', 'GD', 590, 1),
(91, 'GUADELOUPE', 'GP', 591, 1),
(92, 'GUAM', 'GU', 592, 1),
(93, 'GUATEMALA', 'GT', 593, 1),
(94, 'GUERNSEY', 'GG', 594, 1),
(95, 'GUINEA', 'GN', 595, 1),
(96, 'GUINEA', 'GW', 596, 1),
(97, 'GUYANA', 'GY', 597, 1),
(98, 'HAITI', 'HT', 598, 1),
(99, 'HEARD ISLAND', 'HM', 599, 1),
(100, 'HOLY SEE', 'VA', 600, 1),
(101, 'HONDURAS', 'HN', 601, 1),
(102, 'HONG KONG', 'HK', 602, 1),
(103, 'HUNGARY', 'HU', 603, 1),
(104, 'ICELAND', 'IS', 604, 1),
(105, 'INDIA', 'IN', 605, 1),
(106, 'INDONESIA', 'ID', 606, 1),
(107, 'IRAN, ISLAMIC REP.', 'IR', 607, 1),
(108, 'IRAQ', 'IQ', 608, 1),
(109, 'IRELAND', 'IE', 609, 1),
(110, 'ISLE OF MAN', 'IM', 610, 1),
(111, 'ISRAEL', 'IL', 611, 1),
(112, 'JAMAICA', 'JM', 612, 1),
(113, 'JAPAN', 'JP', 613, 1),
(114, 'JERSEY', 'JE', 614, 1),
(115, 'JORDAN', 'JO', 615, 1),
(116, 'KAZAKHSTAN', 'KZ', 616, 1),
(117, 'KENYA', 'KE', 617, 1),
(118, 'KIRIBATI', 'KI', 618, 1),
(119, 'KOREA, DEM. PEO. REP.', 'KP', 619, 1),
(120, 'KOREA, REPUBLIC OF', 'KR', 620, 1),
(121, 'KUWAIT', 'KW', 621, 1),
(122, 'KYRGYZSTAN', 'KG', 622, 1),
(123, 'LAO PEOPLE''S DEM. REP.', 'LA', 623, 1),
(124, 'LATVIA', 'LV', 624, 1),
(125, 'LEBANON', 'LB', 625, 1),
(126, 'LESOTHO', 'LS', 626, 1),
(127, 'LIBERIA', 'LR', 627, 1),
(128, 'LIBYAN ARAB JAM.', 'LY', 628, 1),
(129, 'LIECHTENSTEIN', 'LI', 629, 1),
(130, 'LITHUANIA', 'LT', 630, 1),
(131, 'LUXEMBOURG', 'LU', 631, 1),
(132, 'MACAO', 'MO', 632, 1),
(133, 'MACEDONIA.', 'MK', 633, 1),
(134, 'MADAGASCAR', 'MG', 634, 1),
(135, 'MALAWI', 'MW', 635, 1),
(136, 'MALAYSIA', 'MY', 636, 1),
(137, 'MALDIVES', 'MV', 637, 1),
(138, 'MALI', 'ML', 638, 1),
(139, 'MALTA', 'MT', 639, 1),
(140, 'MARSHALL ISLANDS', 'MH', 640, 1),
(141, 'MARTINIQUE', 'MQ', 641, 1),
(142, 'MAURITANIA', 'MR', 642, 1),
(143, 'MAURITIUS', 'MU', 643, 1),
(144, 'MAYOTTE', 'YT', 644, 1),
(145, 'MEXICO', 'MX', 645, 1),
(146, 'MICRONESIA', 'FM', 646, 1),
(147, 'MOLDOVA, REP.', 'MD', 647, 1),
(148, 'MONACO', 'MC', 648, 1),
(149, 'MONGOLIA', 'MN', 649, 1),
(150, 'MONTENEGRO', 'ME', 650, 1),
(151, 'MONTSERRAT', 'MS', 651, 1),
(152, 'MOROCCO', 'MA', 652, 1),
(153, 'MOZAMBIQUE', 'MZ', 653, 1),
(154, 'MYANMAR', 'MM', 654, 1),
(155, 'NAMIBIA', 'NA', 655, 1),
(156, 'NAURU', 'NR', 656, 1),
(157, 'NEPAL', 'NP', 657, 1),
(158, 'NETHERLANDS ANT.', 'AN', 658, 1),
(159, 'NEW CALEDONIA', 'NC', 659, 1),
(160, 'NEW ZEALAND', 'NZ', 660, 1),
(161, 'NICARAGUA', 'NI', 661, 1),
(162, 'NIGER', 'NE', 662, 1),
(163, 'NIGERIA', 'NG', 663, 1),
(164, 'NIUE', 'NU', 664, 1),
(165, 'NORFOLK ISLAND', 'NF', 665, 1),
(166, 'NORTHERN MARIANA ISL.', 'MP', 666, 1),
(167, 'NORWAY', 'NO', 667, 1),
(168, 'OMAN', 'OM', 668, 1),
(169, 'PAKISTAN', 'PK', 669, 1),
(170, 'PALAU', 'PW', 670, 1),
(171, 'PALESTINIAN TERR.', 'PS', 671, 1),
(172, 'PANAMA', 'PA', 672, 1),
(173, 'PAPUA NEW GUINEA', 'PG', 673, 1),
(174, 'PARAGUAY', 'PY', 674, 1),
(175, 'PERU', 'PE', 675, 1),
(176, 'PHILIPPINES', 'PH', 676, 1),
(177, 'PITCAIRN', 'PN', 677, 1),
(178, 'PUERTO RICO', 'PR', 678, 1),
(179, 'QATAR', 'QA', 679, 1),
(180, 'REUNION', 'RE', 680, 1),
(181, 'ROMANIA', 'RO', 681, 1),
(182, 'RUSSIAN FEDERATION', 'RU', 682, 1),
(183, 'RWANDA', 'RW', 683, 1),
(184, 'SAINT HELENA', 'SH', 684, 1),
(185, 'SAINT KITTS AND NEVIS', 'KN', 685, 1),
(186, 'SAINT LUCIA', 'LC', 686, 1),
(187, 'SAINT PIERRE AND MIQU.', 'PM', 687, 1),
(188, 'SAINT VINCENT', 'VC', 688, 1),
(189, 'SAMOA', 'WS', 689, 1),
(190, 'SAN MARINO', 'SM', 690, 1),
(191, 'SAO TOME AND PRINCIPE', 'ST', 691, 1),
(192, 'SAUDI ARABIA', 'SA', 692, 1),
(193, 'SENEGAL', 'SN', 693, 1),
(194, 'SERBIA', 'RS', 694, 1),
(195, 'SEYCHELLES', 'SC', 695, 1),
(196, 'SIERRA LEONE', 'SL', 696, 1),
(197, 'SINGAPORE', 'SG', 697, 1),
(198, 'SLOVAKIA', 'SK', 698, 1),
(199, 'SLOVENIA', 'SI', 699, 1),
(200, 'SOLOMON ISLANDS', 'SB', 700, 1),
(201, 'SOMALIA', 'SO', 701, 1),
(202, 'SOUTH AFRICA', 'ZA', 702, 1),
(203, 'SOUTH GEORGIA', 'GS', 703, 1),
(204, 'SPAIN', 'ES', 704, 1),
(205, 'SRI LANKA', 'LK', 705, 1),
(206, 'SUDAN', 'SD', 706, 1),
(207, 'SURINAME', 'SR', 707, 1),
(208, 'SVALBARD.', 'SJ', 708, 1),
(209, 'SWAZILAND', 'SZ', 709, 1),
(210, 'SWEDEN', 'SE', 710, 1),
(211, 'SYRIAN ARAB REP.', 'SY', 711, 1),
(212, 'TAIWAN, PROV.OF CHINA', 'TW', 712, 1),
(213, 'TAJIKISTAN', 'TJ', 713, 1),
(214, 'TANZANIA, UN. REP.', 'TZ', 714, 1),
(215, 'THAILAND', 'TH', 715, 1),
(216, 'TIMOR', 'TL', 716, 1),
(217, 'TOGO', 'TG', 717, 1),
(218, 'TOKELAU', 'TK', 718, 1),
(219, 'TONGA', 'TO', 719, 1),
(220, 'TRINIDAD AND TOBAGO', 'TT', 720, 1),
(221, 'TUNISIA', 'TN', 721, 1),
(222, 'TURKEY', 'TR', 722, 1),
(223, 'TURKMENISTAN', 'TM', 723, 1),
(224, 'TURKS', 'TC', 724, 1),
(225, 'TUVALU', 'TV', 725, 1),
(226, 'UGANDA', 'UG', 726, 1),
(227, 'UKRAINE', 'UA', 727, 1),
(228, 'UNITED ARAB EMIRATES', 'AE', 728, 1),
(229, 'UNITED KINGDOM', 'GB', 729, 1),
(230, 'UNITED STATES', 'US', 730, 1),
(231, 'UNITED STATES', 'UM', 731, 1),
(232, 'URUGUAY', 'UY', 732, 1),
(233, 'UZBEKISTAN', 'UZ', 733, 1),
(234, 'VANUATU', 'VU', 734, 1),
(235, 'VENEZUELA', 'VE', 735, 1),
(236, 'VIET NAM', 'VN', 736, 1),
(237, 'VIRGIN ISLANDS, BRIT.', 'VG', 737, 1),
(238, 'VIRGIN ISLANDS, U.S.', 'VI', 738, 1),
(239, 'WALLIS AND FUTUNA', 'WF', 739, 1),
(240, 'WESTERN SAHARA', 'EH', 740, 1),
(241, 'YEMEN', 'YE', 741, 1),
(242, 'ZAMBIA', 'ZM', 742, 1),
(243, 'ZIMBABWE', 'ZW', 743, 1);

CREATE TABLE mc_country_contenttype (
  FK_COID int(11) NOT NULL,
  FK_CTID int(11) NOT NULL,
  FK_SID int(11) NOT NULL DEFAULT '0',
  COCName varchar(150) NOT NULL,
  COCPosition int(11) NOT NULL,
  COCActive tinyint(1) NOT NULL DEFAULT '0',
  KEY FK_COID (FK_COID),
  KEY FK_CTID (FK_CTID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM;

/******************************************************************************/
/*                     Startseite für den Loginbereich                        */
/******************************************************************************/

INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES
(39, 'ContentItemCA', 0, 57);

CREATE TABLE mc_contentitem_ca (
  CAID int(11) NOT NULL AUTO_INCREMENT,
  CATitle varchar(150) NOT NULL,
  CAImage1 varchar(150) NOT NULL,
  CAImage2 varchar(150) NOT NULL,
  CAImage3 varchar(150) NOT NULL,
  CAText1 text NOT NULL,
  CAText2 text NOT NULL,
  CAText3 text NOT NULL,
  CAImageTitles text,
  CALink int(11) NOT NULL,
  FK_CIID int(11) DEFAULT NULL,
  PRIMARY KEY (CAID),
  KEY FK_CIID (FK_CIID),
  KEY CALink (CALink)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_ca_area (
  CAAID int(11) NOT NULL AUTO_INCREMENT,
  CAATitle varchar(100) NOT NULL,
  CAAText text NOT NULL,
  CAAImage varchar(100) NOT NULL,
  CAABoxType enum('large','medium','small') NOT NULL DEFAULT 'large',
  CAAPosition tinyint(4) NOT NULL,
  CAALink int(11) NOT NULL,
  FK_CIID int(11) DEFAULT NULL,
  PRIMARY KEY (CAAID),
  UNIQUE KEY CAAID_CAAPosition_UN (CAAID,CAAPosition),
  KEY FK_CIID (FK_CIID),
  KEY CAALink (CAALink)
) ENGINE=MyISAM;

CREATE TABLE mc_contentitem_ca_area_box (
  CAABID int(11) NOT NULL AUTO_INCREMENT,
  CAABTitle varchar(100) NOT NULL,
  CAABText text NOT NULL,
  CAABImage varchar(100) NOT NULL,
  CAABNoImage tinyint(4) NOT NULL DEFAULT '0',
  CAABPosition tinyint(4) NOT NULL DEFAULT '0',
  CAABPositionLocked tinyint(1) NOT NULL DEFAULT '0',
  CAABLink int(11) NOT NULL,
  FK_CAAID int(11) DEFAULT NULL,
  PRIMARY KEY (CAABID),
  UNIQUE KEY CAABID_CAABPosition_UN (CAABID,CAABPosition),
  KEY FK_CAAID (FK_CAAID),
  KEY CAABLink (CAABLink)
) ENGINE=MyISAM;

/******************************************************************************/
/*         Neues Modul "RecentImages" - neueste Bilder der Webseite           */
/******************************************************************************/

ALTER TABLE mc_contentitem_bg_image ADD BICreateDateTime DATETIME NOT NULL AFTER BIPosition;
ALTER TABLE mc_contentitem_tg_image ADD TGICreateDateTime DATETIME NOT NULL AFTER TGIPosition;
INSERT INTO `mc_moduletype_frontend` (
`MID` ,
`MShortname` ,
`MClass` ,
`MActive` ,
`MActiveMinimalMode` ,
`MActiveLogin` ,
`MActiveLandingPages` ,
`MActiveUser`
)
VALUES (
'30', 'recentimages', 'ModuleRecentImages', '0', '0', '0', '0', '0'
);

/******************************************************************************/
/*        Neues Modul "RecentContent" - neueste Inhalte der Webseite          */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (
`MID` ,
`MShortname` ,
`MClass` ,
`MActive` ,
`MActiveMinimalMode` ,
`MActiveLogin` ,
`MActiveLandingPages` ,
`MActiveUser`
)
VALUES (
'27', 'recentcontent', 'ModuleRecentContent', '0', '0', '0', '0', '0'
);

/******************************************************************************/
/*    Neues Modul "RecentBlogEntries" - neueste Blog Einträge der Webseite    */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend`  (
`MID` ,
`MShortname` ,
`MClass` ,
`MActive` ,
`MActiveMinimalMode` ,
`MActiveLogin` ,
`MActiveLandingPages` ,
`MActiveUser`
)
VALUES (
'28', 'recentblogentries', 'ModuleRecentBlogEntries', '0', '0', '0', '0', '0'
); 

/******************************************************************************/
/*                XU - Verwaltung am Backend + interne Verlinkung             */
/******************************************************************************/

ALTER TABLE `mc_contentitem_xu` ADD `XULink` INT NULL AFTER `XUUrl`;
ALTER TABLE `mc_contentitem_xu` ADD INDEX ( `XULink` );

/******************************************************************************/
/*      Neues Modul "RecentDownloads" - neueste Downloads der Webseite        */
/******************************************************************************/

INSERT INTO `mc_moduletype_frontend` (
`MID` ,
`MShortname` ,
`MClass` ,
`MActive` ,
`MActiveMinimalMode` ,
`MActiveLogin` ,
`MActiveLandingPages` ,
`MActiveUser`
)
VALUES (
'29', 'recentdownloads', 'ModuleRecentDownloads', '0', '0', '0', '0', '0'
); 

/******************************************************************************/
/*       FE-User - Speicherung des letzten Logins + Anzahl + History          */
/******************************************************************************/

ALTER TABLE `mc_frontend_user` ADD `FULastLogin` DATETIME NOT NULL ,
ADD `FUCountLogins` INT NOT NULL DEFAULT '0';
CREATE TABLE mc_frontend_user_history_login (
  FK_FUID INT(11) NOT NULL,
  FUHLDatetime DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY FK_FUID (FK_FUID)
) ENGINE=MyISAM;

/******************************************************************************/
/*           Nicht verwendete zentrale Downloads am FE ausgeben               */
/******************************************************************************/

ALTER TABLE `mc_centralfile` ADD `CFShowAlways` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `CFModified`;
ALTER TABLE `mc_contentitem_words_filelink` ADD `WFTextCount` INT( 11 ) NOT NULL DEFAULT '0' AFTER `WFFile`;

/******************************************************************************/
/*             ContentItemLogin - Benutzerprofil verwalten                    */
/******************************************************************************/

ALTER TABLE `mc_frontend_user` ADD `FUShowProfile` TINYINT( 1 ) NOT NULL DEFAULT '0';

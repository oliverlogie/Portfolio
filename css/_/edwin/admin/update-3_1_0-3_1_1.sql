/*********************/
/* WICHTIGE HINWEISE */
/*********************/

/**
 * [INFO]
 *
 * ModuleBreadcrumb: Die Templatevariable main_breadcrumb muss durch
 * main_mod_breadcrumb ersetzt und das Modul in der DB aktiviert werden, wenn in
 * Verwendung. Aenderung folgender Language Variablen:
 * m_breadcrumb_path_part    -> bu_path_part
 * m_breadcrumb_link_other   -> bu_link_other
 * m_breadcrumb_link_current -> bu_link_current
 *
 * ModuleSidebox.tpl: Die IF c_sb_cgform_link wurde in c_sb_cgform_link_available
 * umbenannt und ist nicht mehr verfügbar. ( muss im Template umbenannt werden )
 *
 * ContentItemCP: Netto + Brottusummen wurden angepasst. Wenn der ShopPlus
 * verwendet wird sollten alle E-Mail-, Warenkorb- und Rechnungstemplates 
 * überprüft und angepasst werden.
 * Bei der Erzeugung der Rechnung haben sich folgende Templatevariablen geändert:
 * - c_cp_order_total* enthält nun die Werte der Zwischensumme ohne
 *   Versandkosten / Zahlungskosten
 * - c_cp_order_sum* enthält nun Werte der Gesamtsumme inklusive aller
 *   Nebenkosten
 *
 * TinyMCE Standardkonfiguration für ModuleCustomText geändert + TinyMCE 
 * standardmäßig auf Textfeld aktiviert: Wird das Modul verwendet, sollte 
 * überprüft werden ob die Konfiguration mit der Verwendung des Modules 
 * kompatibel ist.
 *
 * [/INFO]
 */

/******************************************************************************/
/*                              ModuleBreadcrumb                              */
/******************************************************************************/

INSERT INTO mc_moduletype_frontend (MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser)
VALUES (59, 'breadcrumb', 'ModuleBreadcrumb', 0, 0, 0, 0, 0);

/******************************************************************************/
/*                        Abteilungen fuer Mitarbeiter                        */
/******************************************************************************/

CREATE TABLE IF NOT EXISTS mc_module_employee_department (
  EDID int(11) NOT NULL AUTO_INCREMENT,
  EDTitle varchar(100) NOT NULL,
  EDPosition int(11) NOT NULL,
  FK_SID int(11) DEFAULT NULL,
  PRIMARY KEY (EDID),
  KEY FK_SID (FK_SID)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

ALTER TABLE mc_module_employee ADD FK_EDID int(11) NOT NULL;
ALTER TABLE mc_module_employee ADD KEY FK_EDID (FK_EDID);

ALTER TABLE mc_contentitem_ec ADD FK_ETID int(11) NOT NULL;
ALTER TABLE mc_contentitem_ec ADD FK_EDID int(11) NOT NULL;
ALTER TABLE mc_contentitem_ec ADD KEY FK_ETID (FK_ETID);
ALTER TABLE mc_contentitem_ec ADD KEY FK_EDID (FK_EDID);

/******************************************************************************/
/*       CP - Steuer der Versandkosten fehlt in Steuer der Gesamtsumme        */
/******************************************************************************/

ALTER TABLE mc_contentitem_cp_order 
ADD CPOSubTotalPrice DOUBLE NOT NULL DEFAULT '0' AFTER CPOTotalPriceWithoutTax ,
ADD CPOSubTotalTax DOUBLE NOT NULL DEFAULT '0' AFTER CPOSubTotalPrice ,
ADD CPOSubTotalPriceWithoutTax DOUBLE NOT NULL DEFAULT '0' AFTER CPOSubTotalTax,
ADD CPOShippingCostWithoutTax DOUBLE NOT NULL DEFAULT '0' AFTER CPOShippingCost,
ADD CPOPaymentCostWithoutTax DOUBLE NOT NULL DEFAULT '0' AFTER CPOPaymentCost; 

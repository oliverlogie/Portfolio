/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Achtung: Es kann sein, dass das Löschen des INDEX für CID auf der Tabelle
 *          mc_client fehlschlägt, wenn der INDEX beim Kundenprojekt nicht
 *          existiert. Das kann einfach ignoriert werden.
 *
 * Bei allen Leadmanagement Formularen muss im Formulartemplate die
 * Templatevariable {c_fo_antispam_fields} eingebaut werden ( siehe
 * Standardtemplate templates/modules/ModuleForm.tpl ) damit bestehende Formulare
 * durch den neu hinzugefügten Antispamschutz imme noch funktionieren.
 *
 * In den URLs wurden die Unterstriche durch Bindestriche ersetzt. Die
 * Methode ResourceNameGenerator::directory liefert nun standardmäßig immer
 * Pfade mit Bindestriche zurück. Wenn von einer projektspezifischen Erweiterung
 * weiterhin der Unterstrich benötigt wird: ResourceNameGenerator::directory($path, '_')
 *
 * Durch die Überarbeitung des Newsletter-Empfänger Systems mit optionalem
 * Double-Opt-In Feature wurde das CNewsletter Flag zum Indikator, dass der
 * Kunde einen Newsletter gewünscht hat. Ob er Newsletter-Empfänger ist, wird in
 * CNewsletterConfirmedRecipient gespeichert. Bei Kundenspezifischen
 * Entwicklungen müssen Skripte, die auf Newsletterempfänger zugreifen, geändert
 * werden.
 *
 * Startseitenboxen (large) können nun bis zu 3 Titel, 3 Texte und 3 Bilder haben,
 * wobei die Texte 2 & 3 nicht als Kurztexte behandelt werden, sondern wie ganz
 * normale Inhaltstextfelder. Am Backend müssen durch die entsprechenden
 * Anpassungen im custom_config.css wieder die gewünschten Felder eingeblendet
 * werden.
 *
 * [/INFO]
 */

/******************************************************************************/
/* SEO: URLs (Bindestrich statt Unterstrich)                                  */
/******************************************************************************/

update mc_contentitem SET CIIdentifier = REPLACE(CIIdentifier, '_', '-');
update mc_module_medialibrary_category SET MCIdentifier = REPLACE(MCIdentifier, '_', '-');

/******************************************************************************/
/* Kampagnen und Datenfelder Positionstyp von tinyint auf int ändern          */
/******************************************************************************/

ALTER TABLE mc_campaign CHANGE CGPosition CGPosition INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE mc_campaign_data CHANGE CGDPosition CGDPosition INT( 11 ) NOT NULL DEFAULT '0';

/******************************************************************************/
/* mc_client um Seiten ID erweitern                                           */
/******************************************************************************/

ALTER TABLE mc_client ADD COLUMN FK_SID INT(11) NOT NULL AFTER FK_UID, ADD INDEX(FK_SID);

/******************************************************************************/
/* Client Datensätze um Titel vorgestellt + Titel nachgestellt erweitern      */
/******************************************************************************/

ALTER TABLE mc_client CHANGE CTitle CTitlePre VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE mc_client ADD COLUMN CTitlePost VARCHAR(50) NOT NULL DEFAULT '' AFTER CNewsletter;

/******************************************************************************/
/* Newsletteranmeldung mit Double-Opt-In                                      */
/******************************************************************************/

ALTER TABLE mc_client ADD COLUMN CNewsletterOptInToken VARCHAR(255) NOT NULL DEFAULT '' AFTER CTitlePost;
ALTER TABLE mc_client ADD COLUMN CNewsletterOptInSuccessDateTime DATETIME NOT NULL AFTER CNewsletterOptInToken;
ALTER TABLE mc_client ADD COLUMN CNewsletterConfirmedRecipient BOOL NOT NULL DEFAULT 0 AFTER CNewsletterOptInSuccessDateTime;
/* Set the new flag, that indicates if the client is a confirmed recipient */
UPDATE mc_client SET CNewsletterConfirmedRecipient = CNewsletter;

/******************************************************************************/
/* Startseitenboxen Elemente auf 3x Titel, 3x Bild und 3x Text erweitern      */
/******************************************************************************/

ALTER TABLE mc_module_siteindex_compendium_area_box CHANGE SBTitle SBTitle1 VARCHAR( 100 ) NOT NULL;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD COLUMN SBTitle2 VARCHAR( 100 ) NOT NULL AFTER SBTitle1;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD COLUMN SBTitle3 VARCHAR( 100 ) NOT NULL AFTER SBTitle2;
ALTER TABLE mc_module_siteindex_compendium_area_box CHANGE SBText SBText1 TEXT NOT NULL;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD COLUMN SBText2 TEXT NOT NULL AFTER SBText1;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD COLUMN SBText3 TEXT NOT NULL AFTER SBText2;
ALTER TABLE mc_module_siteindex_compendium_area_box CHANGE SBImage SBImage1 VARCHAR( 100 ) NOT NULL;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD COLUMN SBImage2 VARCHAR( 100 ) NOT NULL AFTER SBImage1;
ALTER TABLE mc_module_siteindex_compendium_area_box ADD COLUMN SBImage3 VARCHAR( 100 ) NOT NULL AFTER SBImage2;

/******************************************************************************/
/* Release 3.19.4 erstellen                                                   */
/******************************************************************************/

/* Sinnvollen INDEX für FK_FUID hinzufügen */
ALTER TABLE mc_client ADD INDEX FK_UID (FK_UID);

/* Überflüssigen INDEX entfernen, da CID ohnehin PRIMARY KEY ist */
ALTER TABLE mc_client DROP INDEX CID;

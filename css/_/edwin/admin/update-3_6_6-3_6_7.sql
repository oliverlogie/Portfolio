/*********************/
/* WICHTIGE HINWEISE */
/*********************/
/**
 * [INFO]
 *
 * Nach den SQL Statements muss update-3_6_6-3_6_7.php aufgerufen werden.
 *
 * Folgende Inhaltstypen und Module wurden vom System entfernt:
 * - ContentItemDU
 * - ContentItemFD, ModuleFastAppointments
 * - ContentItemIM, ModuleRealEstate
 * - ContentItemBO, ModuleBooking
 * - ModuleVideoChannel
 * - ContentItemCC
 *
 * ES MÜSSEN DIEJENIGEN SQL STATEMENTS AUSGEFÜHRT WERDEN, WO INHALTSTYP / MODUL
 * NICHT IN VERWENDUNG IST. ALLE ANDEREN DÜRFEN NICHT AUSGEFÜHRT WERDEN!
 *
 * DER PROGRAMMCODE FÜR DAS JEWEILIGE FEATURE MUSS MANUELL WIEDERHERGESTELLT
 * WERDEN.
 *
 * -----------------------------------------------------------------------------
 *
 * Es wurde Class Autoloading implementiert. Beim Update von bestehenden Projekten
 * müssen kundenspezifische
 *
 * 1. Inhaltstypen ( FE + BE )
 * 2. Module ( FE / BE )
 * 3. Zahlungsklassen Payment<Type> ( FE bei Shop bzw. Shop Plus )
 * 4. Custom TCPDF Klassen für Bestellbestätigungen ( FE bei Shop bzw. Shop Plus )
 *
 * unbedingt in der entsprechenden includes/classmap.php Datei eingetragen werden
 *
 *
 * -----------------------------------------------------------------------------
 *
 * Bei der Verwendung des Shop Plus muss die TCPDF Bibliothek in das neue Third
 * Party Verzeichnis unter tps/includes verschoben werden.
 *
 * Bei kundenspezifischen Modulen am Frontend muss die Methode send_response() in
 * sendResponse() umbenannt werden.
 *
 * Login Bereich: bei verwendetem $_CONFIG['login_cookie_expiration'] muss
 * der Wert geändert werden. Es darf nun kein Array mehr sein, sondern nur noch
 * ein Integer Wert: http://edwin.q2e.at/wiki/index.php/Inhaltstypen#login_cookie_expiration
 *
 * NavigationPage::getContentType() wurde in NavigationPage::getContentTypeId()
 * umbenannt. Es sollte unbedingt der gesamte Quellcode nach "->getContentType()"
 * gesucht und die Vorkommnisse ( bei kundenspezifischen Erweiterungen / Skripts )
 * durch "->getContentTypeId()" ersetzt werden.
 *
 * Die Konfigurationsvariable "root_path" kann entfernt werden: die Root URL der
 * Webseite wird vom System automatisch generiert.
 *
 *
 * [/INFO]
 */

/******************************************************************************/
/* Projektspezifische & deprecated Inhaltstypen und Module aus dem  entfernen */
/******************************************************************************/

/* ContentItemDU */
DROP TABLE mc_contentitem_du;
DELETE FROM mc_contenttype WHERE CTID = 40;

/* ContentItemFD, ModuleFastAppointments */
DROP TABLE mc_contentitem_fd,
mc_contentitem_fd_administration ,
mc_contentitem_fd_bookingdata ,
mc_contentitem_fd_contactdata ,
mc_contentitem_fd_course ,
mc_contentitem_fd_drivinglesson ,
mc_contentitem_fd_examdate ,
mc_contentitem_fd_maindata ,
mc_contentitem_fd_module ,
mc_contentitem_fd_trainer_appointment ,
mc_contentitem_fd_trainer_pdf ,
mc_contentitem_fd_tutor;
DELETE FROM mc_contenttype WHERE CTID = 41;
DELETE FROM mc_moduletype_frontend WHERE MID = 53;

/* ContentItemIM, ModuleRealEstate */
DELETE FROM mc_moduletype_backend WHERE MID = 13;
DROP TABLE mc_module_realestate;
DELETE FROM mc_contenttype WHERE CTID = 18;
DROP TABLE mc_contentitem_im;

/* ContentItemBO, ModuleBooking */
DELETE FROM mc_contenttype WHERE CTID =25;
DELETE FROM mc_contenttype WHERE CTID =23;
DELETE FROM mc_moduletype_backend WHERE MID = 2;
DROP TABLE mc_contentitem_bc,
mc_contentitem_bo,
mc_module_booking_booked_room,
mc_module_booking_booking,
mc_module_booking_booking_roominfo,
mc_module_booking_location,
mc_module_booking_room,
mc_module_booking_roomtype,
mc_module_booking_special_price;

/* ModuleVideoChannel */
DELETE FROM mc_moduletype_frontend WHERE MID = 36;

/* ContentItemCC */
DELETE FROM mc_contenttype WHERE CTID = 8;
DROP TABLE mc_contentitem_cc;

/******************************************************************************/

/******************************************************************************/
/*          Mobile Buttons der Startseite auch für Inhaltsseiten              */
/******************************************************************************/
INSERT INTO mc_moduletype_frontend
(MID, MShortname, MClass, MActive, MActiveMinimalMode, MActiveLogin, MActiveLandingPages, MActiveUser) VALUES
('65', 'mobilebuttons', 'ModuleMobileButtons', '0', '0', '0', '0', '0');

/******************************************************************************/
/*                   Position für Multimediakategorie Zuweisung              */
/******************************************************************************/
ALTER TABLE mc_module_medialibrary_category_assignment
ADD MCAPosition INT( 11 ) NOT NULL,
ADD MCAID INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;

/******************************************************************************/
/*                   Logging von Benutzeraktionen verbessern                  */
/******************************************************************************/
ALTER TABLE mc_contentitem_log CHANGE LType LType VARCHAR( 255 ) NOT NULL DEFAULT '';
UPDATE mc_contentitem_log SET LType = 'created' WHERE LType = 1;
UPDATE mc_contentitem_log SET LType = 'updated' WHERE LType = 2;

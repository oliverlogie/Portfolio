/******************************************************************************/
/* AddThis (Weiterverbreitungs-) Funktionalität für Inhaltsseiten ermöglichen */
/******************************************************************************/

ALTER TABLE mc_contentitem ADD CShare TINYINT(1) NOT NULL DEFAULT 0;

/******************************************************************************/
/* Inhaltstyp, der nur ein Modul ausgibt                                      */
/******************************************************************************/

ALTER TABLE mc_moduletype_frontend ADD MActiveMinimalMode TINYINT(1) NOT NULL DEFAULT 0;
INSERT INTO mc_contenttype (CTID, CTClass, CTActive, CTPosition) VALUES (31, 'ContentItemMO', 0, 50);
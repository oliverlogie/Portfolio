/******************************************************************************/
/* Veranstaltungsbuchungsystem vom System entfernen                           */
/******************************************************************************/

DROP TABLE
mc_contentitem_eb,
mc_module_em_booking,
mc_module_em_booking_data,
mc_module_em_booking_log,
mc_module_em_display_group,
mc_module_em_event,
mc_module_em_event_download,
mc_module_em_event_participance,
mc_module_em_event_participance_log,
mc_module_em_event_type,
mc_module_em_level,
mc_module_em_newslettergroup,
mc_module_em_participant,
mc_module_em_participant_event_type,
mc_module_em_participant_log,
mc_module_em_participant_newslettergroup;

DELETE FROM mc_moduletype_backend WHERE MID = 49;
DELETE FROM mc_contenttype WHERE CTID = 56;

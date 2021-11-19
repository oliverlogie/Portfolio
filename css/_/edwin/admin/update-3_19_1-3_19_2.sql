/******************************************************************************/
/* Tabellen des nicht mehr existierenden Umfragemodules entfernen             */
/******************************************************************************/

DROP TABLE IF EXISTS mc_module_survey;
DROP TABLE IF EXISTS mc_module_survey_answer;
DROP TABLE IF EXISTS mc_module_survey_answer_total;
DROP TABLE IF EXISTS mc_module_survey_question;
DROP TABLE IF EXISTS mc_module_survey_total;
DROP TABLE IF EXISTS mc_module_survey_users;
DROP TABLE IF EXISTS mc_module_survey_votes;
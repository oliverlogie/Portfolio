<?php

$frontendDir = dirname(dirname(__FILE__)) . '/';
include $frontendDir . 'config.php';
if (is_file($frontendDir . 'config.live.php')) {
  include $frontendDir . 'config.live.php';
}

// -----------------------------------------------------------------------------
//
// COMMON
// common configuration values
//
// -----------------------------------------------------------------------------

$_CONFIG['ci_timing_type'] = 'activated';

$_CONFIG['be_allowed_html_level1'] = '<br><b><a><ul><li><br><i><u><sub><sup><span><table><thead><tbody><tr><td><th><iframe>';

// IB / IP box images
$_CONFIG["lo_image_width"] = array(370,370); // Bildergröße Anreißerebene
$_CONFIG["lo_image_height"] = array(270,270);

// LP / IP navigation
$_CONFIG["lo_image_width2"] = array(40,100);
$_CONFIG["lo_image_height2"] = array(80,80);

// -----------------------------------------------------------------------------
//
// CONTENT_TYPES
// content type specific configuration values
//
// -----------------------------------------------------------------------------

// ContentItemBG
$_CONFIG["bg_image_width1"] = 240;
$_CONFIG["bg_image_height1"] = 180;
$_CONFIG["bg_large_image_width1"] = 600;
$_CONFIG["bg_large_image_height1"] = 450;

$_CONFIG["bg_image_width"] = array(450,800);
$_CONFIG["bg_image_height"] = 600;
$_CONFIG["bg_large_image_width"] = $_CONFIG["bg_image_width"];
$_CONFIG["bg_large_image_height"] = $_CONFIG["bg_image_height"];
$_CONFIG["bg_th_image_width"] = 240;
$_CONFIG["bg_th_image_height"] = 180;
$_CONFIG["bg_th_image_height_fixed"] = 180;

// ContentItemCA
$_CONFIG['ca_number_of_boxes'] = array(4,8);
$_CONFIG['ca_type_of_boxes']= array('large','large');

$_CONFIG["ca_image_width"] = 2000;
$_CONFIG["ca_image_height"] = array(150,330);

$_CONFIG['ca_area_image_width'] = 240;
$_CONFIG['ca_area_image_height'] = 180;
$_CONFIG['ca_area_large_image_width'] = 600;
$_CONFIG['ca_area_large_image_height'] = 450;

$_CONFIG['ca_area1_box_image_width'] = 370;
$_CONFIG['ca_area1_box_image_height'] = 270;
$_CONFIG['ca_area1_box_large_image_width'] = 600;
$_CONFIG['ca_area1_box_large_image_height'] = 450;

$_CONFIG['ca_area2_box_image_width'] = 370;
$_CONFIG['ca_area2_box_image_height'] = 270;
$_CONFIG['ca_area2_box_large_image_width'] = 600;
$_CONFIG['ca_area2_box_large_image_height'] = 450;

// ContentItemCB
$_CONFIG["cb_number_of_boxes"]      = 6;
$_CONFIG["cb_number_of_biglinks"]   = 4;
$_CONFIG["cb_number_of_smalllinks"] = 4;

$_CONFIG["cb_image_width"] = 2000;
$_CONFIG["cb_image_height"] = array(150,330);

$_CONFIG['cb_box_image_width'] = 240;
$_CONFIG['cb_box_image_height'] = 180;
$_CONFIG['cb_box_large_image_width'] = 600;
$_CONFIG['cb_box_large_image_height'] = 450;

// ContentItemCB_Box_BigLinks
$_CONFIG["cb_box_biglink_image_width"]        = 240;
$_CONFIG["cb_box_biglink_image_height"]       = 180;
$_CONFIG["cb_box_biglink_large_image_width"]  = 600;
$_CONFIG["cb_box_biglink_large_image_height"] = 450;

// ContentItemCB_Boxes
$_CONFIG["cb_box_image_width"]        = 240;
$_CONFIG["cb_box_image_height"]       = 180;
$_CONFIG["cb_box_large_image_width"]  = 600;
$_CONFIG["cb_box_large_image_height"] = 450;

// ContentItemCM
$_CONFIG["cm_image_width"] = 2000;
$_CONFIG["cm_image_height"] = array(150,330);
$_CONFIG['cm_campaign'][0] = 1;

// ContentItemDL
$_CONFIG["dl_image_width"] = 2000;
$_CONFIG["dl_image_height"] = array(150,330);
$_CONFIG["dl_large_image_width"] = $_CONFIG["dl_image_width"];
$_CONFIG["dl_large_image_height"] = $_CONFIG["dl_image_height"];

$_CONFIG["dl_area_image_width"] = 280;
$_CONFIG["dl_area_image_height"] = 200;
$_CONFIG["dl_area_large_image_width"] = 800;
$_CONFIG["dl_area_large_image_height"] = 600;

// ContentItemFQ
$_CONFIG["fq_image_width"] = 2000;
$_CONFIG["fq_image_height"] = array(150,330);

// ContentItemIB
$_CONFIG["ib_image_width"] = 2000;
$_CONFIG["ib_image_height"] = array(150,330);

// ContentItemPB
$_CONFIG["pb_image_width"] = 2000;
$_CONFIG["pb_image_height"] = array(150,330);
$_CONFIG["pb_image_tpl_width"] = 660;
$_CONFIG["pb_large_image_width"] = $_CONFIG['pb_image_width'];
$_CONFIG["pb_large_image_height"] =  $_CONFIG['pb_image_height'];

// ContentItemQP
$_CONFIG["qp_image_width1"] = 2000;
$_CONFIG["qp_image_height1"] = array(150,330);
$_CONFIG["qp_image_tpl_width1"] = 660;
$_CONFIG["qp_large_image_width1"] = $_CONFIG['qp_image_width1'];
$_CONFIG["qp_large_image_height1"] =  $_CONFIG['qp_image_height1'];

$_CONFIG["qp_statement_image_width"] = 350;
$_CONFIG["qp_statement_image_height"] = array(230, 415);
$_CONFIG["qp_statement_large_image_width"] = 800;
$_CONFIG["qp_statement_large_image_height"] = array(600, 1070);

// ContentItemQS
$_CONFIG['qs_image_width'] = 280;
$_CONFIG['qs_image_height'] = array(200, 360);
$_CONFIG['qs_large_image_width'] = 800;
$_CONFIG['qs_large_image_height'] = array(600, 1070);

$_CONFIG["qs_image_width1"] = 2000;
$_CONFIG["qs_image_height1"] = array(150,330);
$_CONFIG["qs_image_tpl_width1"] = 660;
$_CONFIG["qs_large_image_width1"] = $_CONFIG['qs_image_width1'];
$_CONFIG["qs_large_image_height1"] =  $_CONFIG['qs_image_height1'];

$_CONFIG['qs_statement_image_width'] = 280;
$_CONFIG['qs_statement_image_height'] = array(200, 360);
$_CONFIG['qs_statement_large_image_width'] = 800;
$_CONFIG['qs_statement_large_image_height'] = array(600, 1070);

// ContentItemSE
$_CONFIG["se_image_width"] = 2000;
$_CONFIG["se_image_height"] = array(150,330);

// ContentItemTG
$_CONFIG["tg_image_width"] = array(250,770);
$_CONFIG["tg_image_height"] = 500;
$_CONFIG["tg_large_image_width"] = $_CONFIG["bg_image_width"];
$_CONFIG["tg_large_image_height"] = $_CONFIG["bg_image_height"];
$_CONFIG["tg_th_image_width"] = 200;
$_CONFIG["tg_th_image_height"] = 150;
$_CONFIG["tg_th_image_height_fixed"] = 146;

// ContentItemTI
$_CONFIG["ti_image_width1"] = 800;
$_CONFIG["ti_image_height1"] = array(600, 1070);
$_CONFIG["ti_large_image_width1"] = $_CONFIG["ti_image_width1"];
$_CONFIG["ti_large_image_height1"] = $_CONFIG["ti_image_height1"];

$_CONFIG["ti_image_width2"] = 2000;
$_CONFIG["ti_image_height2"] = array(150,330);
$_CONFIG["ti_image_tpl_width2"] = 660;
$_CONFIG["ti_large_image_width2"] = $_CONFIG['ti_image_width2'];
$_CONFIG["ti_large_image_height2"] =  $_CONFIG['ti_image_height2'];

$_CONFIG["ti_image_width3"] = 800;
$_CONFIG["ti_image_height3"] = array(600, 1070);
$_CONFIG["ti_large_image_width3"] = $_CONFIG["ti_image_width3"];
$_CONFIG["ti_large_image_height3"] = $_CONFIG["ti_image_height3"];


// -----------------------------------------------------------------------------
//
// MODULES
// module specific configuration values
//
// -----------------------------------------------------------------------------

// ModuleSidebox
$_CONFIG["sb_image_width1"] = 300;
$_CONFIG["sb_image_height1"] = 210;
$_CONFIG["sb_image_width2"] = 300;
$_CONFIG["sb_image_height2"] = 210;

// ModuleSiteindexCompendium
$_CONFIG['si_number_of_boxes'][1] = array(6,3,4);
$_CONFIG['si_type_of_boxes'][1] = array('large','large','large');
$_CONFIG["si_area1_box_image_width"] = 2000;
$_CONFIG["si_area1_box_image_height"] = 600;
$_CONFIG["si_area1_box_image_tpl_width"] = 617;
$_CONFIG["si_area2_box_image_width"] = 370;
$_CONFIG["si_area2_box_image_height"] = 270;
$_CONFIG["si_area3_box_image_width"] = 300;
$_CONFIG["si_area3_box_image_height"] = 170;
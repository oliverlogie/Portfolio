<?php

/**
 * Lang: DE
 *
 * $LastChangedDate: $
 * $LastChangedBy: $
 *
 * @package EDWIN Backend
 * @author Koppensteiner Raphael
 * @copyright (c) 2018 Q2E GmbH
 */

if (!isset($_LANG2["ad_ch"])) $_LANG2["ad_ch"] = array();

$_LANG = array_merge($_LANG, array(

  "modtop_ModuleAdminCheck" => "Compatibility check / System info",

  "ad_ch_not_found_label"           => "unavailable",
  "ad_ch_not_online_label"          => "unavailable",
  "ad_ch_obsolete_ver_label"        => "obsolete",
  "ad_ch_min_ver_label"             => "minimal version",
  "ad_ch_optimal_ver_label"         => "recommended version",
  "ad_ch_found_label"               => "available",
  "ad_ch_online_label"              => "available",
  "ad_ch_mysqlnd_label"             => "MYSQL native driver is being used",
  "ad_ch_u_max_filesize_low_label"  => "upload filesize too small",
  "ad_ch_u_max_filesize_min_label"  => "minimal upload filesize",
  "ad_ch_u_max_filesize_high_label" => "recommended upload filesize",
  "ad_ch_p_max_size_low_label"      => "POST size too small",
  "ad_ch_p_max_size_min_label"      => "minimal POST size",
  "ad_ch_p_max_size_high_label"     => "mecommended POST size",
  "ad_ch_memorylimit_low_label"     => "memory limit too small",
  "ad_ch_memorylimit_min_label"     => "minimal memory limit",
  "ad_ch_memorylimit_high_label"    => "recommended memory limit",
  "ad_ch_write_label"               => "write",
  "ad_ch_delete_label"              => "delete",
  "ad_ch_write_success_label"       => "finished with no errors!",
  "ad_ch_write_error_label"         => "finished with errors",
  "ad_ch_undetectable_label"        => "undetectable",

  "end",""));

$_LANG2["ad_ch"] = array_merge($_LANG2["ad_ch"], array(

  "ad_ch_title"         => "System requirements",
  "ad_ch_last_run"      => "Last run:",
  "ad_ch_new_run_label" => "Run check",

  "ad_ch_table_min_req"                    => "Minimum requirements",
  "ad_ch_table_min_req_web_php"            => "Web server module: PHP",
  "ad_ch_table_min_req_web_rewrite"        => "Web server module: rewrite",
  "ad_ch_table_min_req_php_version"        => "PHP: PHP-version (>= 7.0.x bis 7.2.x)",
  "ad_ch_table_min_req_php_mysqlnd"        => "PHP: MYSQL Native Driver",
  "ad_ch_table_min_req_php_curl"           => "PHP-extension: CURL",
  "ad_ch_table_min_req_php_dom"            => "PHP-extension: DOM",
  "ad_ch_table_min_req_php_gd"             => "PHP-extension: GD",
  "ad_ch_table_min_req_php_mysql"          => "PHP-extension: MYSQLi",
  "ad_ch_table_min_req_php_zip"            => "PHP-extension: ZIP",
  "ad_ch_table_min_req_php_zlib"           => "PHP-extension: ZLIB",
  "ad_ch_table_min_req_php_u_max_filesize" => "PHP-configuration: upload_max_filesize (32MB)",
  "ad_ch_table_min_req_php_p_max_size"     => "PHP-configuration: post_max_size (32MB)",
  "ad_ch_table_min_req_php_memorylimit"    => "PHP-configuration: memory_limit (128MB)",
  "ad_ch_table_min_req_php_mail"           => "PHP-mail forwarding: mail()",

  "ad_ch_table_optional"             => "Optional requirements",
  "ad_ch_table_optional_web_dirlist" => "Web server module: directory listing",
  "ad_ch_table_optional_web_caching" => "Web server module: caching",
  "ad_ch_table_optional_web_gzip"    => "Web server module: GZIP Compression",
  "ad_ch_table_optional_php_json"    => "PHP-extension: JSON",

  "ad_ch_table_write"     => "Write permissions",
  "ad_ch_table_write_err" => "Write errors:",
  "ad_ch_table_write_log" => "Write check log:",

  "ad_ch_table_server_status"      => "Q2E-server info",
  "ad_ch_table_server_status_name" => "data.q2e.at",
  "ad_ch_table_server_status_ping" => "data.q2e.at ping",

  "end",""));
<?php
  /**
   * Lang: EN
   *
   * $LastChangedDate: 2017-08-18 11:37:43 +0200 (Fr, 18 Aug 2017) $
   * $LastChangedBy: ulb $
   *
   * @package EDWIN Backend
   * @author Benjamin Ulmer
   * @copyright (c) 2011 Q2E GmbH
   */

if (!isset($_LANG2["ci"])) $_LANG2["ci"] = array();

$_LANG = array_merge($_LANG,array(

  "m_mode_name_mod_cmsindex" => "EDWIN Index",

  "ci_deledted_item_label"                 => "%s - %s (%s)",
  "ci_requirements"                        => "<h5>For working with this CMS we recommend the latest version of the following browsers:</h5><ul><li>Mozilla Firefox</li><li>Google Chrome</li><li>Internet Explorer 11 / Microsoft Edge</li><li>Safari on Mac OS X</li></ul><h5>Download browser:</h5><a href=\"http://www.mozilla-europe.org/de/firefox/\" target=\"_blank\" class=\"sl\">Download Firefox</a><br /><a href=\"https://www.google.com/chrome\" target=\"_blank\" class=\"sl\">Download Google Chrome</a>",
  "ci_broken_type_internal_label"          => "Defective internal link",
  "ci_broken_type_file_label"              => "Defective download-link",
  "ci_disabled_pages_timing_future_label"  => "Beginning %s",
  "ci_disabled_pages_timing_expired_label" => "Expired on %s",
  "ci_disk_space_usage_no_limit"           => "<div class=\"navfonts padding_b_10 padding_r_22\"><div class=\"c_regmsg4 toplessbdr2 font_size_11\">Currently there are %s of disk space used.</div></div>",
  "ci_disk_space_usage"                    => "<div class=\"navfonts padding_b_10 padding_r_22\"><div class=\"c_regmsg4 toplessbdr2 font_size_11\">Currently there are %s of %s disk space used ( %s available ).</div></div>",
  "ci_disk_space_usage_exceeded"           => "<div class=\"navfonts padding_b_10 padding_r_22\"><div class=\"c_regmsg4 toplessbdr2 font_size_11\">Disk full. Currently there are %s of %s used.</div></div>",
  "ci_message_intlinks_delete_success_one" => "Internal link deleted successfully.",
  "ci_message_intlinks_delete_success_more" => "%s internal links deleted successfully.",
  "ci_message_no_disabled_pages_found" => "Currently no disabled pages exist",
  "ci_message_no_timing_pages_found" => "Currently no disabled pages exist due to the timing",
  "ci_message_no_broken_intlinks_found" => "Currently there are no defective links",
  "ci_message_no_broken_textlinks_found" => "Currently there are no defective links in the text",

  "end",""));

$_LANG2["ci"] = array_merge($_LANG2["ci"], array(

  "ci_module_label" => "EDWIN Index",
  "ci_broken_link_label" => "Linkname",
  "ci_broken_link_page_label" => "Pagename",
  "ci_broken_link_mark_label" => "Mark",
  "ci_broken_links_loading_label" => "...downloads...",
  "ci_disabled_broken_links_label" => "Disabled pages / broken links on your (the) website(s)",
  "ci_disabled_pages_label" => "Disabled pages on your (the) website(s)",
  "ci_disabled_pages_tab_label" => "Disabled pages",
  "ci_disabled_pages_timing_label" => "Temporally disabled page(s) on the website",
  "ci_disabled_pages_timing_tab_label" => "Temporally disabled pages",
  "ci_broken_intlinks_label" => "Defective internal link(s) on the website",
  "ci_broken_intlinks_mark_label" => "Mark all",
  "ci_broken_intlinks_unmark_label" => "Unmark",
  "ci_broken_intlinks_delete_label" => "Delete links",
  "ci_broken_intlinks_tab_label" => "Defective internal links",
  "ci_broken_intlinks_action_label" => "Actions",
  "ci_broken_textlinks_label" => "Defective links in the content on the (the) website(s)",
  "ci_broken_textlinks_tab_label" => "Defective links in the content",
  "ci_broken_link_info_enter_label" => "Display defective links",
  "ci_broken_link_info_page_enter_label" => "Edit link",
  "ci_broken_link_page_enter_label" => "Open page",
  "ci_broken_link_page_link_info" => "Link area of the content page",
  "ci_change_pw_label" => "Change password ",
  "ci_change_news_label" => "Change portal news / Add news",
  "ci_permitted_paths_label" => "Your entry points on the website(s)",
  "ci_site_quicklinks_label" => "Frequently used functions - speed selection",
  "ci_quicklinks_siteindex_label" => "Edit welcome page",
  "ci_quicklinks_menu_label" => "Edit menu",
  "ci_userdata_label" => "User data",
  "ci_systemdata_label" => "System login",
  "ci_info_label" => "Details of your system",
  "ci_info_browser_label" => "Browser",
  "ci_info_os_label" => "Operating system",
  "ci_info_user_agent_label" => "Details",

  "end",""));

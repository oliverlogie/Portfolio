<?php

/**
 * $LastChangedDate: $
 * $LastChangedBy:  $
 *
 * @package EDWIN Backend
 * @author Koppensteiner Raphael
 * @copyright (c) 2018 Q2E GmbH
 */
class ModuleAdminCheck extends Module
{
  protected $_prefix = 'ad_ch';

  public function show_innercontent()
  {
    return $this->_showContent();
  }

  protected function _getContentLeftLinks()
  {
    return array();
  }

  protected function _getContentActionBoxButtonsTemplate()
  {
    return 'modules/ModuleAdminCheck_action_boxes.tpl';
  }

  protected function _showContent()
  {
    global $_LANG2;

    if (isset($_POST['process']) || is_null($this->session->read('ad_ch_last_run'))) {
      $this->session->save('ad_ch_last_run', $this->_checkSystem());
    }

    $results = $this->session->read('ad_ch_last_run');
    foreach ($results as $key => $value) {
      $results[$key] = parseOutput($value);
    }

    $this->tpl->load_tpl('content', 'modules/ModuleAdminCheck.tpl');
    $this->tpl->parse_vars('content', array_merge(array(
      'ad_ch_module_action_boxes' => $this->_getContentActionBoxes(),
    ), $results, $_LANG2['ad_ch']));

    return array(
      'content'      => $this->tpl->parsereturn('content'),
      'content_left' => $this->_getContentLeft(true),
    );
  }

  /**
   * @return array
   */
  private function _checkSystem()
  {
    global $_LANG;

    $web_php            = '1' . $_LANG['ad_ch_undetectable_label'];
    $web_rewrite        = '1' . $_LANG['ad_ch_undetectable_label'];
    $web_dirlist        = '2' . $_LANG['ad_ch_undetectable_label'];
    $web_caching        = '2' . $_LANG['ad_ch_undetectable_label'];
    $web_gzip           = '2' . $_LANG['ad_ch_undetectable_label'];
    $php_version        = phpversion();
    $php_mysqlnd        = '1' . $_LANG['ad_ch_not_found_label'];
    $php_curl           = '1' . $_LANG['ad_ch_not_found_label'];
    $php_dom            = '1' . $_LANG['ad_ch_not_found_label'];
    $php_gd             = '1' . $_LANG['ad_ch_not_found_label'];
    $php_json           = '2' . $_LANG['ad_ch_not_found_label'];
    $php_mysql          = '1' . $_LANG['ad_ch_not_found_label'];
    $php_zip            = '1' . $_LANG['ad_ch_not_found_label'];
    $php_zlib           = '1' . $_LANG['ad_ch_not_found_label'];
    $php_u_max_filesize = ini_get('upload_max_filesize');
    $php_p_max_size     = ini_get('post_max_size');
    $php_memorylimit    = ini_get('memory_limit');
    $php_mail           = '1' . $_LANG['ad_ch_not_found_label'];
    $files_write_err    = 0;
    $files_msg          = '';
    $files_errdir       = '';
    $status_data_q2e    = '1' . $_LANG['ad_ch_not_online_label'];
    $ping_data_q2e      = '1' . $_LANG['ad_ch_not_online_label'];


    if (function_exists('apache_get_modules')) {
      $web_php     = '1' . $_LANG['ad_ch_not_found_label'];
      $web_rewrite = '1' . $_LANG['ad_ch_not_found_label'];
      $web_dirlist = '2' . $_LANG['ad_ch_not_found_label'];
      $web_caching = '2' . $_LANG['ad_ch_not_found_label'];
      $web_gzip    = '2' . $_LANG['ad_ch_not_found_label'];

      foreach (apache_get_modules() as $module) {
        if ($this->_startsWith($module, 'mod_php')) {
          $web_php='0' . $_LANG['ad_ch_found_label'] . ' (' . $module . ')';
        }
        if ($this->_startsWith($module, 'mod_rewrite')) {
          $web_rewrite='0' . $_LANG['ad_ch_found_label'] . ' (' . $module . ')';
        }
        if ($this->_startsWith($module, 'mod_autoindex')) {
          $web_dirlist='0' . $_LANG['ad_ch_found_label'] . ' (' . $module . ')';
        }
        if ($this->_startsWith($module, 'mod_expires')) {
          $web_caching='0' . $_LANG['ad_ch_found_label'] . ' (' . $module . ')';
        }
        if ($this->_startsWith($module, 'mod_deflate')) {
          $web_gzip='0' . $_LANG['ad_ch_found_label'] . ' (' . $module . ')';
        }
      }
    }

    if ($php_version < 7) {
      $php_version='1' . $_LANG['ad_ch_obsolete_ver_label'] . ' ('.phpversion().')';
    }
    if ($php_version == 7) {
      $php_version='3' . $_LANG['ad_ch_min_ver_label'] . ' ('.phpversion().')';
    }
    if ($php_version > 7) {
      $php_version='0' . $_LANG['ad_ch_optimal_ver_label'] . ' ('.phpversion().')';
    }

    // check for mysqlnd
    // https://stackoverflow.com/questions/1475701/how-to-know-if-mysqlnd-is-the-active-driver#1475996
    if (function_exists('mysqli_fetch_all')) {
      $php_mysqlnd = '0' . $_LANG['ad_ch_mysqlnd_label'];
    }

    foreach (get_loaded_extensions() as $directory) {
      if ($this->_startsWith($directory, 'curl')) {
        $php_curl='0' . $_LANG['ad_ch_found_label'] . ' (' . $directory . ')';
      }
      if ($this->_startsWith($directory, 'dom')) {
        $php_dom='0' . $_LANG['ad_ch_found_label'] . ' (' . $directory . ')';
      }
      if ($this->_startsWith($directory, 'gd')) {
        $php_gd='0' . $_LANG['ad_ch_found_label'] . ' (' . $directory . ')';
      }
      if ($this->_startsWith($directory, 'json')) {
        $php_json='0' . $_LANG['ad_ch_found_label'] . ' (' . $directory . ')';
      }
      if ($directory === 'mysqli') {
        $php_mysql='0' . $_LANG['ad_ch_found_label'] . ' (' . $directory . ')';
      }
      if ($this->_startsWith($directory, 'zip')) {
        $php_zip='0' . $_LANG['ad_ch_found_label'] . ' (' . $directory . ')';
      }
      if ($this->_startsWith($directory, 'zlib')) {
        $php_zlib='0' . $_LANG['ad_ch_found_label'] . ' (' . $directory . ')';
      }
    }

    if ($php_u_max_filesize < 32) {
      $php_u_max_filesize = '1' . $_LANG['ad_ch_u_max_filesize_low_label'] . ' ('.ini_get('upload_max_filesize').')';
    }
    else if ($php_u_max_filesize == 32) {
      $php_u_max_filesize = '3' . $_LANG['ad_ch_u_max_filesize_min_label'] . ' ('.ini_get('upload_max_filesize').')';
    }
    else if ($php_u_max_filesize > 32) {
      $php_u_max_filesize = '0' . $_LANG['ad_ch_u_max_filesize_high_label'] . ' ('.ini_get('upload_max_filesize').')';
    }

    if ($php_p_max_size < 32) {
      $php_p_max_size = '1' . $_LANG['ad_ch_p_max_size_low_label'] . ' ('.ini_get('post_max_size').')';
    }
    else if ($php_p_max_size == 32) {
      $php_p_max_size = '3' . $_LANG['ad_ch_p_max_size_min_label'] . ' ('.ini_get('post_max_size').')';
    }
    else if ($php_p_max_size > 32) {
      $php_p_max_size = '0' . $_LANG['ad_ch_p_max_size_high_label'] . ' ('.ini_get('post_max_size').')';
    }

    if ($php_memorylimit < 128) {
      $php_memorylimit = '1' . $_LANG['ad_ch_memorylimit_low_label'] . ' ('.ini_get('memory_limit').')';
    }
    else if ($php_memorylimit == 128) {
      $php_memorylimit = '3' . $_LANG['ad_ch_memorylimit_min_label'] . ' ('.ini_get('memory_limit').')';
    }
    if ($php_memorylimit > 128) {
      $php_memorylimit = '0' . $_LANG['ad_ch_memorylimit_high_label'] . ' ('.ini_get('memory_limit').')';
    }

    if (function_exists('mail')) {
      $php_mail = '0' . $_LANG['ad_ch_found_label'] . '';
    }

    $directories = array_merge(
      $this->_recursiveDir(base_path() . 'storage'),
      $this->_recursiveDir(base_path() . 'files'),
      $this->_recursiveDir(base_path() . 'img'),
      array(1 => base_path()) // for config.php, config.live.php, ...
    );

    foreach ($directories as $directory) {
      @$file = fopen($directory.'\\__test__.txt', 'w+');
      @fwrite($file, 'test');
      @fclose($file);
      if (!file_exists($directory.'\\__test__.txt')) {
        $files_write_err++;
        $files_errdir.='<br>' . $_LANG['ad_ch_write_label'] . ':'.substr($directory, 6);
      }
      else {
        unlink($directory.'\\__test__.txt');
        if (file_exists($directory.'\\__test__.txt')) {
          $files_write_err++;
          $files_errdir.='<br>' . $_LANG['ad_ch_delete_label'] . ':'.substr($directory, 6);
        }
      }
    }

    if ($files_write_err == 0) {
      $files_write_err = '0'.$files_write_err;
      $files_msg='0' . $_LANG['ad_ch_write_success_label'];
    }
    else if ($files_write_err == 1) {
      $files_write_err = '3'.$files_write_err;
      $files_msg='3' . $_LANG['ad_ch_write_error_label'] . ':'.$files_errdir;
    }
    else if ($files_write_err == 2) {
      $files_write_err = '2'.$files_write_err;
      $files_msg='2' . $_LANG['ad_ch_write_error_label'] . ':'.$files_errdir;
    }
    else if ($files_write_err > 2) {
      $files_write_err = '1'.$files_write_err;
      $files_msg='1' . $_LANG['ad_ch_write_error_label'] . ':'.$files_errdir;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "data.q2e.at");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($ch);
    $info = curl_getinfo($ch);
    if (curl_errno($ch)) {
      $status_data_q2e = '1' . $_LANG['ad_ch_not_online_label'] . ' ('.curl_error($ch).')';
    }
    else {
      $status_data_q2e = '0' . $_LANG['ad_ch_online_label'];
      $ping_data_q2e = $info['total_time'] * 1000;

      if ($ping_data_q2e <= 200) {
        $ping_data_q2e = '0' . $ping_data_q2e;
      }
      else if ($ping_data_q2e > 200 && $ping_data_q2e <= 400) {
        $ping_data_q2e = '3' . $ping_data_q2e;
      }
      else if ($ping_data_q2e > 400 && $ping_data_q2e <= 600) {
        $ping_data_q2e = '2' . $ping_data_q2e;
      }
      else if ($ping_data_q2e > 600) {
        $ping_data_q2e = '1' . $ping_data_q2e;
      }
    }
    curl_close($ch);

    return array(
      'ad_last_run'           => date('j.n.Y G:i:s'),
      'ad_web_php'            => $web_php,
      'ad_web_rewrite'        => $web_rewrite,
      'ad_web_dirlist'        => $web_dirlist,
      'ad_web_caching'        => $web_caching,
      'ad_web_gzip'           => $web_gzip,
      'ad_php_version'        => $php_version,
      'ad_php_mysqlnd'        => $php_mysqlnd,
      'ad_php_curl'           => $php_curl,
      'ad_php_dom'            => $php_dom,
      'ad_php_gd'             => $php_gd,
      'ad_php_json'           => $php_json,
      'ad_php_mysql'          => $php_mysql,
      'ad_php_zip'            => $php_zip,
      'ad_php_zlib'           => $php_zlib,
      'ad_php_u_max_filesize' => $php_u_max_filesize,
      'ad_php_p_max_size'     => $php_p_max_size,
      'ad_php_memorylimit'    => $php_memorylimit,
      'ad_php_mail'           => $php_mail,
      'ad_files_write_err'    => $files_write_err,
      'ad_files_msg'          => $files_msg,
      'ad_status_data_q2e'    => $status_data_q2e,
      'ad_ping_data_q2e'      => $ping_data_q2e . ' ms',
    );
  }

  private function _startsWith($haystack, $needle)
  {
    return strncmp($haystack, $needle, strlen($needle)) === 0;
  }

  private function _recursiveDir($path)
  {
    $rii   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    $files = array();
    foreach ($rii as $file) {
      if ($file->isDir()) {
        $files[] = $file->getPath();
      }
    }
    $files = array_unique($files);
    $files = array_values($files);

    return $files;
  }
}
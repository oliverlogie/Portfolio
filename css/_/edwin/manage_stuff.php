<?php

/**
 * Manage edwin backend stuff
 *
 * This script require two $_GET params to be set:
 * - site - the site id of site / portal to operate on. Note, that some actions
 *          are site independant ( nevertheless 'site' has to be specified )
 * - do   - the action to execute
 *
 * Actions:
 * - migrate_global_sidebox
 * - frontend_user_company_export
 * - frontend_user_company_import
 * - import_fu
 * - import_tags
 * - spider_text_filelinks
 * - spider_text_internallinks
 * - spider_content
 *   * spiders all sites for $_GET['site'] == 999999
 * - sync_files
 * - export_fe_user_stats
 * - import_files
 * - compress_lang
 * - export_fe_user_history_download
 * - export_fe_user_history_login
 * - files_update_filesize
 *   Outputs sql statement for updating the filesize of downloads.
 * - countries_to_db
 *   Creates an SQL insert statement for mc_country table from $_CONFIG['countries']
 *   config. Use whenever updating old EDWIN versions.
 * - countries_export
 *   use as draft for 'countries_import'
 * - countries_import
 *   required file: ./countries.csv
 * - language_files_to_utf8
 * - custom_language_files_to_utf8
 * - regenerate_all_page_identifiers
 *   regenerates all page identifiers from the given site for trees main, footer, login
 *   Attention: existing URLs in search engine results will not work anymore
 *
 * $LastChangedDate: 2020-02-28 09:34:18 +0100 (Fr., 28 Feb 2020) $
 * $LastChangedBy: jua $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2009 Q2E GmbH
 */

  include 'includes/bootstrap.php';

  if (!$_GET ["site"]){
    exit();
  }
  $site = $_GET ["site"];

  if (!$_GET ["do"]){
    $str = mb_strstr ($_SERVER["REQUEST_URI"], '?');
    $do = mb_substr($str, 1);
  }
  else $do = $_GET ["do"];

  /**
   * Global helper variable to store various information inside
   *
   * @var array
   */
  $_DATA = array();

  $tablePrefix = ConfigHelper::get('table_prefix');

  switch($do)
  {
    /**
     * Migrates global sideboxes to global areas.
     */
    case "migrate_global_sidebox":
      if (!ConfigHelper::get('gs_show_area')) {
        return '';
      }

      echo "Globale Sideboxen migrieren nach ModuleGlobalAreaManagement.<br><br>";

      $gs = ConfigHelper::get('gs_show_area');
      foreach ($gs as $siteId => $areaId) {
        $sql = " SELECT SATitle, SAText, SAImage, SABoxType, SADisabled, FK_CIID, "
             . "        FK_SID "
             . " FROM {$tablePrefix}module_siteindex_compendium_area "
             . " WHERE SAID = $areaId ";
        $area = $db->GetRow($sql);
        if ($area) {
          $sql = " SELECT MAX(GAPosition) FROM {$tablePrefix}module_global_area "
               . " WHERE FK_SID = {$area['FK_SID']} ";
          $position = $db->GetOne($sql);
          if (!$position) {
            $position = 1;
          }
          else {
            $position ++;
          }
          $sql = " INSERT INTO {$tablePrefix}module_global_area "
               . " (GATitle, GAText, GABoxType, GAPosition, GADisabled, "
               . "  FK_CIID, FK_SID) VALUES ( "
               . " '{$area['SATitle']}', '{$area['SAText']}', '{$area['SABoxType']}', "
               . " '{$position}', '{$area['SADisabled']}', '{$area['FK_CIID']}', "
               . " '{$area['FK_SID']}' ) ";
          $db->query($sql);
          $gaId = $db->insert_id();
          $areaImage = $area['SAImage'];
          if ($areaImage) {
            $timestamp = time() % 1000;
            $imgExtension = explode('.', $areaImage);
            $newImage = "img/ga-{$area['FK_SID']}-{$gaId}_{$timestamp}.{$imgExtension[1]}";
            if (is_file("../".$areaImage)){
              copy("../".$areaImage, "../".$newImage);
              chmod("../".$newImage,0644);
            }
            $sql = " UPDATE {$tablePrefix}module_global_area SET GAImage = '{$newImage}' WHERE GAID = {$gaId} ";
            $db->query($sql);
          }
          echo "Konfigurierte globale Sidebox mit der ID $areaId / Seiten-Id {$area['FK_SID']}<br>"
             . "wurde erfolgreich kopiert (ID des globalen Bereiches: $gaId)!<br>";

          $sql = " SELECT SBTitle1, SBText1, SBImage1, SBNoImage, SBPosition, "
               . "        SBPositionLocked, SBDisabled, FK_CIID "
               . " FROM {$tablePrefix}module_siteindex_compendium_area_box "
               . " WHERE FK_SAID = $areaId ";
          $boxes = $db->query($sql);
          $numberOfBoxes = $db->num_rows($boxes);
          if ($boxes) {
            while ($box = $db->fetch_row($boxes)) {

              $noText = ($box['SBText1'] == '&nbsp;') ? '1' : '0';

              $sql = " INSERT INTO {$tablePrefix}module_global_area_box "
                   . " (GABTitle, GABText, GABNoImage, GABNoText, "
                   . "  GABPosition, GABPositionLocked, GABDisabled, FK_CIID, "
                   . "  FK_GAID) VALUES ( "
                   . " '{$box['SBTitle1']}', '{$box['SBText1']}', "
                   . " '{$box['SBNoImage']}', $noText, {$box['SBPosition']}, "
                   . " '{$box['SBPositionLocked']}', '{$box['SBDisabled']}', "
                   . " '{$box['FK_CIID']}', '$gaId' ) ";
              $db->query($sql);
              $gabId = $db->insert_id();

              $boxImage = $box['SBImage1'];
              if ($boxImage) {
                $timestamp = time() % 1000;
                $imgExtension = explode('.', $boxImage);
                $newImage = "img/ga-{$area['FK_SID']}-{$gaId}-{$gabId}_{$timestamp}.{$imgExtension[1]}";
                if (is_file("../".$boxImage)){
                  copy("../".$boxImage, "../".$newImage);
                  chmod("../".$newImage,0644);
                }
                $sql = " UPDATE {$tablePrefix}module_global_area_box SET GABImage = '{$newImage}' WHERE GABID = {$gabId} ";
                $db->query($sql);
              }
            }
          }
          echo ' Global Area Konfiguration erweitern/anpassen:<br>'
             . ' $_CONFIG["ga_number_of_boxes"] = array('.$siteId.' => array('.$numberOfBoxes.'));<br>'
             . ' $_CONFIG["ga_type_of_boxes"] = array('.$siteId.' => array("'.$area['SABoxType'].'"));<br>'
             . ' Optional: Startseitenbereich mit ID '.$areaId.' ueber Backend loeschen; Startseitenkonfiguration<br>'
             . ' ueberarbeiten und ueberfluessigen Startseitenbereich ueber die DB loeschen.<br><br>';
        }
        else {
          echo "Konfigurierte globale Sidebox mit der ID $areaId wurde ignoriert, weil nicht vorhanden!<br/>";
        }
      }

      if ($db->getLogger()) {
        $o = new \Core\Widgets\BottomOfThePageLogOutput(ConfigHelper::get('DEBUG_SQL'), $db->getLogger());
        echo $o->getOutput();
      }
      break;

    /**
     * Exports a csv file containing all non-deleted companies data
     */
    case "frontend_user_company_export":
      $separator = ';';
      $newline = "\n";

      $sql = " SELECT COID, COName FROM {$tablePrefix}country ";
      $countries = $db->GetAssoc($sql);

      $labels = implode($separator, array('ID', 'NAME', 'STREET', 'POSTAL CODE', 'CITY', 'COUNTRY', 'PHONE'));
      $lines = array();

      $sql = " SELECT * "
           . " FROM {$tablePrefix}frontend_user_company "
           . " WHERE FUCDeleted = 0 "
           . " ORDER BY FUCID ASC ";
      $result = $db->query($sql);
      while ($row = $db->fetch_row($result)) {
        $line = array(
          $row['FUCID'],
          $row['FUCName'],
          $row['FUCStreet'],
          $row['FUCPostalCode'],
          $row['FUCCity'],
          isset($countries[$row['FK_CID_Country']]) ? $countries[$row['FK_CID_Country']] : '',
          $row['FUCPhone'],
        );
        $lines[] = implode($separator, $line);
      }

      $csv = '';
      $csv = $labels . $newline;
      foreach ($lines as $line) {
        $csv .= $line . $newline;
      }

      //header("Content-Type: application/vnd.ms-excel");
      //header("Content-Disposition: inline; filename=\"kundendaten_export".date("Y-m-d").".xls\"");
      header('Content-Type: text/x-csv');
      header('Content-Disposition: attachment; filename=frontend_user_companies.csv');
      header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
      header('Pragma: no-cache');
      echo $csv;

      break;

      /**
       * Import companies from a csv file ( frontend_user_companies.csv )
       *
       * @see frontend_user_company_export delivers a well formatted csv containing current
       *      data from database
       */
      case 'frontend_user_company_import':
        echo "<pre>";

        $data = getCsvFileAsArray("./frontend_user_companies.csv", ";");

        // Check first line
        // $data = fgetcsv ($fp, 100000, ";");
        if (!isset($data[0]) || !is_array($data[0]) || count($data[0]) != 7) {
        echo <<<STR
FEHLER: Die CSV Datei konnte nicht gelesen werden, oder die Spaltenanzahl der CSV Datei ist nicht korrekt.

Spalten: ID;NAME;STREET;POSTAL CODE;CITY;COUNTRY;PHONE

STR;

          exit;
        }

        unset($data[0]); // headline labels are not required any more

        echo <<<STR
/******************************************************************************/
/* IMPORT COMPANIES FROM CSV                                                  */
/******************************************************************************/


STR;

        $sql = " SELECT LOWER(COName), COID FROM {$tablePrefix}country ";
        $countries = $db->GetAssoc($sql);

        // read countries to match it later with the csv country name
        $sql = " SELECT FUCID FROM {$tablePrefix}frontend_user_company ";
        $existing = $db->GetCol($sql);
        $lastId = (int)max($existing);
        $updates = array();
        $inserts = array();

        // Process all data lines
        foreach ($data as $line) {
          $id = (int)$line[0];
          $name = trim($line[1]);
          $street = trim($line[2]);
          $zip = trim($line[3]);
          $city = trim($line[4]);
          $country = trim($line[5]);
          $phone = trim($line[6]);

          $countryId = 0;
          // for specified country, check validity
          if ($country) {
            if (isset($countries[mb_strtolower($country)])) {
              $countryId = $countries[mb_strtolower($country)];
            }

            if (!$countryId) {
              echo "Das Land '$country' fÃ¼r die Firma '$name' konnte nicht gefunden werden.\n"
                 . "Bitte gib ein gÃ¼ltiges Land an." ;
              exit;
            }
          }

          if (in_array($id, $existing)) {
             $sql = " UPDATE {$tablePrefix}frontend_user_company "
                  . " SET FUCName = '{$db->escape($name)}', "
                  . "     FUCStreet = '{$db->escape($street)}', "
                  . "     FUCPostalCode = '{$db->escape($zip)}', "
                  . "     FUCCity = '{$db->escape($city)}', "
                  . "     FK_CID_Country = '$countryId',  "
                  . "     FUCPhone = '{$db->escape($phone)}' "
                  . " WHERE FUCID = '$id' ";
            $updates[] = $sql;
          }
          else if (!$id){
            $newId = ++$lastId;
            $sql = " INSERT INTO {$tablePrefix}frontend_user_company "
                 . " ( FUCID, FUCName, FUCStreet, FUCPostalCode, FUCCity, FK_CID_Country, FUCPhone ) "
                 . " VALUES "
                 . " ($newId, '{$db->escape($name)}', '{$db->escape($street)}', '{$db->escape($zip)}', '{$db->escape($city)}', '$countryId', '{$db->escape($phone)}')";
            $inserts[] = $sql;
          }
          else {
            echo "Die Firma '$name' mit der ID '$id' konnte nicht gefunden werden.\n" .
                 "Bitte korrigiere die ID oder entferne sie, wenn diese Firma erstellt werden soll.";
            exit;
          }
        }

        echo <<<STR

/* Update existing companies */


STR;
        foreach ($updates as $update) {
          echo $update . ";\n";
        }
        echo <<<STR


/* Create new companies */


STR;
        foreach ($inserts as $insert) {
          echo $insert . ";\n";
        }
        echo "</pre>";
        break;

    case "import_fu":
      // this case imports frontend users into EDWIN of a csv file
      $file = "./frontend_users.csv";
      if (($handle = fopen($file, "r")) !== FALSE) {
        // Set the parent multidimensional array key to 0.
        $nn = 0;
        $csvarray = array();
        // do not use fgetcsv with utf-8
        while (($dataStr = fgets($handle)) !== FALSE) {
          // remove whitespaces
          $dataStr = str_replace(array("\r", "\r\n", "\n"), '', $dataStr);
          $data = explode(';', $dataStr);
          // Count the total keys in the row.
          $c = count($data);
          // Populate the multidimensional array.
          for ($x=0;$x<$c;$x++)
          {
            $csvarray[$nn][$x] = $db->escape($data[$x]);
          }
          $nn++;
        }
        // Close the File.
        fclose($handle);
        if ($csvarray) {
          // handle generated array of csv file
          $rows = count($csvarray);
          $colTitles = $csvarray[0];
          for ($row = 1; $row < $rows; $row++)
          {
            // Start at line 2; Line 1 should contain column titles
            $rowData = $csvarray[$row];
            // ---------------------
            // BEGIN: CUSTOM CONFIGS
            // ---------------------

            // generate password
            $pwd = md5($rowData[1]);

            // sort datetime value
            $dateTime = preg_split("/[;,.]+/",$rowData[9]);
            $dateTime = $dateTime[2].'-'.$dateTime[1].'-'.$dateTime[0];

            // insert frontend user
            $sql = "INSERT INTO {$tablePrefix}frontend_user "
                 . "(FUFirstname, FUPW, FUEmail, FULastname, FUCompany, FUCity, FUCountry, "
                 . " FUAddress, FUPhone, FUCreateDateTime, FUZIP, FUNewsletter) "
                 . "VALUES ('{$rowData[0]}', "
                 . "        '{$pwd}', "
                 . "        '{$rowData[2]}', "
                 . "        '{$rowData[3]}', "
                 . "        '{$rowData[4]}', "
                 . "        '{$rowData[5]}', "
                 . "        '{$rowData[6]}', "
                 . "        '{$rowData[7]}', " //address
                 . "        '{$rowData[8]}', "
                 . "        '{$dateTime}', "
                 . "        '{$rowData[10]}', "
                 . "        '{$rowData[11]}' "
                 . ")";
            $result = $db->query($sql);
            $fuid = $db->insert_id();

            // insert frontend user group rights
            $sql = "INSERT INTO {$tablePrefix}frontend_user_rights "
                 . "(FK_FUID, FK_FUGID)"
                 . "VALUES ({$fuid}, '{$rowData[12]}')";
            $db->query($sql);
            // ---------------------
            // END: CUSTOM CONFIGS
            // ---------------------
          }
          echo "Frontend users successfully imported!";
        } else {
          echo "File $file corrupt.\n";
        }

      } else {
        echo "Could not open file: $file \n";
      }
      break;

    case "import_tags":

      $globalId = (int)$db->GetOne("SELECT MAX(TAGID) FROM {$tablePrefix}module_tag_global");
      $globalPosition = (int)$db->GetOne("SELECT MAX(TAGPosition) FROM {$tablePrefix}module_tag_global WHERE FK_SID = $site");
      $tagId = (int)$db->GetOne("SELECT MAX(TAID) FROM {$tablePrefix}module_tag");

      $sql1 = " INSERT INTO {$tablePrefix}module_tag "
            . " (TAID, TATitle, TAPosition, FK_TAGID) VALUES \n";
      $sql1Values = array();

      $fp = fopen ("./input_tags.csv", "r");
      while ($data = fgetcsv ($fp, 100000, ";"))
      {
        if ($data[0])
        {
          $globalId++;
          $globalPosition++;
          $sql = " INSERT INTO {$tablePrefix}module_tag_global "
               . " (TAGID, TAGTitle, TAGPosition, FK_SID) VALUES "
               . " ({$globalId}, '{$db->escape($data[0])}', $globalPosition, $site);";
          echo $sql."\n";
          // tag position renew for new global item
          $tagPosition = 0;
        }

        $tagId++;
        $tagPosition++;
        $sql1Values[] = "($tagId, '{$db->escape($data[1])}', $tagPosition, $globalId)";
      }
      fclose($fp);

      $sql1 .= implode(",\n", $sql1Values) . ";";
      echo $sql1;

      break;

    /**
     * Image resources with the suffix "-th" within the directory img/ will be
     * regenerated based on the content-type image configuration.
     *
     * Most content types should work.
     * // TODO: Some module/content-type thumbnails are not regenerated, because of
     * special file names.
     *
     * required url $_GET parameters
     * do : regenerate_thumbnails
     * site : the site to regenerate the thumbnails from
     */
    case "regenerate_thumbnails":

      $imgPath = realpath(dirname(dirname(__FILE__))) . "/img/";
      $sites = array_flip(ConfigHelper::get('site_hosts'));
      if ($site != 999999 && !isset($sites[$site])) {
        echo "Site $site not found.";
        break;
      }

      $thumbnailPaths = glob($imgPath . "*-th.*"); // * will address all files
      $nav = Navigation::getInstance($db, $tablePrefix);
      $tpl = new Template();
      $session = new Session(ConfigHelper::get('m_session_name_backend'));
      $login = new Login($tpl, $db, $tablePrefix, $session);
      $user = $login->check();
      $action2 = '';

      // Go through all thumbnails of img/ directory.
      foreach ($thumbnailPaths as $thumbnailPath) {
        $thumbnail = basename($thumbnailPath);
        $filenameParts = explode('-', $thumbnail);
        $siteId = $filenameParts[1];

        // Ignore thumbnails of other sites.
        if (!$siteId || $siteId != $site) {
          echo "$thumbnail wurde ignoriert (Seite: $siteId).<br />";
          continue;
        }
        $prefix = $filenameParts[0];
        $ciid = $filenameParts[2];
        $suffixParts = explode('.', $filenameParts[count($filenameParts) - 1]);
        $ext = $suffixParts[1];
        $timestampParts = explode('_', $filenameParts[count($filenameParts) - 2]);

        // Do some basic checks. Allow only content-items at the moment.
        $sql = " SELECT CIID FROM {$tablePrefix}contentitem ci "
             . " WHERE CIID = '$ciid' ";
        if (!$prefix || !intval($ciid) || !is_array($suffixParts) || !$ext || !is_array($timestampParts)
           || !$db->GetCol($sql)) {
          echo "$thumbnail wurde ignoriert (Keine Inhaltsseite oder nicht unterst&uuml;tzter Bildname).<br />";
          continue;
        }

        // Build normal image name.
        $normalImageNameParts = array();
        foreach ($filenameParts as $key => $filenamePart) {
          // Do not add thumbnail suffix.
          if ($key >= (count($filenameParts)-1)) {
            break;
          }
          $normalImageNameParts[] = $filenamePart;
        }
        $normalImageName = implode('-', $normalImageNameParts) . '.' . $ext;
        $largeImageName = implode('-', $normalImageNameParts) . '-l.' . $ext;
        $normalImage = new CmsGdImage($imgPath . $normalImageName);
        $largeImage = null;
        if (file_exists($imgPath . $largeImageName)) {
          $largeImage = new CmsGdImage($imgPath . $normalImageName);
        }
        $imageNumber = $timestampParts[0];

        // Regenerate thumbnail image.
        $contentItem = ContentItem::create((int) $siteId, (int) $ciid, $tpl,
          $db, $tablePrefix,
          $action2, $user, $session,
          $nav);
        $thumbnailImage = $contentItem->storeImageCreateThumbnailImage($normalImage, $largeImage, $prefix, $imageNumber);

        if ($normalImage->getType() == IMAGETYPE_JPEG) {
          $thumbnailImage->writeJpeg($thumbnailPath, 0644);
        }
        else {
          $thumbnailImage->writePng($thumbnailPath, 0644);
        }
        echo "$thumbnailPath wurde neu generiert ({$thumbnailImage->getHeight()} x {$thumbnailImage->getWidth()}) <br/>";
      }
      break;

    // spider content text filelinks
    case "spider_text_filelinks":

      loadContentTypeSuffix();
      $fileIds = array(); // file types and ids
      $cifiles = array(); // file types and ids indexed by content item id

      $sql = " SELECT CIID FROM {$tablePrefix}contentitem ci "
           . " WHERE FK_SID = $site ";
      $ciids = $db->GetCol($sql);
      $sqlCiids = implode(',',$ciids);

      foreach ($_DATA["contenttype_suffix"] as $suffix)
      {
        // do not join with mc_contentitem here as FK_CIID will not be unique
        // any more
        $sql = " SELECT * FROM {$tablePrefix}contentitem_$suffix c_sub "
             . " WHERE FK_CIID IN ($sqlCiids) ";
        $result = $db->query($sql);

        while ($row = $db->fetch_row($result))
        {
          $ciid = 0;
          $text = '';
          foreach ($row as $col => $val)
          {
            // content item text field
            if (mb_strpos($col, 'Text') !== FALSE) {
              $text .= $val;
            }
            // content item id
            if ($col == 'FK_CIID') {
              $ciid = $val;
            }
          }
          // no texts found or invalid content item id
          if (!$text || !$ciid) {
            continue;
          }

          // Retrieve all file links (linktype and file id)
          $pattern = '#href="(edwin-file://(file|centralfile|dlfile)/(\d+))#ui';
          while (preg_match($pattern, $text, $matches))
          {
            $fileIds[$matches[2]][] = (int)$matches[3];
            // Remove the found link from $text and check again
            $text = mb_substr_replace($text, '', mb_strpos($text, $matches[1]), mb_strlen($matches[1][0]));
            // store files for content item id
            // the value contains <filetype>-<file id>
            $cifiles[$ciid][] = $matches[2]."-".$matches[3];
          }
        }
      }

      $paths = array();
      // Retrieve all file paths
      foreach ($fileIds as $type => $value) {
        $value = array_unique($value);
        // generate select statement depending on filetype
        switch ($type) {
          case 'file':
            $sql = 'SELECT FID AS ID, FFile AS File '
                 . "FROM {$tablePrefix}file "
                 . "WHERE FID IN ( " . implode(',', $value) . ") ";
            $paths[$type] = $db->GetAssoc($sql);
            break;
          case 'dlfile':
            $sql = 'SELECT DFID AS ID, DFFile  AS File '
                 . "FROM {$tablePrefix}contentitem_dl_area_file "
                 . "WHERE DFID IN ( " . implode(',', $value) . ") ";
            $paths[$type] = $db->GetAssoc($sql);
            break;
          case 'centralfile':
            $sql = 'SELECT CFID AS ID, CFFile  AS File '
                 . "FROM {$tablePrefix}centralfile "
                 . "WHERE CFID IN ( " . implode(',', $value) . ") ";
            $paths[$type] = $db->GetAssoc($sql);
            break;
          default:
            trigger_error("Unknown file type '$type'.", E_USER_WARNING);
        }
      }

      $sqlArgs = array();
      foreach ($cifiles as $ciid => $values)
      {
        // sum up files with same filename and get count
        $values = array_count_values($values);
        foreach ($values as $val => $count)
        {
          list($type, $fileId) = explode('-', $val);

          if (isset($paths[$type][$fileId])) {
            $sqlArgs[] = "( $ciid, '{$paths[$type][$fileId]}', $count)";
          }
        }
      }

      echo "#Delete from {$tablePrefix}contentitem_words_filelink " . "\n";
      echo "DELETE FROM  {$tablePrefix}contentitem_words_filelink "
          ."WHERE FK_CIID IN ($sqlCiids);" . "\n";
      if ($sqlArgs)
      {
        echo "#Insert into {$tablePrefix}contentitem_words_filelink " . "\n";
        echo "INSERT INTO {$tablePrefix}contentitem_words_filelink "
            ."(FK_CIID, WFFile, WFTextCount) VALUES " . "\n";
        $i = 1;
        $count = count($sqlArgs);
        foreach ($sqlArgs as $arg)
        {
          if ($i >= $count) { // last item
            echo "$arg;\n";
          }
          else {
            echo "$arg,\n";
          }
          $i++;
        }
      }

      break;

    // spider content text internallinks
    case "spider_text_internallinks":

      loadContentTypeSuffix();
      $intlinks = array();

      $sql = " SELECT CIID FROM {$tablePrefix}contentitem ci "
           . " WHERE FK_SID = $site ";
      $ciids = $db->GetCol($sql);
      $sqlCiids = implode(',',$ciids);

      foreach ($_DATA["contenttype_suffix"] as $suffix)
      {
        // do not join with mc_contentitem here as FK_CIID will not be unique
        // any more
        $sql = " SELECT * FROM {$tablePrefix}contentitem_$suffix c_sub "
             . " WHERE FK_CIID IN ($sqlCiids) ";
        $result = $db->query($sql);

        while ($row = $db->fetch_row($result))
        {
          $ciid = 0;
          $text = '';
          foreach ($row as $col => $val)
          {
            // content item text field
            if (mb_strpos($col, 'Text') !== FALSE) {
              $text .= $val;
            }
            // content item id
            if ($col == 'FK_CIID') {
              $ciid = $val;
            }
          }
          // no texts found or invalid content item id
          if (!$text || !$ciid) {
            continue;
          }

          // Retrieve all internal links
          $pattern = '#href="(edwin-link://internal/(\d+))"#ui';
          while (preg_match($pattern, $text, $matches))
          {
            $intlinks[$ciid][] = (int)$matches[2];
            // Remove the found link from $text and check again
            $text = mb_mb_substr_replace($text, '', mb_strpos($text, $matches[1]), mb_strlen($matches[1]));
          }
        }
      }

      echo "#Delete from {$tablePrefix}contentitem_words_internallink " . "\n";
      echo "DELETE FROM  {$tablePrefix}contentitem_words_internallink "
          ."WHERE FK_CIID IN ($sqlCiids);" . "\n";
      if ($intlinks)
      {
        echo "#Insert into {$tablePrefix}contentitem_words_internallink " . "\n";
        // generate sql
        foreach ($intlinks as $id => $links)
        {
          foreach ($links as $link) {
            $sqlLinks[] = "( $id, $link )";
          }
        }
        echo "INSERT INTO {$tablePrefix}contentitem_words_internallink "
            ."(FK_CIID, FK_CIID_Link) VALUES " . "\n";
        $i = 1;
        $count = count($sqlLinks);
        foreach ($sqlLinks as $link)
        {
          if ($i >= $count) { // last item
            echo "$link;\n";
          }
          else {
            echo "$link,\n";
          }
          $i++;
        }
      }

      break;

    /**
     * Spider content text - refresh contentitem words, filelinks and
     * internal links.
     * Required to spider utf-8 data:
     * - ContentBase::_parseForSpider must use multibyte functions and extended regular expressions.
     * - Set mb_internal_encoding and mb_regex_encoding to UTF-8
     * - MySQL query: "set names utf8"
     *
     * required url $_GET parameters
     * do : spider_content
     * site : the site to spider / if 999999 spiders all sites
     */
    case "spider_content":

      $process = (isset($_GET ['process'])) ? $_GET ['process'] : '';
      $sites = array_flip(ConfigHelper::get('site_hosts'));
      if ($site != 999999 && !isset($sites[$site])) {
        echo "Site $site not found.";
        break;
      }

      // Utf-8 header (just for information output)
      // header('Content-type: text/html; charset=utf-8');

      // REQUIRED to spider content with utf-8 data:
      // mb_internal_encoding("UTF-8");
      // mb_regex_encoding('UTF-8');
      // $db->query("set names 'utf8'");

      // Default includes
      // Load content item suffixes into a global variable
      $nav = Navigation::getInstance($db, $tablePrefix);
      $tpl = new Template();
      $session = new Session(ConfigHelper::get('m_session_name_backend'));
      $login = new Login($tpl, $db, $tablePrefix, $session);
      $user = $login->check();
      if (!$user->isValid()) {
        return;
      }
      echo '<html><body>';
      $action2 = '';
      $sql = ' SELECT CIID, CTitle, FK_SID '
           . " FROM {$tablePrefix}contentitem ci "
           . " WHERE FK_CTID > 0 "
           //. "   AND CContentLocked = 0 "
           . ($site != 999999 ? " AND FK_SID = $site " : '')
           . "   AND (CType = 1 OR CType = 90) "; // We ignore root pages
      $result = $db->query($sql);
      $_POST['process'] = 'process';
      // Create content item objects and call its spider content function
      while ($row = $db->fetch_row($result))
      {
        $contentItem = ContentItem::create((int)$row['FK_SID'], $row['CIID'], $tpl,
                                           $db, $tablePrefix,
                                           $action2, $user, $session,
                                           $nav);

        if ($contentItem != 'contentclass_notfound')
        {
          echo "Spider ".$row['CTitle']." (ID ".$row['CIID']." / Seite ".$row['FK_SID'].")<br />\n";
          $contentItem->spiderContent();
        }
        else {
          echo "<i>$contentItem contentclass_notfound</i><br />\n";
        }
      }
      echo '</body></html>';

      break;

      // update / syncronize files with files from a specified directory
      case 'sync_files':

        if (!isset($_GET['source']))
        {
          echo "Missing url parameter 'source'!";
          exit();
        }

        $source = trim($_GET['source']);

        if (!$source || !is_dir('../'.$source))
        {
          echo "Invalid url parameter 'source', specify a valid directory!";
          exit();
        }

        $targetDirName = '../files/';
        $sourceDirName = '../'.$source.'/';

        // open source directory
        $sourcedir = opendir($sourceDirName);

        if ($sourcedir === false)
          exit();

        while (false !== ( $file = readdir($sourcedir)) )
        {
          $process = false;

          // check if current file exists within the EDWIN files/ directory
          if (( $file != '.' ) && ( $file != '..' ) && file_exists($targetDirName.$file))
            $process = true;

          if ($process === false)
            continue;

          // check if the last change date of current file is younger than of
          // EDWIN file - only newer files have to be updated
          $sourceModified = filemtime($sourceDirName.$file);
          $targetModified = filemtime($targetDirName.$file);
          if (!$sourceModified || !$targetModified || $sourceModified <= $targetModified)
            $process = false;

          if ($process === false)
            continue;

          $sqlFile = 'files/'.$file;

          // Retrieve filetype and id of the current file within EDWIN CMS.
          // This query will always return one row, as filenames are unique.
          $sql = " SELECT 'centralfile' AS Type, CFID AS ID "
               . " FROM {$tablePrefix}centralfile "
               . " WHERE CFFile = '{$sqlFile}' "
               . " UNION "
               . " SELECT 'dlfile' AS Type, DFID AS ID "
               . " FROM {$tablePrefix}contentitem_dl_area_file "
               . " WHERE DFFile = '{$sqlFile}' "
               . " UNION "
               . " SELECT 'file' AS Type, FID AS ID "
               . " FROM {$tablePrefix}file "
               . " WHERE FFile = '{$sqlFile}' ";
          $row = $db->GetRow($sql);

          $type = $row['Type'];
          $id = $row['ID'];
          $sqlModified = date('Y-m-d H:i:s', $sourceModified);

          // copy current file to EDWIN files/ directory
          // if copying the file failed, continue with next file from source
          // destination
          if (copy($sourceDirName.$file, $targetDirName.$file) === false)
            continue;

          switch ($type)
          {
            case 'centralfile':

              $sql = " UPDATE {$tablePrefix}centralfile "
                   . "    SET CFModified = '$sqlModified' "
                   . " WHERE CFID = '$id' ";
              $db->query($sql);

              $sql = " UPDATE {$tablePrefix}file "
                   . "    SET FModified = '$sqlModified' "
                   . " WHERE FK_CFID = '$id' ";
              $db->query($sql);

              $sql = " UPDATE {$tablePrefix}contentitem_dl_area_file "
                   . "    SET DFModified = '$sqlModified' "
                   . " WHERE FK_CFID = '$id' ";
              $db->query($sql);

              break;

            case 'dlfile':

              $sql = " UPDATE {$tablePrefix}contentitem_dl_area_file "
                   . "    SET DFModified = '$sqlModified' "
                   . " WHERE DFID = '$id' ";
              $db->query($sql);

              break;

            case 'file':

              $sql = " UPDATE {$tablePrefix}file "
                   . "    SET FModified = '$sqlModified' "
                   . " WHERE FID = '$id' ";
              $db->query($sql);

              break;
          }

          // finally remove current file from filesystem
          unlink($sourceDirName.$file);

        }

        break;

      case 'export_fe_user_stats':

        $result = $db->query("SELECT * FROM {$tablePrefix}frontend_user WHERE FUCountLogins > 0 AND FUDeleted = 0 ORDER BY FUEmail ASC");

        //header('Content-Type: text/x-csv');
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: inline; filename=\"tmp_file_".date("Y-m-d").".xls\"");
        //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');

        echo "e-mail"."\t"."nick"."\t"."number of logins"."\t"."show profile on next login"."\t\n";

        while ($row = $db->fetch_row($result)) {
          echo $row["FUEmail"]."\t".$row["FUNick"]."\t".$row["FUCountLogins"]."\t".($row["FUShowProfile"] ? "yes" : "no")."\t\n";
        }

        $db->free_result($result);
        exit();

        break;

      /**
       * required url $_GET parameters
       *
       * site : the site the files will belong to
       * do : import_files
       * source : the directory to source files in cms root path
       * public : 0 or 1, set to 0 if created files have to be protected
       *
       * Attention: files from source directory are removed
       */
      case 'import_files':

        if (!(int)$site) {
          echo "Invalid site id $site!";
          exit(1);
        }

        if (!isset($_GET['source']))
        {
          echo "Missing url parameter 'source'!";
          exit(1);
        }

        $source = trim($_GET['source']);

        if (!$source || !is_dir('../'.$source))
        {
          echo "Invalid url parameter 'source', specify a valid directory!";
          exit(1);
        }

        $public = 1;
        if (isset($_GET['public']) && !(int)$_GET['public']) // create non-public files
          $public = 0;

        $targetDirName = '../files/';
        $sourceDirName = '../'.$source.'/';

        // open source directory
        $sourcedir = opendir($sourceDirName);

        if ($sourcedir === false)
          exit(1);

        $str1 = " INSERT INTO {$tablePrefix}centralfile "
              . " (CFTitle, CFFile, CFCreated, CFShowAlways, FK_SID) VALUES ";
        $sqlPart = array();

        while (false !== ( $file = readdir($sourcedir)) )
        {
          $filename =  ResourceNameGenerator::file($file); // remove bad characters

          // do not import files:
          // 1. '.'
          // 2. '..'
          // 3. existing within the EDWIN files/ directory
          if (( $file == '.' ) || ( $file == '..' ) || file_exists($targetDirName.$filename))
            continue;

          // copy current file to EDWIN files/ directory
          // if copying the file failed, continue with next file from source
          // destination
          if (copy($sourceDirName.$file, $targetDirName.$filename) === false)
            continue;

          $sqlFile = 'files/'.$filename;
//          $sqlFileTitle = mb_substr($filename, 0, mb_strrpos($filename , '.')); // sanitized filename without extension
          $sqlFileTitle = $file;                                            // use original filename as title

          $now = date('Y-m-d H:i:s');
          $sqlPart[] = " ('{$db->escape($sqlFileTitle)}', '{$db->escape($sqlFile)}', '$now', $public, $site) ";

          // finally remove current source file from filesystem
          unlink($sourceDirName.$file);

        }

        if ($sqlPart) {
          echo $str1."\n".implode($sqlPart, ",\n").";\n";
        }

        break;

      /**
       * required url $_GET parameters
       *
       * do : compress_lang
       */
      case 'compress_lang':

        $fileName = 'lang.compressed.php';
        $backend = 'language';
        $frontend = '../language';
        // create compressed file of each language folder in EDWIN backend
        createCompressedLangFiles($fileName, $backend, 'Backend');
        // create compressed file of each language folder in EDWIN frontend
        createCompressedLangFiles($fileName, $frontend, 'Frontend');

        break;

      case 'export_fe_user_history_download':

        $sql = " SELECT FUEmail, FUNick, FUHDFile, FUHDDatetime "
             . " FROM {$tablePrefix}frontend_user "
             . " JOIN {$tablePrefix}frontend_user_history_download "
             . "   ON FK_FUID = FUID "
             . " WHERE FUDeleted = 0 "
             . " ORDER BY FUEmail ASC, FUHDFile ASC, FUHDDatetime DESC ";
        $result = $db->query($sql);

        //header('Content-Type: text/x-csv');
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: inline; filename=\"tmp_file_".date("Y-m-d").".xls\"");
        //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');

        echo "e-mail"."\t"."nick"."\t"."file"."\t"."download date"."\t\n";

        $rootPath = root_url();
        while ($row = $db->fetch_row($result)) {
          echo $row["FUEmail"]."\t".$row["FUNick"]."\t".$rootPath.
               $row["FUHDFile"]."\t".$row["FUHDDatetime"]."\t\n";
        }

        $db->free_result($result);
        exit();

        break;

      case 'export_fe_user_history_login':

        $sql = " SELECT FUEmail, FUNick, FUHLDatetime FROM {$tablePrefix}frontend_user "
             . " JOIN {$tablePrefix}frontend_user_history_login "
             . "   ON FK_FUID = FUID "
             . " WHERE FUDeleted = 0 "
             . " ORDER BY FUEmail ASC, FUHLDatetime DESC ";
        $result = $db->query($sql);

        //header('Content-Type: text/x-csv');
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: inline; filename=\"tmp_file_".date("Y-m-d").".xls\"");
        //header('Content-Disposition: attachment; filename=kundendaten_export.csv');
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');

        echo "e-mail"."\t"."nick"."\t"."login date"."\t\n";

        while ($row = $db->fetch_row($result)) {
          echo $row["FUEmail"]."\t".$row["FUNick"]."\t".$row["FUHLDatetime"]."\t\n";
        }

        $db->free_result($result);
        exit();

        break;

      /**
       * Outputs sql statement for updating the filesize of all downloads.
       * - mc_centralfile
       * - mc_contentitem_dl_area_file
       * - mc_file
       * Ignores files where filesize() fails.
       *
       * Note, that this function ignores the 'site' url parameter and creates
       * the update statements for ALL files.
       */
      case 'files_update_filesize':

        echo <<<STR
/******************************************************************************/
/* Update filesize off all files                                              */
/******************************************************************************/

STR;
        // central files
        $sql = " SELECT CFID AS id, CFFile AS file "
             . " FROM {$tablePrefix}centralfile ";
        $result = $db->query($sql);

        while ($row = $db->fetch_row($result)) {
          $id = $row['id'];
          $file = '../' . $row['file'];
          $bytes = filesize($file);

          if ($bytes !== false) {
            echo "UPDATE {$tablePrefix}centralfile SET CFSize = $bytes WHERE CFID = $id;\n";
          }
        }

        $db->free_result($result);

        // DL area files
        $sql = " SELECT DFID AS id, DFFile AS file "
             . " FROM {$tablePrefix}contentitem_dl_area_file df "
             . " WHERE DFFile IS NOT NULL ";
        $result = $db->query($sql);

        while ($row = $db->fetch_row($result)) {
          $id = $row['id'];
          $file = '../' . $row['file'];
          $bytes = filesize($file);

          if ($bytes !== false) {
            echo "UPDATE {$tablePrefix}contentitem_dl_area_file SET DFSize = $bytes WHERE DFID = $id;\n";
          }
        }

        // files
        $sql = " SELECT FID AS id, FFile AS file "
             . " FROM {$tablePrefix}file f "
             . " WHERE FFile IS NOT NULL ";
        $result = $db->query($sql);

        while ($row = $db->fetch_row($result)) {
          $id = $row['id'];
          $file = '../' . $row['file'];
          $bytes = filesize($file);

          if ($bytes !== false) {
            echo "UPDATE {$tablePrefix}file SET FSize = $bytes WHERE FID = $id;\n";
          }
        }

        break;

      /**
       * Creates the db insert statement for country configuration from
       * $_CONFIG['countries'] array.
       */
      case 'countries_to_db':

        echo <<<STR
/******************************************************************************/
/* COUNTRIES                                                                  */
/******************************************************************************/

STR;

        $countries = ConfigHelper::get('countries');

        if (!$countries) {
          echo "There are not countries defined within \$_CONFIG['countries']\n\n";
          exit();
        }

        $pos = 1;
        $str = '';
        foreach ($countries as $id => $name) {
          $str .= "('$id', '{$db->escape($name)}', '', '$pos', 1),\n\n";
          $pos++;
        }

        $str = mb_substr($str, 0, mb_strlen($str) - 3);

        $sql = " INSERT INTO {$tablePrefix}country "
             . " (COID, COName, COSymbol, COPosition, COActive) VALUES "
             . $str . ";";

        echo $sql;
        break;

      /**
       * Export countries to a csv file, use as draft for 'countries_import'
       */
      case 'countries_export':

        $separator = ';';
        $newline = "\n";

        $labels = implode($separator, array('ID', 'NAME', 'SYMBOL', 'CODE', 'POSITION', 'ACTIVE'));
        $lines = array();

        $result = $db->query("SELECT * FROM {$tablePrefix}country ORDER BY COID ASC");
        while ($row = $db->fetch_row($result)) {
          $line = array(
            $row['COID'],
            $row['COName'],
            $row['COSymbol'],
            $row['COCode'],
            $row['COPosition'],
            $row['COActive'],
          );
          $lines[] = implode($separator, $line);
        }

        $csv = '';
        $csv = $labels . $newline;
        foreach ($lines as $line) {
          $csv .= $line . $newline;
        }

        //header("Content-Type: application/vnd.ms-excel");
        //header("Content-Disposition: inline; filename=\"kundendaten_export".date("Y-m-d").".xls\"");
        header('Content-Type: text/x-csv');
        header('Content-Disposition: attachment; filename=countries.csv');
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: no-cache');
        echo $csv;

        break;

      /**
       * Import countries from a csv file
       *
       * @see countries_export delivers a well formatted csv containing current
       *      data from database
       */
      case 'countries_import':
        echo "<pre>";

        $data = getCsvFileAsArray("./countries.csv", ";");

        // Check first line
        // $data = fgetcsv ($fp, 100000, ";");
        if (!isset($data[0]) || !is_array($data[0]) || count($data[0]) != 6) {
        echo <<<STR
FEHLER: Die CSV Datei konnte nicht gelesen werden, oder die Spaltenanzahl der CSV Datei ist nicht korrekt.

Spalten: ID;NAME;SYMBOL;CODE;POSITION;ACTIVE

Verwende 'countries_export' um dir eine g&uuml;ltige CSV Datei mit den derzeitigen L&auml;ndern zu erhalten.

STR;

          exit;
        }

        unset($data[0]); // headline labels are not required any more

        echo <<<STR
/******************************************************************************/
/* IMPORT COUNTRIES FROM CSV                                                  */
/******************************************************************************/


STR;

        // read countries to match it later with the csv country name
        $sql = " SELECT COID FROM {$tablePrefix}country ";
        $existing = $db->GetCol($sql);
        $lastId = (int)max($existing);
        $updates = array();
        $inserts = array();

        // Process all data lines
        foreach ($data as $line) {
          $id = (int)$line[0];
          $name = trim($line[1]);
          $symbol = trim($line[2]);
          $code = (int)$line[3];
          $position = (int)$line[4];
          $active = (int)$line[5];

          if (in_array($id, $existing)) {
             $sql = " UPDATE {$tablePrefix}country "
                  . " SET COName = '{$db->escape($name)}', "
                  . "     COSymbol = '{$db->escape($symbol)}', "
                  . "     COCode = '$code', "
                  . "     COPosition = '$position', "
                  . "     COActive = '$active' "
                  . " WHERE COID = '$id' ";
            $updates[] = $sql;
          }
          else if (!$id){
            $newId = ++$lastId;
            $sql = " INSERT INTO {$tablePrefix}country "
                 . " ( COID, COName, COSymbol, COCode, COPosition, COActive ) "
                 . " VALUES "
                 . " ($newId, '{$db->escape($name)}', '{$db->escape($symbol)}', '$code', '$position', '$active')";
            $inserts[] = $sql;
          }
          else {
            echo "Das Land mit der ID '$id' und dem NAMEN '$name' konnte nicht gefunden werden.\n" .
                 "Bitte korrigiere die ID oder entferne sie, wenn dieses Land erstellt werden soll.";
            exit;
          }
        }

        echo <<<STR

/* Update existing countries */


STR;
        foreach ($updates as $update) {
          echo $update . ";\n";
        }
        echo <<<STR


/* Create new countries */


STR;
        foreach ($inserts as $insert) {
          echo $insert . ";\n";
        }
        echo "</pre>";
        break;

      case 'language_files_to_utf8':

        echo '<pre>';
        echo "Converting frontend language files:\n\n";
        runConvertToUtf8('../language', array());
        echo "Converting backend language files:\n\n";
        runConvertToUtf8('language', array());
        echo '</pre>';
        break;

      case 'custom_language_files_to_utf8':

        echo '<pre>';
        echo "Converting frontend language files:\n\n";
        runConvertToUtf8('../language', array('../language/german-default',
                                              '../language/english-default'));
        echo "Converting backend language files:\n\n";
        runConvertToUtf8('language', array('language/german-default',
                                           'language/english-default'));
        echo '</pre>';
        break;
    case 'regenerate_all_page_identifiers':
      manageStuffRegenerateAllPageIdentifiers();
      break;
  }

  /**
   * Load content type suffix from database. ($_DATA["contenttype_suffix"])
   */
  function loadContentTypeSuffix()
  {
    global $db, $_DATA, $tablePrefix;

    // content types have been loaded before
    if (isset($_DATA["contenttype_suffix"]) && !empty($_DATA["contenttype_suffix"])) {
      return;
    }

    // load content types
    $sql = "SELECT * FROM {$tablePrefix}contenttype";
    $result = $db->query($sql);
    while ($row = $db->fetch_row($result)){
      $_DATA["contenttype_suffix"][$row["CTID"]] = mb_strtolower(mb_substr($row["CTClass"],11));
    }
  }

  /**
   * Generates compressed language files on target directory.
   * The file contains two arrays: $_LANG and $_LANG2 of
   * all content types, modules and core files.
   * Each language folder has got its own compressed lang file.
   *
   * @param string $fileName
   *        The filename of the compressed language file.
   * @param string $targetDir
   *        The target directory to scan the language folders.
   * @param string $targetLabel
   *        The custom label of the target directory. It is used in the
   *        copyright information of each file.
   */
  function createCompressedLangFiles($fileName, $targetDir, $targetLabel)
  {
    $c = "\n/**
 * @package EDWIN $targetLabel
 * @copyright (c) ".date('Y')." Q2E GmbH
 */\n\n";
    $dirs = scandir($targetDir);
    foreach ($dirs as $dir)
    {
      // ignore files and empty folders
      if (!is_dir($targetDir.'/'.$dir) || !is_file($targetDir.'/'.$dir.'/lang.core.php')) {
        continue;
      }
      // read language files
      else
      {
        unset($_LANG);
        unset($_LANG2);
        // set init to false - lang.core.php will include all module / contentitem
        // language files
        $init = false;
        // Include default language file for custom language file ( use english
        // as fallback )
        if (!mb_strstr($dir, '-default')) {
          if (is_file($targetDir.'/'.$dir.'-default/lang.core.php')) {
            include($targetDir.'/'.$dir.'-default/lang.core.php');
          }
          else {
            include($targetDir.'/english-default/lang.core.php');
          }
        }
        include($targetDir.'/'.$dir.'/lang.core.php');
        $lang = var_export($_LANG, true);
        $lang2 = var_export($_LANG2, true);
        if (!$lang || !$lang2) {
          echo "$targetLabel: Fehler beim Auslesen des Language Arrays<br>";
        }
        $lang = '<?php '.$c.' $_LANG = '.$lang.";\n";
        $lang2 = '$_LANG2 = '.$lang2.";";
        // create compressed file
        $cfile = $targetDir.'/'.$dir.'/'.$fileName;
        $handle = fopen($cfile, 'w');
        if (@fwrite($handle, $lang.$lang2) === false) {
          echo "$targetLabel: Kann in die Datei $cfile nicht schreiben<br>";
        }
        fclose($handle);
        echo "$targetLabel: Datei $cfile erfolgreich erzeugt.<br>";
      }
    }
  }

function runConvertToUtf8($path, array $exclude)
{
  if (in_array($path, $exclude)) {
    return;
  }

  if (is_dir($path)) {
    $dir = opendir($path);
    while (false !== ($file = readdir ($dir)))
    {
      if ($file =='.' || $file == '..' || $file == '.svn') {
        continue;
      }
      runConvertToUtf8($path . '/' . $file, $exclude);
    }
    closedir($dir);
  }
  else if (is_file($path)) {
    convertFileToUtf8($path);
  }
}

function convertFileToUtf8($file)
{
  $content = file_get_contents($file);
  $encoding = mb_detect_encoding($content, mb_detect_order(), true);
  debug($file . ' [' . (string)$encoding . '] ');

  if ($encoding !== 'UTF-8') {
    $content = iconv($encoding, 'UTF-8', $content);
    file_put_contents($file, $content);
    debug('...updated');
  }
  else {
    debug('..skip');
  }
}

function debug($str)
{
  echo $str . "\n";
}

/**
 * Read a CSV file and generate a 2d array -> [row][column]
 * @param string $file CSV filename
 * @return multitype: array, empty on error
 */
function getCsvFileAsArray($file, $delimeter)
{
  $csvarray = array();
  if (($handle = fopen($file, "r")) !== FALSE) {
    // Set the parent multidimensional array key to 0.
    $nn = 0;
    // do not use fgetcsv() as it has problems with special chars at the
    // beginning of words
    while (($dataStr = fgets($handle)) !== FALSE) {
      // remove whitespaces
      $dataStr = str_replace(array("\r", "\r\n", "\n"), '', $dataStr);
      $data = explode($delimeter, $dataStr);
      // Count the total keys in the row.
      $c = count($data);
      // Populate the multidimensional array.
      for ($x=0;$x<$c;$x++) {
        $csvarray[$nn][$x] = $data[$x];
      }
      $nn++;
    }
    // Close the File.
    fclose($handle);
  }
  return $csvarray;
}

function manageStuffRegenerateAllPageIdentifiers()
{
  global $site, $tablePrefix, $db;

  $site = (int)$site;

  if ($site) {
    $trees = array(
      Navigation::TREE_MAIN,
      Navigation::TREE_FOOTER,
      Navigation::TREE_LOGIN,
    );


    $navigationSite = Navigation::getInstance($db, $tablePrefix)->getSiteByID($site);

    echo "<pre>Folgende Pfade wurden aktualisiert: \n";

    foreach ($trees as $tree) {
      manageStuffRegenerateAllPageIdentifiersByParentId($navigationSite->getRootPage($tree)->getID());
    }

    echo "</pre>";
  }
}

function manageStuffRegenerateAllPageIdentifiersByParentId($parentId)
{
  global $site, $tablePrefix, $db;

  $parent = Navigation::getInstance($db, $tablePrefix)->getPageByID($parentId);

  $sql = " SELECT CIID, CTitle, CIIdentifier "
       . " FROM {$tablePrefix}contentitem "
       . " WHERE FK_CIID = $parentId ";
  $result = $db->query($sql);

  while ($row = $db->fetch_row($result)) {
    $oldIdentifier = $row['CIIdentifier'];
    $newIdentifier = Container::make('Core\Url\ContentItemPathGenerator')->generateChildPath($parent->getDirectPath(), $row['CTitle'], $site, $row['CIID']);

    if ($oldIdentifier != $newIdentifier) {

      echo "$oldIdentifier > $newIdentifier \n";

      // 1. update path of item itself
      $sql = " UPDATE {$tablePrefix}contentitem "
           . " SET CIIdentifier = '{$db->escape($newIdentifier)}' "
           . " WHERE CIID = {$row['CIID']} ";
      $db->query($sql);

      // 2. check for child pages and update their paths too recursively
      $sql = " UPDATE {$tablePrefix}contentitem "
           . " SET CIIdentifier = CONCAT('{$newIdentifier}', SUBSTRING(CIIdentifier FROM " . (mb_strlen($oldIdentifier) + 1) . ")) "
           . " WHERE CIIdentifier LIKE '{$db->escape($oldIdentifier)}/%' "
           . " AND FK_SID = {$parent->getSite()->getID()}";
      $db->query($sql);
    }

    manageStuffRegenerateAllPageIdentifiersByParentId($row['CIID']);
  }

  $db->free_result($result);
}

<?php

/**
 * Reseller Module Class
 * Export and import reseller data
 *
 * $LastChangedDate: 2018-12-14 10:26:08 +0100 (Fr, 14 Dez 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2010 Q2E GmbH
 */
class ModuleReseller extends Module
{
  /**
   * Specifies the validation const. of emails
   * TODO: use instead of custom validation: class.Validation.php
   * So why was this custom validation coded?? -> Updates on projects!
   *
   * @var int
   */
  const rm_VALIDATION_EMAIL = 2;

  protected $_prefix = 'rm';

  public function show_innercontent() {
    if (isset($_POST["import_structure"])) {
      $this->import_csv_structure();
    } else if (isset($_POST["import_reseller"])) {
      $this->import_csv_reseller();
    } else if (isset($_POST["image_upload"])) {
      $this->_uploadImage();
    } else if (isset($_GET['image_delete'])) {
      $this->_deleteImage(urldecode($_GET['image_delete']));
    }

    if ($this->action[0] == "export_reseller") {
        return $this->export_csv_reseller();
    } else if ($this->action[0] == "export_structure") {
        return $this->export_csv_structure();
    } else {
        return $this->get_content();
    }
  }

  protected function _getContentLeftLinks()
  {
    return array(
        array($this->_parseUrl('export_structure'), $this->_langVar('export_structure_label')),
        array($this->_parseUrl('export_reseller'), $this->_langVar('export_reseller_label')),
    );
  }

  /**
   * Generate inner content and show it
   * @return multitype:string unknown
   */
  private function get_content()
  {
    global $_LANG, $_LANG2;

    $rm_hidden_fields = '';
    $rm_action = 'index.php?action=mod_reseller';

    $images = $this->_getUploadedImages();
    $imageItems = $this->_getUploadedImagesTemplateVars($images);

    $this->tpl->load_tpl('content_reseller', 'modules/ModuleReseller.tpl');
    $this->tpl->parse_if('content_reseller', 'message', $this->_getMessage(), $this->_getMessageTemplateArray('rm'));
    $this->tpl->parse_if('content_reseller', 'image_message', !$images,
        Message::createFailure($_LANG['rm_message_no_images_uploaded'])->getTemplateArray('rm'));
    $this->tpl->parse_loop('content_reseller', $imageItems, 'uploaded_images');
    $rm_content = $this->tpl->parsereturn('content_reseller',array_merge(array(
      'rm_function_label' => $_LANG["rm_function_label1"],
      'rm_function_label2' => $_LANG["rm_function_label2"],
      'rm_import_submit_label' => $_LANG["rm_import_submit_label"],
      'rm_file_structure_label' => $_LANG["rm_file_structure_label"],
      'rm_file_structure_info_label' => $_LANG["rm_file_structure_info_label"],
      'rm_file_reseller_label' => $_LANG["rm_file_reseller_label"],
      'rm_file_reseller_info_label' => $_LANG["rm_file_reseller_info_label"],
      'rm_hidden_fields' => $rm_hidden_fields,
      'rm_max_file_size'             => ConfigHelper::get('fi_file_size'),
      'rm_required_resolution_label' => $this->_getImageSizeInfo('rm', 0),
      'rm_action' => $rm_action,
    ), $_LANG2['rm']));

    return array(
        'content'      => $rm_content,
        'content_left' => $this->_getContentLeft(),
    );

  }

  /**
   * Import CSV (structure) file to database
   */
  private function import_csv_structure() {
    global $_LANG;

    $file = $_FILES['rm_file_structure']['tmp_name'];
    $csvarray = $this->readCSVFile($file);
    if ($csvarray) {
      $rows = count($csvarray);
      $colTitles = $csvarray[0];
      // Delete old data
      $this->db->query("DELETE FROM {$this->table_prefix}module_reseller_areas");
      $this->db->query("DELETE FROM {$this->table_prefix}module_reseller_labels");
      for ($row = 1; $row < $rows; $row++)
      {
        $rowData = $csvarray[$row];
        // Start at line 2; Line 1 should contain column titles
        if (count($rowData) >= 3)
        {
          $areaId = trim($rowData[0]);
          if (is_numeric($areaId) && $rowData[1] && is_numeric($rowData[2]))
          {
            // Only proceed if the content of each cell and the number of columns is correct
            // Insert sructure data
            $sql = "INSERT INTO {$this->table_prefix}module_reseller_areas "
                 . "(RAID, RAName, FK_RAID) "
                 . "VALUES ({$areaId}, "
                 . "        '{$this->db->escape(trim($rowData[1]))}', "
                 . "        {$rowData[2]})";
            $result = $this->db->query($sql);
            // labels for different languages
            for ($j = 3; $j < count($colTitles); $j++)
            {
              // valid label for language
              if (trim($rowData[$j])) {
                $sqlLabelValues[] = " ({$areaId}, '{$this->db->escape(trim($colTitles[$j]))}', "
                                  . "  '{$this->db->escape(trim($rowData[$j]))}')";
              }
              // use area name (no label defined for current language)
              else {
                $sqlLabelValues[] = " ({$areaId}, '{$this->db->escape(trim($colTitles[$j]))}', "
                                  . "  '{$this->db->escape(trim($rowData[1]))}')";
              }
            }
          } else {
              $this->setMessage(Message::createFailure($_LANG["rm_message_columns_error"].' '.($row+1)));
              return;
          }
        } else {
            $this->setMessage(Message::createFailure($_LANG["rm_message_columns_error"].' '.($row+1)));
            return;
        }
      }

      // language labels defined
      if (isset($sqlLabelValues) && !empty($sqlLabelValues)) {
        $sql = " INSERT INTO {$this->table_prefix}module_reseller_labels "
             . " (FK_RAID, RLLanguage, RLLabel) "
             . " VALUES " . implode(',', $sqlLabelValues);
        $this->db->query($sql);
      }

      $this->setMessage(Message::createSuccess($_LANG["rm_message_success"]));
      return;
    } else {
        $this->setMessage(Message::createFailure($_LANG["rm_message_no_file_structure"]));
        return;
    }
  }

  /**
   * Import CSV (reseller) file to database
   */
  private function import_csv_reseller() {
    global $_LANG;

    // First check if areas are available or in other words:
    // check if there's some kind of structure
    if (!$this->db->num_rows($this->db->query("SELECT RAID FROM {$this->table_prefix}module_reseller_areas"))) {
      $this->setMessage(Message::createFailure($_LANG["rm_message_structure"]));
      return;
    }
    $file = $_FILES['rm_file_reseller']['tmp_name'];
    $csvarray = $this->readCSVFile($file);
    if ($csvarray)
    {
      $columnCount = count($csvarray[0]);
      $rows = count($csvarray);

      // Delete old data
      $this->db->query("DELETE FROM {$this->table_prefix}module_reseller_assignation ");
      $this->db->query("DELETE FROM {$this->table_prefix}module_reseller_category ");
      $this->db->query("DELETE FROM {$this->table_prefix}module_reseller_category_assignation ");
      $this->db->query("DELETE FROM {$this->table_prefix}module_reseller ");

      // Read reseller categories
      $categories = array();
      $catValues = array();
      for ($i = 0; $i < $columnCount; $i++)
      {
        if (mb_stristr($csvarray[0][$i], mb_strtolower($_LANG['rm_category_label'])) !== false
           && mb_strstr($csvarray[0][$i], ':') !== false)
        {
          $tmpCat = explode(':', $csvarray[0][$i]);
          $catId = count($categories) + 1;
          $categories[$i] = $catId;
          $catValues[$catId] = " (".$catId.", '".$this->db->escape($tmpCat[1])."') ";
        }
      }
      if ($catValues)
      {
        $sql = " INSERT INTO {$this->table_prefix}module_reseller_category (RCID, RCName) "
             . " VALUES ".implode(', ', $catValues)." ";
        $this->db->query($sql);
      }
      // available areas
      $sql = " SELECT RAName, RAID FROM {$this->table_prefix}module_reseller_areas ";
      $resellerAreas = $this->db->GetAssoc($sql);
      $resellerAreaIDs = array_values($resellerAreas); /* area ids only */
      for ($row = 1; $row < $rows; $row++)
      {
        // Start at line 2; Line 1 should contain column titles
        if ($csvarray[$row][0])
        {
          // Only proceed if at least a name is available and the number of columns is correct
          $areas = explode(':', $csvarray[$row][12]);
          $default = 0;
          foreach ($areas as $key => $val) {
            if (trim(mb_strtolower($val)) == 'default') {
              $default = 1;
              break;
            }
          }

          $email = $this->_prepareMultipleValues($csvarray[$row][7], " ", self::rm_VALIDATION_EMAIL);
          $web = $this->_prepareMultipleValues($csvarray[$row][8], " ");

          // Return if there was set a message during preparing multiple values
          if ($this->_getMessage()) {
            return '';
          }

          // Insert reseller data
          $sql = "INSERT INTO {$this->table_prefix}module_reseller "
               . "(RName, RAddress, RPostalCode, RCity, RCountry, RCallNumber, RFax, REmail, RWeb, RNotes, RType, RImage, RDefault) "
               . "VALUES ('{$this->db->escape($csvarray[$row][0])}', "
               . "        '{$this->db->escape($csvarray[$row][1])}', "
               . "        '{$this->db->escape($csvarray[$row][2])}', "
               . "        '{$this->db->escape($csvarray[$row][3])}', "
               . "        '{$this->db->escape($csvarray[$row][4])}', "
               . "        '{$this->db->escape($csvarray[$row][5])}', "
               . "        '{$this->db->escape($csvarray[$row][6])}', "
               . "        '{$this->db->escape($email)}', "
               . "        '{$this->db->escape($web)}', "
               . "        '{$this->db->escape($csvarray[$row][9])}', "
               . "        '{$this->db->escape($csvarray[$row][10])}', "
               . "        '{$this->db->escape($csvarray[$row][11])}', "
               ."         {$default})";
          $result = $this->db->query($sql);
          $rId = $this->db->insert_id();

          // Category assignation
          if ($categories)
          {
            $catAssValues = array();
            foreach ($categories as $column => $cId)
            {
              if (isset($csvarray[$row][$column]) && $csvarray[$row][$column]) {
                $catAssValues[] = " ( $rId, $cId ) ";
              }
            }
            if ($catAssValues)
            {
              $sql = " INSERT INTO {$this->table_prefix}module_reseller_category_assignation "
                   . " (FK_RID, FK_RCID) VALUES ".implode(',', $catAssValues)." ";
              $result = $this->db->query($sql);
            }
          }

          // Assignation
          foreach ($areas as $val)
          {
            $tmpVal = trim($val);
            if ($tmpVal && (mb_strtolower($tmpVal) != 'default'))
            {
              $raId = null;
              // area name provided
              if (array_key_exists($tmpVal, $resellerAreas)) {
                $raId = (int)$resellerAreas[$tmpVal];
              }
              // area id provided
              else if (in_array($tmpVal, $resellerAreaIDs)) {
                $raId = $tmpVal;
              }

              if ($raId !== null) {
                $sql = " INSERT INTO {$this->table_prefix}module_reseller_assignation "
                     . " (FK_RAID, FK_RID) VALUES ( {$raId}, {$rId} ) ";
                $result = $this->db->query($sql);
              } else {
                $this->setMessage(Message::createFailure($_LANG["rm_message_invalid_assignation"].': '.$val));
                return;
              }
            }
          }
        } else {
            $this->setMessage(Message::createFailure($_LANG["rm_message_columns_error"].' '.($row+1)));
            return;
        }
      }
      $this->setMessage(Message::createSuccess($_LANG["rm_message_success"]));
      return;
    } else {
      $this->setMessage(Message::createFailure($_LANG["rm_message_no_file_reseller"]));
      return;
    }
  }

  /**
   * Read a CSV file and generate a 2d array -> [row][column]
   * @param string $file CSV filename
   * @return multitype: array, empty on error
   */
  private function readCSVFile($file) {
    if ($file) {
      // Open the File.
      if (($handle = fopen($file, "r")) !== FALSE) {
        // Set the parent multidimensional array key to 0.
        $nn = 0;
        $csvarray = array();
        // do not use fgetcsv() as it has problems with special chars at the
        // beginning of words
        while (($dataStr = fgets($handle)) !== FALSE) {
          // remove whitespaces
          $dataStr = str_replace(array("\r", "\r\n", "\n"), '', $dataStr);
          $data = explode(';', $dataStr);
          // Count the total keys in the row.
          $c = count($data);
          // Populate the multidimensional array.
          for ($x=0;$x<$c;$x++)
          {
            $csvarray[$nn][$x] = $this->db->escape($data[$x]);
          }
          $nn++;
        }
        // Close the File.
        fclose($handle);

        return $csvarray;
      }
    }
    return array();
  }

  /**
   * Generate reseller CSV file, finally output it as downloadable file
   */
  private function export_csv_reseller() {
    global $_LANG;

    $glue = ConfigHelper::get('rm_export_glue');
    $areaIds = ConfigHelper::get('rm_export_area_ids');

    header('Content-Type: text/x-csv');
    header('Content-Disposition: attachment; filename=resellers_export_'.date("Y-m-d").'.csv');
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: no-cache');

    $sql = " SELECT RCID, RCName "
         . " FROM {$this->table_prefix}module_reseller_category ";
    $categories = $this->db->GetAssoc($sql);

    $rm_title = array();
    $rm_title[] = $_LANG["rm_name_label"];
    $rm_title[] = $_LANG["rm_address_label"];
    $rm_title[] = $_LANG["rm_postal_code_label"];
    $rm_title[] = $_LANG["rm_city_label"];
    $rm_title[] = $_LANG["rm_country_label"];
    $rm_title[] = $_LANG["rm_call_number_label"];
    $rm_title[] = $_LANG["rm_fax_label"];
    $rm_title[] = $_LANG["rm_email_label"];
    $rm_title[] = $_LANG["rm_web_label"];
    $rm_title[] = $_LANG["rm_notes_label"];
    $rm_title[] = $_LANG["rm_type_label"];
    $rm_title[] = $_LANG["rm_image_label"];
    $rm_title[] = $_LANG["rm_assignation_label"];

    if ($categories)
    {
      foreach ($categories as $id => $cat) {
        $rm_title[] = $_LANG["rm_category_label"].':'.$cat;
      }
    }

    // output title row
    echo implode($glue, $rm_title)."\n";

    // read and output reseller data, row per row
    $result = $this->db->query("SELECT RID, RName, RAddress, RPostalCode, RCity, RCountry, RCallNumber, RFax, REmail, RWeb, RNotes, RType, RImage, RDefault from ".$this->table_prefix."module_reseller");
    while ($row = $this->db->fetch_row($result))
    {
      $default = ($row["RDefault"]) ? 'DEFAULT:' : '';

      $areas = array();
      //get reseller's areas (e.g.: countries, cities,...)
      $areaCol = $areaIds ? 'RAID' : 'RAName';
      $sql = " SELECT $areaCol "
           . " FROM {$this->table_prefix}module_reseller_areas a "
           . " INNER JOIN {$this->table_prefix}module_reseller_assignation ra "
           . "            ON a.RAID = ra.FK_RAID "
           . " WHERE FK_RID = {$row["RID"]} "
           . " ORDER BY $areaCol ";
      $areas = $this->db->GetCol($sql);

      // read assigned categories of this reseller
      $sql = " SELECT FK_RCID "
           . " FROM {$this->table_prefix}module_reseller_category_assignation "
           . " WHERE FK_RID = {$row["RID"]} ";
      $catAssigns = $this->db->GetCol($sql);

      $rm_content = array();
      $rm_content[] = parseCSVOutput($row["RName"]);
      $rm_content[] = parseCSVOutput($row["RAddress"]);
      $rm_content[] = parseCSVOutput($row["RPostalCode"]);
      $rm_content[] = parseCSVOutput($row["RCity"]);
      $rm_content[] = parseCSVOutput($row["RCountry"]);
      $rm_content[] = parseCSVOutput($row["RCallNumber"]);
      $rm_content[] = parseCSVOutput($row["RFax"]);
      $rm_content[] = parseCSVOutput($row["REmail"]);
      $rm_content[] = parseCSVOutput($row["RWeb"]);
      $rm_content[] = parseCSVOutput($row["RNotes"]);
      $rm_content[] = parseCSVOutput($row["RType"]);
      $rm_content[] = parseCSVOutput($row["RImage"]);
      $rm_content[] = $default.implode(':', $areas);
      foreach ($categories as $id => $cat)
      {
        if (in_array($id, $catAssigns)) {
          $rm_content[] = '1';
        }
        else {
          $rm_content[] = '0';
        }
      }
      // output reseller data
      echo implode($glue, $rm_content)."\n";
    }
    $this->db->free_result($result);

    exit();
  }

  /**
   * Generate structure CSV file, finally output it as downloadable file
   *
   */
  private function export_csv_structure()
  {
    global $_LANG;

    $glue = ConfigHelper::get('rm_export_glue');

    header('Content-Type: text/x-csv');
    header('Content-Disposition: attachment; filename=structure_export_'.date("Y-m-d").'.csv');
    header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: no-cache');

    $rm_title = array();
    $rm_title[] = 'RAID';
    $rm_title[] = 'RAName';
    $rm_title[] = 'FK_RAID';
    // different languages
    $sql = " SELECT DISTINCT(RLLanguage) "
         . " FROM {$this->table_prefix}module_reseller_labels "
         . " ORDER BY RLLanguage ASC ";
    $languages = $this->db->GetCol($sql);
    foreach ($languages as $lang) {
      $rm_title[] = $lang;
    }

    // output title row
    echo implode($glue, $rm_title)."\n";

    $tmpArea = null;
    // read and output reseller data, row per row
    $sql = " SELECT RAID, RAName, ra.FK_RAID, RLLanguage, RLLabel "
         . " FROM {$this->table_prefix}module_reseller_areas ra "
         . " JOIN {$this->table_prefix}module_reseller_labels rl "
         . "      ON RAID = rl.FK_RAID "
         . " ORDER BY RAID, RLLanguage ASC ";
    $result = $this->db->query($sql);
    while ($row = $this->db->fetch_row($result))
    {
      // same area as before (multiple rows foreach area language label)
      // add language label to csv
      if ($tmpArea == (int)$row["RAID"]) {
        $rm_content[] = parseCSVOutput($row['RLLabel']);
      }
      // new area or area changed
      else
      {
        // area available
        if ($tmpArea !== null) {
          echo implode($glue, $rm_content)."\n";
        }
        // create area data
        $rm_content = array();
        $tmpArea = (int)$row["RAID"]; /* remember the current area */
        $rm_content[] = parseCSVOutput($row["RAID"]);
        $rm_content[] = parseCSVOutput($row["RAName"]);
        $rm_content[] = parseCSVOutput($row["FK_RAID"]);
        // first language
        $rm_content[] = parseCSVOutput($row['RLLabel']);
      }
    }
    // Important:
    // Output the last area (not generated within loop as loop breaks with last
    // row available from database)
    echo implode($glue, $rm_content)."\n";

    $this->db->free_result($result);

    exit();
  }

  /**
   * Gets a prepared string value to store in db.
   *
   * @param string $string
   *        The values.
   * @param string $delimiter
   *        The delimiter, that is used to separate/glue
   *        given string.
   * @param string $validate
   *        Set this to validate something.
   * @return string
   *         String. If delimiter was found, string values are
   *         separated by given delimiter.
   *         If $validate is set and there is an error, empty string is returned.
   */
  private function _prepareMultipleValues($string, $delimiter, $validate='')
  {
    global $_LANG;

    $values = explode($delimiter, trim($string));
    // if there is an array, we have got at least two values
    if ($values && is_array($values))
    {
      foreach ($values as $key => $value)
      {
        $value = trim($value);
        // website string must be at least 2 characters long
        if (mb_strlen($value) > 2)
        {
          if ($validate == self::rm_VALIDATION_EMAIL && !Validation::isEmail($value))
          {
            $this->setMessage(Message::createFailure(sprintf($_LANG["rm_message_invalid_email"], $value)));
            return '';
          }
          $values[$key] = $value;
        }
        else {
          unset($values[$key]);
        }
      }
      // generate string of websites, seperated by space
      $values = implode(" ", $values);
    }
    else {
      $values = trim($string);
    }

    return $values;
  }

  private function _uploadImage()
  {
    global $_LANG;

    try {
      $upload = new CmsUpload('rm_image');
      if ($this->_verifyUpload($upload->getArray(), ConfigHelper::get('fi_file_size'))) {

        $targetName = $this->_getImageDirectory() . ResourceNameGenerator::file($upload->getName());
        if (is_file($targetName)) {
          unlinkIfExists($targetName);
        }

        $image = CmsImageFactory::create($upload->getTemporaryName());
        if ($this->_storeImageGetSize($image, 'rm', 0) !== ContentBase::IMAGESIZE_INVALID) {
          switch ($image->getType()) {
            case IMAGETYPE_GIF:
              $image->writeGif($targetName, 0644);
              break;
            case IMAGETYPE_JPEG:
              $image->writeJpeg($targetName, 0644);
              break;
            case IMAGETYPE_PNG:
              $image->writePng($targetName, 0644);
              break;
            default:
              $this->setMessage(Message::createFailure($_LANG['global_message_upload_type_error']));
          }
          $this->setMessage(Message::createSuccess($_LANG['rm_message_image_upload_success']));
        }
      }
    }
    catch (RuntimeException $e) {
      $this->setMessage(Message::createFailure($_LANG['rm_message_image_upload_error']));
    }
  }

  private function _getImageDirectory()
  {
    return '../img/reseller/';
  }

  private function _deleteImage($name)
  {
    global $_LANG;

    $path = $this->_getImageDirectory() . $name;
    if (is_file($path)) {
      unlinkIfExists($path);
      $this->setMessage(Message::createSuccess($_LANG['rm_message_image_delete_success']));
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['rm_message_image_delete_error']));
    }
  }

  private function _getUploadedImages()
  {
    $files = array();
    $dir = @opendir($this->_getImageDirectory());

    if ($dir) {
      while (false !== ($file = readdir ($dir)))
      {
        $parts = explode('.', $file);
        if (!in_array(mb_strtolower($parts[count($parts) - 1]), array('png', 'jpg', 'gif'))) {
          continue;
        }
        $files[] = $file;
      }
      closedir($dir);
    }

    return $files;
  }

  private function _getUploadedImagesTemplateVars(array $images)
  {
    $result = array();
    foreach ($images as $image) {
      $result[] = array(
        'rm_image_delete_link' => 'index.php?action=mod_reseller&image_delete=' . urlencode($image),
        'rm_image_name' => $image,
        'rm_image_src' => $this->_getImageDirectory() . $image,
      );
    }
    return $result;
  }
}
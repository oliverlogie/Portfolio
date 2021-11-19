<?php

/**
 * NOTE: For usage of this Module ensure to install the PHP Lookup with the
 *       EDWIN CMS.
 *
 * Management of PHP based lookup. Available functions are
 * - product import
 * - product-answer dependency import
 * - product image handling
 *
 * $LastChangedDate: 2018-12-14 10:26:08 +0100 (Fr, 14 Dez 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2013 Q2E GmbH
 */
class ModuleLookupManagement extends Module
{
  protected $_prefix = 'lu';

  public function show_innercontent()
  {
    if (isset($_POST["import_products"])) {
      $this->_importProducts();
    } else if (isset($_POST["import_dependencies"])) {
      $this->_importDependencies();
    } else if (isset($_POST["image_upload"])) {
      $this->_uploadImage();
    } else if (isset($_GET['image_delete'])) {
      $this->_deleteImage(urldecode($_GET['image_delete']));
    }

    return $this->_getContent();
  }

  /**
   * @return array
   */
  private function _getContent()
  {
    global $_LANG, $_LANG2;

    $images = $this->_getUploadedImages();
    $imageItems = $this->_getUploadedImagesTemplateVars($images);

    $this->tpl->load_tpl('content', 'modules/ModuleLookupManagement.tpl');
    $this->tpl->parse_if('content', 'message', $this->_getMessage(), $this->_getMessageTemplateArray($this->_prefix));
    $this->tpl->parse_if('content', 'image_message', !$images,
        Message::createFailure($_LANG['lu_message_no_images_uploaded'])->getTemplateArray($this->_prefix));
    $this->tpl->parse_loop('content', $imageItems, 'uploaded_images');
    $content = $this->tpl->parsereturn('content',array_merge(array(
      'lu_hidden_fields'             => '',
      'lu_max_file_size'             => ConfigHelper::get('fi_file_size'),
      'lu_required_resolution_label' => $this->_getImageSizeInfo($this->_prefix, 0),
      'lu_action'                    => 'index.php?action=mod_lookupmgmt',
    ), $_LANG2[$this->_prefix]));

    return array(
      'content' => $content,
    );
  }


  private function _importDependencies()
  {
    global $_LANG;

    $csv = $this->_getCsvUpload('lu_file_dependencies');
    if (!$csv) {
      return;
    }

    $lines = $csv->removeEmptyLines()->getLines();
    $mapping = array();
    $columnNames = $lines[0];
    foreach ($columnNames as $key => $col) {
      if ((int)trim($col)) {
        $mapping[$key] = (int)$col;
      }
    }

    // unset descriptive rows
    unset($lines[0]);
    unset($lines[1]);
    unset($lines[2]);

    $inserts = array();
    foreach ($lines as $number => $line) {
      $productId = (int)$line[0];
      if ($productId) {
        foreach ($mapping as $colNumber => $answerId) {
          $weight = trim($line[$colNumber]);
          if (!$weight) {
            $weight = 0;
          }
          $inserts[] = "('{$this->db->escape($weight)}', $productId, $answerId) ";
        }
      }
    }

    if ($inserts) {
      $sql = " DELETE FROM {$this->table_prefix}lookup_product_answer "
           . " WHERE 1 ";
      $this->db->query($sql);

      $sql = " INSERT INTO {$this->table_prefix}lookup_product_answer "
           . " ( PAWeight, FK_PID_Product, FK_AID_Answer ) VALUES "
           . implode(",", $inserts);
      $this->db->query($sql);

      $this->setMessage(Message::createSuccess($_LANG['lu_message_dependencies_import_success']));
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['lu_message_dependencies_import_failure']));
    }
  }

  private function _validateAnswerWeight($weight)
  {
    $weight = trim($weight);
    $error = preg_match('/[0-9]+\*[0-9]+\;[0-9]+/u', $weight, $matches);
    if (Validation::isNumber($weight) || ( !$error && $matches[0] == $weight )) {
      return true;
    }
    else {
      return false;
    }
  }

  private function _importProducts()
  {
    global $_LANG;

    $csv = $this->_getCsvUpload('lu_file_products');
    if (!$csv) {
      return;
    }

    $lines = $csv->removeEmptyLines()->getLines();
    $mapping = array();
    $columnNames = $lines[0];
    foreach ($columnNames as $key => $col) {
      if (trim($col)) {
        $mapping[$key] = $col;
      }
    }

    // unset descriptive rows
    unset($lines[0]);
    unset($lines[1]);

    $inserts = array();
    foreach ($lines as $number => $line) {
      $insert = "( ";
      foreach ($line as $key => $value) {
        if (isset($mapping[$key])) {
          $insert .= "'" . $this->db->escape(trim($value)) . "',";
        }
      }
      $insert = trim($insert, ',');
      $insert .= ") ";
      $inserts[] = $insert;
    }

    if ($inserts) {
      $sql = " DELETE FROM {$this->table_prefix}lookup_product "
           . " WHERE 1 ";
      $this->db->query($sql);

      $sql = " INSERT INTO {$this->table_prefix}lookup_product "
           . " (" . implode(',', $mapping) . ") VALUES "
           . implode(",", $inserts);
      $this->db->query($sql);
      $this->setMessage(Message::createSuccess($_LANG['lu_message_product_import_success']));
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['lu_message_product_import_failure']));
    }
  }

  /**
   * Returns the uploaded CSV file and sets a Module message on failure
   *
   * @param string $fieldName
   *        the name of the csv upload field
   *
   * @return CmsCsvFile | null
   */
  private function _getCsvUpload($fieldName)
  {
    global $_LANG;

    $csv = null;
    $msg = false;

    try {
      $upload = new CmsUpload($fieldName);
      if (!$upload->notEmpty()) { // empty file
        $msg = $_LANG['lu_message_failure_no_file'];
      }
    }
    catch(RuntimeException $e) { // not available from  $_FILES
      $msg = $_LANG['lu_message_failure_no_file'];
    }

    if (!$msg) {
      try {
        $csv = new CmsCsvFile($upload->getTemporaryName());
      }
      catch(InvalidArgumentException $e) {
        $msg = $_LANG['lu_message_failure_no_file'];
      }
      catch(RuntimeException $e) {
        $msg = $_LANG['lu_message_failure_file_not_readable'];
      }
    }

    if ($msg) {
      $this->setMessage(Message::createFailure($msg));
    }
    return $csv;
  }

  /**
   * Uploads, validates and stores an the provided file or image.
   */
  private function _uploadImage()
  {
    global $_LANG;

    try {
      $upload = new CmsUpload('lu_image');
      if ($this->_verifyUpload($upload->getArray(), ConfigHelper::get('fi_file_size'))) {

        $targetName = $this->_getImageDirectory() . ResourceNameGenerator::file($upload->getName());
        if (is_file($targetName)) {
          unlinkIfExists($targetName);
        }

        $image = CmsImageFactory::create($upload->getTemporaryName());
        if ($this->_storeImageGetSize($image, $this->_prefix, 0) !== ContentBase::IMAGESIZE_INVALID) {
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
          $this->setMessage(Message::createSuccess($_LANG['lu_message_image_upload_success']));
        }
      }
    }
    catch (RuntimeException $e) {
      $this->setMessage(Message::createFailure($_LANG['lu_message_image_upload_error']));
    }
  }

  /**
   * @return string
   */
  private function _getImageDirectory()
  {
    return '../img/lookup/';
  }

  /**
   * @param string $name
   */
  private function _deleteImage($name)
  {
    global $_LANG;

    $path = $this->_getImageDirectory() . $name;
    if (is_file($path)) {
      unlinkIfExists($path);
      $this->setMessage(Message::createSuccess($_LANG['lu_message_image_delete_success']));
    }
    else {
      $this->setMessage(Message::createFailure($_LANG['lu_message_image_delete_error']));
    }
  }

  /**
   * @return array
   */
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

  /**
   * @param array $images
   * @return array
   */
  private function _getUploadedImagesTemplateVars(array $images)
  {
    $result = array();
    foreach ($images as $image) {
      $result[] = $this->_getUploadedImageTemplateVars($image);
    }
    return $result;
  }

  /**
   * @param string $image
   * @return array
   */
  private function _getUploadedImageTemplateVars($image)
  {
    return array(
      'lu_image_delete_link' => 'index.php?action=mod_lookupmgmt&image_delete=' . urlencode($image),
      'lu_image_name'        => $image,
      'lu_image_src'         => $this->_getImageDirectory() . $image,
    );
  }
}

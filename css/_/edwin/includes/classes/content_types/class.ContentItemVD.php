<?php

  /**
   * Content Class
   *
   * $LastChangedDate: 2018-01-30 11:58:29 +0100 (Di, 30 Jan 2018) $
   * $LastChangedBy: ulb $

   * @package EDWIN Backend
   * @author Anton Jungwirth
   * @copyright (c) 2011 Q2E GmbH
   */
  class ContentItemVD extends ContentItem
  {
    protected $_configPrefix = 'vd';
    protected $_contentPrefix = 'vd';
    protected $_columnPrefix = 'V';
    protected $_contentElements = array(
      'Title' => 3,
      'Text' => 3,
    );
    protected $_contentImageTitles = false;
    protected $_templateSuffix = 'VD';

    /**
     * Deletes issuu document of current page
     * @see edwin/includes/classes/ContentItem::delete_content()
     */
    public function delete_content() {

      $sql = "SELECT VDocumentName "
           . "FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . "WHERE FK_CIID = {$this->page_id}";

      $name = $this->db->GetOne($sql);
      // delete issuu document
      $response = $this->_issuuDeleteDocuments($name);

      parent::delete_content();
    }

    /* (non-PHPdoc)
     * @see edwin/includes/classes/ContentItem::edit_content()
     */
    public function edit_content() {
      global $_LANG;

      if (isset($_POST['process_save']) || isset($_POST['process']) || isset($_POST['process_date'])) {
        if (isset($_FILES['vd_file'])) {
          if (!empty($_FILES['vd_file']['tmp_name'])) {
            // save/edit document
            $this->_saveDocument($_FILES['vd_file']['tmp_name'], $_FILES['vd_file']['name']);
            if (isset($_POST['vd_store_file'])) {
              $this->_storeDocument($_FILES['vd_file']);
            }
          }
        }
      }
      // save other changes
      parent::edit_content();
    }

    /**
     * Store document as decentral file download.
     * @param array $fileArray
     * @return bool
     *        true, if the file was created, otherwise false.
     */
    public function _storeDocument($fileArray)
    {
      $ciFiles = new ContentItemFiles($this->site_id, $this->page_id,
                                      $this->tpl, $this->db, $this->table_prefix,
                                      $this->action, $this->page_path, $this->_user,
                                      $this->session, $this->_navigation);
      $fixedFileArray = $fileArray;
      self::_fixFilesArray($fixedFileArray);
      $title = ResourceNameGenerator::file($fixedFileArray['name']);
      $created = $ciFiles->createFileNewUpload($title, $fileArray);
      // add the title to the search index of the content item
      if ($created) {
        $this->_spiderDownload($title, $this->page_id, true);
      }
      return $created;
    }

    public function get_content($params = array())
    {
      global $_LANG, $_LANG2;

      $row = $this->_getData();

      // May delete a document
      $this->_deleteDocument();

      $vd_document_id = $row["VDocumentId"];
      $vd_document_name = $row["VDocumentName"];
      $vd_document_title = $row["VDocumentTitle"];

      $documentName = '';
      $pages = 1;
      if (empty($vd_document_name)) {
        for ($i = 0; $i < $pages; $i++) {
          $response = $this->_issuuDocumentList($i);
          $json = Json::Decode($response);
          if ($this->_checkResponse($json)) {
            foreach ($json['rsp']['_content']['result']['_content'] as $key => $documents) {
              foreach ($documents as $key => $document) {
                if ($document['documentId'] == $vd_document_id) {
                  //save document name to database
                  $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
                       . " SET VDocumentName = '{$this->db->escape($document['name'])}' "
                       . " WHERE FK_CIID = {$this->page_id}";
                  $this->db->query($sql);
                  $vd_document_name = $document['name'];
                  break;
                }
              }
            }
            $pages ++;
          } else if ($json['rsp']['_content']['error']['code'] == '012') {
            // break if error code: 012, message: Request throttled
            break;
          }

          if ($vd_document_name) {
            // stop if document name was found
            break;
          }
        }
      }

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'issuu_document', $vd_document_name);
      $this->tpl->parse_vars($tplName, array (
        'vd_convert_document' => (empty($vd_document_name) && !empty($vd_document_id)) ? '<br /><br />'.$_LANG['vd_convert_document'] : '',
        'vd_smalllink_delete_link' => "index.php?action=content&amp;site={$this->site_id}&amp;page={$this->page_id}&amp;deleteDocumentId={$vd_document_id}",
        'vd_smalllink_delete_question_label' => $_LANG['vd_smalllink_delete_question_label'],
        'vd_smalllink_delete_label' => $_LANG['vd_smalllink_delete_label'],
        'vd_document_id' => $vd_document_id,
        'vd_document_name' => $vd_document_name,
        'vd_document_title' => $vd_document_title,
        'vd_issuu_user' => $this->getConfig('issuu_user'),
        'vd_document_info' => $_LANG["vd_document_info"],
        'vd_current_document' => $_LANG["vd_current_document"],
        'vd_document_label' => $_LANG["vd_document_label"],
      ));

      return parent::get_content(array_merge($params, array(
        'row'      => $row,
        'settings' => array( 'tpl' => $tplName ),
      )));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Preview Content                                                                       //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function preview()
    {
      $post = new Input(Input::SOURCE_POST);

      $tplName = $this->_getStandardTemplateName();
      $this->tpl->set_tpl_dir("../templates");
      $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
      $this->tpl->parse_if($tplName, 'inside_archive', $this->_isInsideArchive(), array(
        'm_metainfo_part' => $this->_getMetainfoPart('vd')
      ));
      $this->tpl->parse_vars($tplName, array (
        'c_vd_title1' => parseOutput($post->readString('vd_title1', Input::FILTER_CONTENT_TITLE),2),
        'c_vd_title2' => parseOutput($post->readString('vd_title2', Input::FILTER_CONTENT_TITLE),2),
        'c_vd_title3' => parseOutput($post->readString('vd_title3', Input::FILTER_CONTENT_TITLE),2),
        'c_vd_text1' => parseOutput($post->readString('vd_text1', Input::FILTER_CONTENT_TEXT), 1),
        'c_vd_text2' => parseOutput($post->readString('vd_text2', Input::FILTER_CONTENT_TEXT), 1),
        'c_vd_text3' => parseOutput($post->readString('vd_text3', Input::FILTER_CONTENT_TEXT), 1),
        'c_surl' => "../",
        'm_print_part' => $this->get_print_part(),
      ));
      $vd_content = $this->tpl->parsereturn($tplName, $this->_getFrontentLang());
      $this->tpl->set_tpl_dir("./templates");
      return $vd_content;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Return Content of all ContentItems                                                    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    public function return_class_content()
    {
      $class_content = array();
      $result = $this->db->query("SELECT FK_CTID,CIID,CIIdentifier,CTitle,VTitle1,VTitle2,VTitle3,VText1,VText2,VText3 FROM ".$this->table_prefix."contentitem_vd cic LEFT JOIN ".$this->table_prefix."contentitem ci ON ci.CIID=cic.FK_CIID ORDER BY cic.FK_CIID ASC");
      while ($row = $this->db->fetch_row($result)){
        $class_content[$row["CIID"]]["path"] = $row["CIIdentifier"];
        $class_content[$row["CIID"]]["path_title"] = $row["CTitle"];
        $class_content[$row["CIID"]]["type"] = $row["FK_CTID"];
        $class_content[$row["CIID"]]["c_title1"] = $row["VTitle1"];
        $class_content[$row["CIID"]]["c_title2"] = $row["VTitle2"];
        $class_content[$row["CIID"]]["c_title3"] = $row["VTitle3"];
        $class_content[$row["CIID"]]["c_text1"] = $row["VText1"];
        $class_content[$row["CIID"]]["c_text2"] = $row["VText2"];
        $class_content[$row["CIID"]]["c_text3"] = $row["VText3"];
        $class_content[$row["CIID"]]["c_image_title1"] = "";
        $class_content[$row["CIID"]]["c_image_title2"] = "";
        $class_content[$row["CIID"]]["c_image_title3"] = "";
        $class_content[$row["CIID"]]["c_sub"] = array();
      }
      $this->db->free_result($result);

      return $class_content;
    }

    protected function _getData()
    {
      // Create database entries.
      $this->_checkDataBase();

      foreach ($this->_contentElements as $type => $count) {
        for ($i = 1; $i <= $count; $i++) {
          $sqlArgs[] = $this->_getContentElementColumnName($type, $count, $i);
        }
      }

      $sql = ' SELECT ' . implode(', ', $this->_dataFields) . ', '
           . '        VDocumentId, VDocumentName, VDocumentTitle '
           .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
           .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
           . " FROM {$this->table_prefix}contentitem ci "
           . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
           . '      ON CIID = ci_sub.FK_CIID '
           . " WHERE CIID = $this->page_id ";
      return $this->db->GetRow($sql);
    }

    /**
     * Checks if issuu response is okay or if there are errors
     * @param array $json decoded json object array
     * @throws Exception
     * @return boolean false if there are errors, otherwise true
     */
    private function _checkResponse($json) {

      global $_LANG;

      if ($json !== NULL) {
        if ($json['rsp']['stat'] == 'ok') {
          return true;
        } else if ($json['rsp']['stat'] == 'fail') {
          $error = $_LANG['vd_message_issuu_error'].'[';
          // TODO: add error code messages: http://issuu.com/services/api/gettingstarted.html#step5
          foreach ($json['rsp']['_content']['error'] as $key => $value) {
            $error .= $key.': '.$value.', ';
          }
          // remove last whitspace+comma
          $error = mb_substr_replace($error, "", -2);
          if (!$json['rsp']['_content']['error']['code'] == '012') {
            // do not show error code 012, message: Request throttled
            $this->setMessage(Message::createFailure($error.']'));
          }
        } else {
          // should not happen; check issuu API http://issuu.com/services/api/gettingstarted.html#step5
          throw new Exception("Unknown ISSUU - JSON stat: ".$json['rsp']['stat']);
        }
      } else {
        // response of issuu could not be converted from json to multidimensional array
        throw new Exception("Unknown issuu response!");
      }

      return false;
    }

    /**
     * Deletes document(s) if GET deleteDocumentId is available
     */
    private function _deleteDocument() {
      global $_LANG;

      if (isset($_GET["deleteDocumentId"])) {
        $id = $this->db->escape($_GET["deleteDocumentId"]);
        $sql = "SELECT VDocumentName "
             . "FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
             . "WHERE VDocumentId = '{$id}' ";

        $name = $this->db->GetOne($sql);
        // delete issuu document
        $response = $this->_issuuDeleteDocuments($name);
        $json = Json::Decode($response);
        if ($this->_checkResponse($json)) {
          // also delete document information (ID, name, title)
          $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
               . " SET VDocumentId = '', "
               . "     VDocumentName = '', "
               . "     VDocumentTitle = '' "
               . "WHERE VDocumentId = '{$id}' ";
          $this->db->query($sql);
        }

        $this->setMessage(Message::createSuccess($_LANG["vd_smalllink_delete_success"]));
      }
    }

    /**
     * Saves a new document
     * @param String $tmpFileLocation
     *        Temporary location of currently uploaded document
     * @param String $fileName
     *        Name of uploaded document
     */
    private function _saveDocument($tmpFileLocation, $fileName) {

      global $_LANG;

      // title should not be longer than 100 chars
      $fileName = mb_substr($fileName, 0, 100);
      // only letters (a-z), numbers (0-9) and characters (_.-) allowed, so remove everything else, also spaces
      $fileName = preg_replace('/[^0-9A-Za-z.-_]/ui', '', $fileName);

      $sql = "SELECT VDocumentName "
           . "FROM {$this->table_prefix}contentitem_{$this->_contentPrefix} "
           . "WHERE FK_CIID = {$this->page_id}";
      $name = $this->db->GetOne($sql);
      // if old issuu document is available, delete it
      if ($name)
        $response = $this->_issuuDeleteDocuments($name);

      $response = $this->_issuuDocumentUpload($tmpFileLocation, $fileName);
      $json = Json::Decode($response);
      if ($this->_checkResponse($json)) {
        // doucment was successfully uploaded

        $documentId = $json['rsp']['_content']['document']['documentId'];
        // Title of the publication
        $title = $json['rsp']['_content']['document']['title'];

        //save document ID to database
        $sql = " UPDATE {$this->table_prefix}contentitem_{$this->_contentPrefix} "
             . " SET VDocumentId = '{$this->db->escape($documentId)}', "
             . "     VDocumentName = '', "
             . "     VDocumentTitle = '{$this->db->escape($title)}' "
             . " WHERE FK_CIID = {$this->page_id}";
        $this->db->query($sql);
      }
    }

    /**
     * Generates parameters and requests issuu.com to upload a new document
     * @see http://issuu.com/services/api/issuu.document.upload.html
     * @param String $fileLocation locaction of temporary file
     * @param String $name
     *        Value determining the URL address of the publication http://issuu.com/<username>/docs/<name>
     *        The name must be 3-50 characters long. Use lowercase letters (a-z), numbers (0-9) and characters (_.-).
     *        No spaces allowed. This value must be unique for the account.
     *        In case no value is specified this name will be autogenerated
     * @return String cURL response of issuu.com, in this case it would be a json object
     */
    private function _issuuDocumentUpload($fileLocation, $name)
    {
      $parameters = array(
        'action' => 'issuu.document.upload',
        'file' => curl_file_create($fileLocation),
        'apiKey' => $this->getConfig('issuu_api_key'), //Application key for the account (required)
        'format' => 'json', // format of issuu.com response should be json
        'commentsAllowed' => $this->getConfig('issuu_comments_allowed'),
        'access' => $this->getConfig('issuu_access'),
        'title' => $name, // Title of the publication. If no value is specified the filename of the uploaded document will be used
        'ratingsAllowed' => $this->getConfig('issuu_ratings_allowed'),
      );

      $parameters = $this->_issuuGenerateSignature($parameters);

      // curl http post send
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $this->getConfig('issuu_uri_upload'));
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);

      $response = curl_exec($ch);
      curl_close($ch);

      return $response;
    }

    /**
     * Generates parameters and requests issuu.com to list documents
     * @param int $startIndex zero based index to start pagination from
     * @return String cURL response of issuu.com, in this case it would be a json object
     */
    private function _issuuDocumentList($startIndex = 0)
    {
      $parameters = array(
        'action' => 'issuu.documents.list',
        'apiKey' => $this->getConfig('issuu_api_key'),     // application key for the account (required)
        'responseParams' => 'name,documentId,title',  // comma-separated list of response parameters to be returned. If no value is submitted all parameters will be returned
        'format' => 'json',                 // must be "xml" or "json"
        'access' => $this->getConfig('issuu_access'),      // "public" or "private". If no value is submitted both "public" and "private" documents will be returned
        'pageSize' => 30,                   // maximum number of documents to be returned. Value must be between 0 - 30
        'startIndex' => $startIndex,        // zero based index to start pagination from
        'documentSortBy' => 'publishDate',  // sort parameters: @see http://issuu.com/services/api/issuu.document.list.html
        'resultOrder' => 'desc',            // "asc" or "desc"
        'documentStates' => 'A',            // list only active documents
      );

      $parameters = $this->_issuuGenerateSignature($parameters);

      $url = $this->getConfig('issuu_uri').'?';

      foreach ($parameters as $key => $value) {
        $url .= $key.'='.$value.'&';
      }

      // curl http get send
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

      $response = curl_exec($ch);
      curl_close($ch);

      return $response;
    }

    /**
     * Deletes one or more documents on the issuu account
     * @param string $names Comma-separated list of document names
     * @return String cURL response of issuu.com, in this case it would be a json object
     */
    private function _issuuDeleteDocuments($names)
    {
      $parameters = array(
        'action' => 'issuu.document.delete',
        'apiKey' => $this->getConfig('issuu_api_key'), // application key for the account (required)
        'format' => 'json', // format of issuu.com response should be json
        'names' => $names
      );

      $parameters = $this->_issuuGenerateSignature($parameters);

      // curl http post send
      $ch = curl_init();

      $url = '';
      foreach ($parameters as $key => $value) {
        $url .= $key.'='.$value.'&';
      }

      curl_setopt($ch, CURLOPT_URL, $this->getConfig('issuu_uri'));
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $url);

      $response = curl_exec($ch);
      curl_close($ch);

      return $response;
    }

    /**
     * Sorts all parameters and generates a signature with secret api key
     * @param array $parameters
     *        parameters that should be sorted and used to generate a signature
     * @return array parameters
     *         sorted parameters with generated signature
     */
    private function _issuuGenerateSignature($parameters)
    {
      // 1. Sort request parameters alphabetically (e.g. foo=1, bar=2, baz=3 sorts to bar=2, baz=3, foo=1)
      ksort($parameters);

      // 2. Concatenate in order your API secret key and request name-value pairs (e.g. SECRETbar2baz3foo1)
      $signature = $this->getConfig('issuu_api_key_secret');

      foreach ($parameters as $k => $v) {
        // file is not part of standard parameters and should not be part of signature
        if ($k == 'file') {
          continue;
        } else {
          $signature .= $k . $v;
        }
      }

      // 3. Calculate the signature as the MD5 hash of this string
      $signature = md5($signature);

      // 4. Include the signature parameter in the request encoded as lowercase HEX
      $signature = mb_strtolower($signature);

      $parameters['signature'] = $signature;

      return $parameters;
    }

  }


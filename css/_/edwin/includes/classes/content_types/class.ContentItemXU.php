<?php

/**
 * Content Class
 *
 * $LastChangedDate: 2019-05-10 11:58:32 +0200 (Fr, 10 Mai 2019) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */

class ContentItemXU extends ContentItem
{
  protected $_configPrefix = 'xu';
  protected $_contentPrefix = 'xu';
  protected $_columnPrefix = 'XU';
  protected $_contentElements = array(
    'Link' => 1
  );
  protected $_contentImageTitles = false;
  protected $_templateSuffix = 'XU';

  public function edit_content()
  {
    global $_LANG, $_LANG2;

    $this->_changePageActivation();

    $post = new Input(Input::SOURCE_POST);

    $extLink = $post->readString('xu_url');
    $intLink = $this->_readContentElementsLinks();

    // neither an external link nor an internal link has been provided
    if (!$extLink && !$intLink['XULink'])
    {
      $this->setMessage(Message::createFailure($_LANG["xu_message_insufficient_input"]));
      return;
    }

    if ($extLink)
    {
      // validate url protocol
      $valid = false;
      $protocols = $this->_configHelper->getVar('url_protocols', 'xu');
      foreach ($protocols as $protocol)
      {
        if (mb_substr($extLink, 0, mb_strlen($protocol)) === $protocol) {
          $valid = true;
          break;
        }
      }

      if (!$valid) {
        $this->setMessage(Message::createFailure(sprintf($_LANG['xu_message_invalid_url_protocol'], implode(', ', $protocols))));
        return;
      }
    }

    $sql = " UPDATE {$this->table_prefix}contentitem_xu "
         . "    SET XUUrl = '$extLink', "
         . "        XULink = " . $intLink['XULink'] . " "
         . " WHERE FK_CIID = $this->page_id ";
    $result = $this->db->query($sql);

    $this->setMessage(Message::createSuccess($_LANG["global_message_success"]));

    if ($this->_hasContent()) {
      // set contentitem CHasContent field to true
      $sql = " UPDATE {$this->table_prefix}contentitem "
           . ' SET CHasContent = 1 '
           . " WHERE CIID = {$this->page_id}";
      $result = $this->db->query($sql);
    }
  }

  public function get_content($params = array())
  {
    global $_LANG, $_LANG2;

    $row = $this->_getData();

    $internalLink = $this->getInternalLinkHelper($row['XULink']);

    $tplName = $this->_getStandardTemplateName();
    $this->tpl->load_tpl($tplName, $this->_getTemplatePath());
    $this->tpl->parse_vars($tplName, array_merge($internalLink->getTemplateVars('xu'), array (
      'xu_url' => parseOutput($row['XUUrl']),
      'xu_autocomplete_contentitem_url' => "index.php?action=response&site=$this->site_id&page=$this->page_id&request=ContentItemAutoComplete&scope=global&excludeContentItems=$this->page_id",
    )));

    // TODO: do not display share / blog options within content action boxes

    $settings = array(
      'no_preview' => true,
      'tpl'        => $tplName,
    );

    return parent::get_content(array_merge($params, array(
      'content_top' => "",
      'row'         => $row,
      'settings'    => $settings,
    )));
  }

  public function getImageTitles($subcontent = true)
  {
    return array();
  }

  public function getTexts($subcontent = true)
  {
    return array();
  }

  ///////////////////////////////////////////////////////////////////////////////////////////
  // Return Content of all ContentItems                                                    //
  ///////////////////////////////////////////////////////////////////////////////////////////
  public function return_class_content()
  {
    return array();
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
         . '        c_link.CIID AS Link_CIID, c_link.CIIdentifier AS Link_CIIdentifier, '
         . '        c_link.FK_SID AS Link_FK_SID, XUUrl '
         .( $sqlArgs ? ', '.implode(',', $sqlArgs) : '' )
         .( $this->_contentImageTitles ? ', '.$this->_columnPrefix.'ImageTitles' : '' )
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contentitem_{$this->_contentPrefix} ci_sub "
         . '      ON CIID = ci_sub.FK_CIID '
         . " LEFT JOIN {$this->table_prefix}contentitem c_link "
         . '      ON c_link.CIID = ci_sub.XULink '
         . " WHERE ci.CIID = $this->page_id ";
    return $this->db->GetRow($sql);
  }

  /**
   * {@inheritDocs}
   */
  protected function _hasContent()
  {
    $row = $this->_getData();

    return $row['Link_CIID'] || $row['XUUrl'];
  }
}

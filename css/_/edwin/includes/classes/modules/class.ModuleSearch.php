<?php

/**
 * class.ModuleSearch.php
 *
 * $LastChangedDate: 2018-03-08 14:17:07 +0100 (Do, 08 Mrz 2018) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Frontend
 * @author Anton Jungwirth
 * @copyright (c) 2012 Q2E GmbH
 */

class ModuleSearch extends Module
{

  /**
   * Constant defines the search option for searching
   * only on current site.
   *
   * @var string
   */
  const OPTION_CURRENT_SITE = 'current_site';

  /**
   * Constant defines the search option for fulltext search.
   *
   * @var string
   */
  const OPTION_FULLTEXT = 'fulltext';

  /**
   * Module's prefix used for configuration, template
   * and language variables.
   *
   * @var string
   */
  protected $_prefix = 'sh';

  /**
   * Module's shortname.
   *
   * @var string
   */
  protected $_shortname = 'search';

  /**
   * Gets the search box for left content.
   *
   * @return string
   *         The parsed ModuleSearch_box template.
   */
  public function getSearchBox()
  {
    $tplId = 'content_'.$this->_prefix.'_box';
    $this->tpl->load_tpl($tplId, 'modules/ModuleSearch_box.tpl');
    $this->tpl->parse_if($tplId, 'sh_last_search_term', $this->readLastSearchTerm());
    $this->tpl->parse_if($tplId, 'sh_available', $this->isAvailableForUser($this->_user, $this->_navigation->getSiteByID($this->site_id)));
    return $this->tpl->parsereturn($tplId, array ());
  }

  /**
   * Gets the latest search term from session.
   *
   * @return string
   */
  public function readLastSearchTerm()
  {
    if ($this->session->read('sh_last_search_term')) {
      return $this->session->read('sh_last_search_term');
    }
    return '';
  }

  /**
   * Send ajax response.
   *
   * @see ContentBase::sendResponse()
   */
  public function sendResponse($request)
  {
    switch ($request) {
      case 'search':
        return $this->_sendResponseSearch();
        break;
      case 'search_result':
        return $this->_sendResponseSearchResult();
        break;
      default:
        // Call the sendResponse() method of the parent Module class.
        parent::sendResponse($request);
        break;
    }
  }

  /**
   * Parses the search result template.
   *
   * @return string
   *         The parsed search result template.
   */
  private function _getSearchResult()
  {
    global $_LANG, $_LANG2;

    $request = new Input(Input::SOURCE_REQUEST);
    $searchString = $this->_getSearchString();
    $this->session->save('sh_last_search_term', $searchString);
    $numberOfResults = 0;
    $resultsPerPage = (int)ConfigHelper::get('sh_results_per_page');
    $searchResult = array();
    $pageNavigation = "";
    // If OPTION_CURRENT_SITE is true the string will be searched only on current site content.
    $optionCurrentSite = $this->_readOption(self::OPTION_CURRENT_SITE);
    // If OPTION_CURRENT_SITE is true the string will be searched only in content item titles and text elements.
    $optionFulltext = $this->_readOption(self::OPTION_FULLTEXT);

    // Only run search if there is a search string (notice: Input::readString performed already the trim stuff)
    if ($searchString) {
      $searchResult = $this->_search($searchString);
      $numberOfResults = count($searchResult);
      // Handle paging
      $resultPage = 1;
      if ($request->readInt('page')) {
        $resultPage = $request->readInt('page');
      }
      $pageOffset = 0;
      if ($numberOfResults > $resultsPerPage) {
        // If requested result page is greater than the possible amount of pages, set it to the highest possible result page
        if ($resultPage > ($numberOfResults / $resultsPerPage)) {
          $resultPage = ceil($numberOfResults / $resultsPerPage);
        }
        $pageNavigation = create_page_navigation($numberOfResults, $resultPage, 10, $resultsPerPage, '', '', "");
        $pageOffset = (($resultPage - 1) * $resultsPerPage);
      }
      $searchResult = array_slice($searchResult, $pageOffset, $resultsPerPage);
    }

    $message = null;
    if ($searchString && !$numberOfResults) {
      $message = Message::createFailure(sprintf($_LANG['sh_no_result'], parseOutput($searchString)));
    }

    $tplId = 'content_'.$this->_prefix.'_result';
    $this->tpl->load_tpl($tplId, 'modules/ModuleSearch_result.tpl');
    $this->tpl->parse_if($tplId, 'sh_result', $numberOfResults);
    $this->tpl->parse_if($tplId, 'sh_message', $message, $message ? $message->getTemplateArray('sh') : array());
    $this->tpl->parse_if($tplId, 'sh_paging', ($numberOfResults > $resultsPerPage));
    $this->tpl->parse_loop($tplId, $searchResult, 'sh_results');
    return $this->tpl->parsereturn($tplId, array_merge(array (
      'sh_number_of_results' => ($numberOfResults > 1) ? sprintf($_LANG['sh_results'], $numberOfResults) : $_LANG['sh_result'],
      'sh_result_page_navigation' => $pageNavigation,
    ), $_LANG2[$this->_prefix]));
  }

  /**
   * Gets the search string from request or session.
   *
   * @return string
   */
  private function _getSearchString()
  {
    $request = new Input(Input::SOURCE_REQUEST);
    // urldecode is not required. The superglobals $_GET and $_REQUEST are already decoded.
    $searchString = $request->readString('text');
    if (!$searchString && $request->readInt('search_last_term')) {
      $searchString = $this->readLastSearchTerm();
    }
    return $searchString;
  }

  /**
   * Gets the option data.
   *
   * @param string $name
   * @return Ambigous <NULL, multitype:>
   */
  private function _readOption($name)
  {
    return $this->session->read('sh_option_'.$name);
  }

  /**
   * Saves the option data.
   *
   * @param string $name
   * @param Ambigous <NULL, multitype:> $data
   */
  private function _saveOption($name, $data)
  {
    $this->session->save('sh_option_'.$name, $data);
  }

  /**
   * Searches for given string and with given options.
   *
   * @param string $searchString
   *        The string to search for.
   * @return array
   *         The search result.
   */
  private function _search($searchString)
  {
    global $_LANG;

    $currentSite = $this->_navigation->getSiteByID($this->site_id);
    // Replace single backslash with double backslash to allow
    $searchString = str_replace("\\", "\\\\", $searchString);
    $searchString = $this->db->escape($searchString);
    $sqlScope = '';
    $sqlArg = '';
    $sqlJoin = '';
    $sqlOrder = '';
    $searchResult = array();

    // Search only on current site
    if ($this->_readOption(self::OPTION_CURRENT_SITE)) {
      $sqlScope = "AND FK_SID = {$currentSite->getID()} ";
    }

    // Fulltext search
    if ($this->_readOption(self::OPTION_FULLTEXT)) {
      $searchWords = explode(" ", $searchString);
      $searchQuery = array();
      // Parse words from search and create sql query.
      foreach ($searchWords as $id => $searchword) {
        $searchQuery[] = ' WWord LIKE "%'.$searchword.'%" ';
      }
      // Create the query string from query searchstring array
      $sqlArg = ' OR '.implode(' OR ', $searchQuery);

      $sqlJoin = " LEFT JOIN {$this->table_prefix}contentitem_words cw ON cw.FK_CIID = ci.CIID ";
      // Order for fulltext search
      $sqlOrder = ' ORDER BY WContentTitleCount + WTitleCount + WTextCount + WDownloadCount + WImageCount DESC, '
                 . ' WContentTitleCount DESC, WTitleCount DESC, '
                 . ' WTextCount DESC, WDownloadCount DESC, WImageCount DESC, '
                 . ' CHAR_LENGTH(WWord) ASC ';
    }

    $sql = ' SELECT CIID, CIIdentifier, CTitle, CTClass, FK_SID, CTree, ci.FK_CTID, FUDeleted '
         . " FROM {$this->table_prefix}contentitem ci "
         . " JOIN {$this->table_prefix}contenttype ct ON ci.FK_CTID = CTID "
         . " $sqlJoin "
         . " LEFT JOIN {$this->table_prefix}frontend_user ON ci.FK_FUID = FUID "
         . " WHERE ( "
         . "  CIIdentifier LIKE '%$searchString%' OR "
         . "  CTitle LIKE '%$searchString%' "
         . "  $sqlArg "
         . " ) "
         . " AND ci.CTree != 'hidden' " // Notice: The user object does not contain rights for hidden pages too.
         . $sqlScope
         . ' GROUP BY CIID, CIIdentifier, CTitle, CTClass, FK_SID, CTree, ci.FK_CTID, FUDeleted '
         . $sqlOrder;
    $result = $this->db->query($sql);

    while ($row = $this->db->fetch_row($result)) {
      // Filter not available pages to current user and filter user pages of deleted
      // users.
      if (!$this->_user->AvailablePage($this->_navigation->getPageByID($row['CIID']))
         || $row['FUDeleted'] == 1) {
        continue;
      }
      // Content items from the current site are local, from other sites global.
      $siteScope = ScopeHelper::SCOPE_LOCAL;
      if ($currentSite->getID() != (int)$row['FK_SID']) {
        $siteScope = ScopeHelper::SCOPE_GLOBAL;
      }
      $typeShortname = ContentItem::getTypeShortname($row['FK_CTID']);
      $searchResult[] = array(
        'id'          => (int)$row['CIID'],
        'identifier'  => $row['CIIdentifier'],
        'title'       => $row['CTitle'],
        'contenttype' => $row['CTClass'],
        'siteID'      => (int)$row['FK_SID'],
        'siteToken'   => ScopeHelper::getSiteToken((int)$row['FK_SID']),
        'siteTitle'   => self::getLanguageSiteLabel($this->_navigation->getSiteByID($row['FK_SID'])),
        'siteScope'   => $siteScope,
        'tree'        => $row['CTree'],
        'type'        => $typeShortname,
        'backend_url' => root_url() .'edwin/',
        'link' => 'index.php?action=content&site='.$row['FK_SID'].'&page='.$row['CIID'],
        'type_tooltip' => $_LANG['m_nv_ctype_label_'.$typeShortname],
        'tree_tooltip' => $_LANG["global_sites_backend_root_{$row['CTree']}_title"],
      );
    }
    $this->db->free_result($result);
    // Sort the results on normal search
    if (!$this->_readOption(self::OPTION_FULLTEXT)) {
      $autoCompleteResultSorter = new AutoCompleteResultSorter($currentSite->getID());
      usort($searchResult, array($autoCompleteResultSorter, 'sortCallback'));
    }
    // Add common template variable names
    foreach ($searchResult as $resultKey => $result) {
      foreach ($result as $key => $value) {
        if (in_array($key, array('title', 'siteTitle', 'tree_tooltip'))) {
          $value = parseOutput($value);
        }
        $searchResult[$resultKey][$this->_prefix.'_result_'.camelToUnderscore($key)] = $value;
      }
    }

    return $searchResult;
  }

  /**
   * Parses the search template.
   *
   * @return string
   *         The parsed search template.
   */
  private function _sendResponseSearch()
  {
    global $_LANG, $_LANG2;

    $request = new Input(Input::SOURCE_REQUEST);
    $currentSite = $this->_navigation->getSiteByID($this->site_id);
    $tplId = 'content_'.$this->_prefix;
    $this->tpl->load_tpl($tplId, 'modules/ModuleSearch.tpl');
    $this->tpl->parse_if($tplId, 'm_backend_live_mode', ConfigHelper::get('m_backend_live_mode'));
    $this->tpl->parse_if($tplId, 'm_backend_dev_mode', !ConfigHelper::get('m_backend_live_mode'));
    return $this->tpl->parsereturn($tplId, array_merge(array (
      'sh_option_current_site'         => sprintf($_LANG['sh_option_current_site'], self::getLanguageSiteLabel($currentSite)),
      'sh_option_current_site_checked' => ($this->_readOption(self::OPTION_CURRENT_SITE)) ? 'checked="checked"' : '',
      'sh_option_fulltext_checked'     => ($this->_readOption(self::OPTION_FULLTEXT)) ? 'checked="checked"' : '',
      'sh_result'                      => $this->_getSearchResult(),
      'sh_text'                        => parseOutput($this->_getSearchString(), 2),
      'sh_title'                       => $_LANG['global_main_name'],
      'main_cache_resource_version'    => ConfigHelper::get('m_cache_resource_version'),
      'main_theme'                     => ConfigHelper::get('m_backend_theme'),
    ), $_LANG2[$this->_prefix]));
  }

  /**
   * Gets the AJAX search result.
   *
   * @return string
   */
  private function _sendResponseSearchResult()
  {
    $request = new Input(Input::SOURCE_REQUEST);
    $this->_saveOption(self::OPTION_CURRENT_SITE, $request->readBool(self::OPTION_CURRENT_SITE));
    $this->_saveOption(self::OPTION_FULLTEXT, $request->readBool(self::OPTION_FULLTEXT));

    return $this->_getSearchResult();
  }
}

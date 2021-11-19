<?php

/**
 * Objects of CFunctionSeoManagement class handle the function for read and update
 * the seo fields: title, description and keywords.
 *
 * @see class.CFunctionAdditionalImageLevel.php
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Anton Jungwirth
 * @copyright (c) 2014 Q2E GmbH
 */
class CFunctionSeoManagement extends AbstractCFunction
{
  private $_fields = array();
  private $_pageId = 0;

  /**
   * @return string
   *         the function shortname, i.e. 'func', 'my_func'
   */
  public function getShortname()
  {
    return 'seomgmt';
  }

  /**
   * Checks if the function is active
   *
   * @return bool
   *         true if funtion is active, false otherwise
   */
  public function isActive()
  {
    if (in_array($this->getShortname(), $this->_modules)) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Checks if the function is available on page. Only active functions can be
   * available.
   *
   * @param NavigationPage $page
   * @return bool
   *         true if funtion is available, false otherwise
   */
  public function isAvailableOnPage(NavigationPage $page)
  {
    return $page->isRealLeaf() || $page->isArchive() || $page->isOverview() || $page->isBlog();
  }

  /**
   * Checks if the function is available for user on given site. Only
   * active functions can be available.
   *
   * @param User $user
   * @param NavigationSite $site
   * @return bool
   *         true if funtion is available, false otherwise
   */
  public function isAvailableForUser(User $user, NavigationSite $site)
  {
    $available = false;

    $active = $this->isActive();
    $permitted = $user->AvailableModule($this->getShortname(), $site->getID());

    if ($active && $permitted) {
      $available = true;
    }

    return $available;
  }

  /**
   * @param User $user
   * @param NavigationPage $page
   * @return bool
   */
  public function isAvailableForUserOnPage(User $user, NavigationPage $page)
  {
    return ($this->isAvailableForUser($user, $page->getSite()) && $this->isAvailableOnPage($page));
  }

  /**
   * @param int $pageId
   * @return $this
   */
  public function setPageId($pageId)
  {
    $this->_pageId = $pageId;
    return $this;
  }

  /**
   * @return int
   */
  public function getPageId()
  {
    if ($this->_pageId) {
      return $this->_pageId;
    }
    return $this->_navigation->getCurrentPage()->getID();
  }

  /**
   * Gets all SEO template variables.
   * @return array
   */
  public function getTemplateVars()
  {
    $this->_read();
    return array(
      'so_title'       => $this->_fields['CSEOTitle'],
      'so_description' => $this->_fields['CSEODescription'],
      'so_keywords'    => $this->_fields['CSEOKeywords'],
    );
  }


  /**
   * Updates the SEO content.
   * @return CFunctionSeoManagement
   */
  public function update()
  {
    $preparedArgs = array();
    foreach ($this->_fields as $col => $val) {
      $preparedArgs[] = "$col = '".$this->_db->escape($val)."'";
    }

    $sql = " UPDATE {$this->_tablePrefix}contentitem "
      . "    SET ".implode(', ', $preparedArgs)." "
      . " WHERE CIID = {$this->_db->escape($this->getPageId())} ";
    $this->_db->query($sql);

    return $this;
  }

  /**
   * Sets the SEO fields from POST data.
   * @param Input $post
   * @return CFunctionSeoManagement
   */
  public function setVarsFromPost(Input $post)
  {
    $this->_fields = array(
      'CSEOTitle'       => $post->readString('so_title', Input::FILTER_PLAIN),
      'CSEODescription' => $post->readString('so_description', Input::FILTER_PLAIN),
      'CSEOKeywords'    => $post->readString('so_keywords', Input::FILTER_PLAIN),
    );

    return $this;
  }

  /**
   * Reads SEO content from database and stores it into
   * the G6SeoManagement::_fields array.
   * @return CFunctionSeoManagement
   */
  private function _read()
  {
    $sql = " SELECT CSEOTitle, CSEODescription, CSEOKeywords "
      . " FROM {$this->_tablePrefix}contentitem "
      . " WHERE CIID = {$this->_db->escape($this->getPageId())} ";
    $row = $this->_db->GetRow($sql);
    $this->_fields = array(
      'CSEOTitle'       => $row['CSEOTitle'],
      'CSEODescription' => $row['CSEODescription'],
      'CSEOKeywords'    => $row['CSEOKeywords'],
    );

    return $this;
  }
}
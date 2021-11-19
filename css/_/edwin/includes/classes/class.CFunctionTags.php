<?php

/**
 * Objects of CFunctionTags class handle the tagging of contentitems within
 * taggable levels. Levels can be marked as taggable by 'taglevel' core function,
 * thus, this function requires the 'taglevel' function to be activated.
 *
 * @see class.CFunctionTagLevel.php
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class CFunctionTags extends AbstractCFunction
{
  public function getShortname()
  {
    return 'tags';
  }

  public function isActive()
  {
    if (in_array('taglevel', $this->_modules)) {
      return true;
    }
    else {
      return false;
    }
  }

  public function isAvailableOnPage(NavigationPage $page)
  {
    $available = false;

    $parent = $page->getParent();

    if ($parent && $parent->isTaggable()) {
      $available = true;
    }

    return $available;
  }

  public function isAvailableForUser(User $user, NavigationSite $site)
  {
    $available = false;

    $active = $this->isActive();
    $permitted = $user->AvailableModule('taglevel', $site->getID());

    if ($active && $permitted) {
      $available = true;
    }

    return $available;
  }
}

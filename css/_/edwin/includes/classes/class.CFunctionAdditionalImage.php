<?php

/**
 * Objects of CFunctionAdditionalImage class handle the function for adding
 * additional image to a contentitem. Levels can be marked to provide the
 * 'additionalimage' functionality to subitems by 'additionalimagelevel' core
 * function.
 *
 * @see class.CFunctionAdditionalImageLevel.php
 *
 * $LastChangedDate: 2014-03-10 11:34:35 +0100 (Mo, 10 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class CFunctionAdditionalImage extends AbstractCFunction
{
  public function getShortname()
  {
    return 'additionalimage';
  }

  public function isActive()
  {
    if (in_array($this->getShortname(), $this->_modules)) {
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

    if ($parent && $parent->isAdditionalImageLevel()) {
      $available = true;
    }

    return $available;
  }

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
}

<?php

/**
 * Objects of CFunctionForm class handle the 'form' core function /
 * module features.
 *
 * The 'form' function allows attaching lead management forms to contentitems.
 *
 * $LastChangedDate: 2014-03-14 10:48:28 +0100 (Fr, 14 Mrz 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2012 Q2E GmbH
 */
class CFunctionForm extends AbstractCFunction
{
  public function getShortname()
  {
    return 'form';
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
    $active = $this->isActive();
    $available = false;

    if (   $active
        && !$this->isExcludedForContentTypeIdOnSite($page->getContentTypeId(), $page->getSite())
    ) {
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


  /**
   * @param int $id
   *        content type id
   * @param NavigationSite $site
   * @return boolean
   */
  public function isExcludedForContentTypeIdOnSite($id, NavigationSite $site)
  {
    $config = ConfigHelper::get('m_form_contenttype_excluded', '', $site->getID());
    return is_array($config) && in_array($id, $config);
  }
}

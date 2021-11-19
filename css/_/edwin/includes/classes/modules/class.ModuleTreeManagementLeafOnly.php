<?php

/**
 * ModuleTreeManagementLeafOnly Module class
 *
 * $LastChangedDate: 2014-03-12 11:10:07 +0100 (Mi, 12 Mär 2014) $
 * $LastChangedBy: ulb $
 *
 * @package EDWIN Backend
 * @author Benjamin Ulmer
 * @copyright (c) 2011 Q2E GmbH
 */
class ModuleTreeManagementLeafOnly extends AbstractModuleTreeManagement
{
  protected $_shortname = 'treemgmtleafonly';

  protected function _setSourcePage(NavigationPage $page)
  {
    if (!$this->_user->AvailablePath($page->getDirectPath(), $page->getSite()->getID(), $page->getTree()) || $page->isRoot())
      return;
    // leaf pages only
    else if ($page->getType() == ContentType::TYPE_LOGICAL_WITH_NAV)
      return;

    $this->_sourcePage = $page;
  }
}

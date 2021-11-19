<?php

/*******************************************************************************

Classmap for autoloader.

$LastChangedDate: 2019-12-13 11:49:30 +0100 (Fr, 13 Dez 2019) $
$LastChangedBy: ulb $

@package config
@author Benjamin Ulmer
@copyright (c) 2014 Q2E GmbH

*******************************************************************************/

return array(

  /* ---------------------------------------------------------------------------
   | EDWIN CMS Backend classes
     ------------------------------------------------------------------------ */

  'ActivationClockInterface' => 'includes/classes/class.ActivationClockInterface.php',
  'ActivationLightInterface' => 'includes/classes/class.ActivationLightInterface.php',
  'AbstractCFunction' => 'includes/classes/class.AbstractCFunction.php',
  'BackendRequest' => 'includes/classes/class.BackendRequest.php',
  'CFunctionAdditionalImage' => 'includes/classes/class.CFunctionAdditionalImage.php',
  'CFunctionAdditionalImageLevel' => 'includes/classes/class.CFunctionAdditionalImageLevel.php',
  'CFunctionAdditionalText' => 'includes/classes/class.CFunctionAdditionalText.php',
  'CFunctionAdditionalTextLevel' => 'includes/classes/class.CFunctionAdditionalTextLevel.php',
  'CFunctionBlog' => 'includes/classes/class.CFunctionBlog.php',
  'CFunctionForm' => 'includes/classes/class.CFunctionForm.php',
  'CFunctionMobileSwitch' => 'includes/classes/class.CFunctionMobileSwitch.php',
  'CFunctionSeoManagement' => 'includes/classes/class.CFunctionSeoManagement.php',
  'CFunctionShare' => 'includes/classes/class.CFunctionShare.php',
  'CFunctionTaglevel' => 'includes/classes/class.CFunctionTaglevel.php',
  'CFunctionTags' => 'includes/classes/class.CFunctionTags.php',
  'CmsDiskSpace' => 'includes/classes/class.CmsDiskSpace.php',
  'ContentBase' => 'includes/classes/class.ContentBase.php',
  'ContentItem' => 'includes/classes/class.ContentItem.php',
  'ContentItemComments' => 'includes/classes/class.ContentItemComments.php',
  'ContentItemExtLinks' => 'includes/classes/class.ContentItemExtLinks.php',
  'ContentItemFiles' => 'includes/classes/class.ContentItemFiles.php',
  'ContentItemIntLinks' => 'includes/classes/class.ContentItemIntLinks.php',
  'ContentItemLogical' => 'includes/classes/class.ContentItemLogical.php',
  'ContentItemLogical_CFunctionView' => 'includes/classes/class.ContentItemLogical_CFunctionView.php',
  'ContentItemStrLinks' => 'includes/classes/class.ContentItemStrLinks.php',
  'ContentItemSubelementList' => 'includes/classes/class.ContentItemSubelementList.php',
  'ContentUser' => 'includes/classes/class.ContentUser.php',
  'Login' => 'includes/classes/class.Login.php',
  'Module' => 'includes/classes/class.Module.php',
  'ModuleSiteindex' => 'includes/classes/modules/class.ModuleSiteindexCompendium.php',
  'ModuleTypeBackend' => 'includes/classes/class.ModuleTypeBackend.php',
  'ModuleTypeBackendFactory' => 'includes/classes/class.ModuleTypeBackendFactory.php',
  'NavigationHelper' => 'includes/classes/class.NavigationHelper.php',
  'NavigationPageMover' => 'includes/classes/class.NavigationPageMover.php',
  'NavigationPageActivator' => 'includes/classes/class.NavigationPageActivator.php',
  'User' => 'includes/classes/class.User.php',

  /* ---------------------------------------------------------------------------
   | Third party classes
     ------------------------------------------------------------------------ */

  'dUnzip2' => '../tps/includes/dunzip2/dUnzip2.inc.php',
  'dZip' => '../tps/includes/dunzip2/dZip.inc.php',
  'htmlMimeMail5' => '../tps/includes/htmlmimemail5/htmlMimeMail5.php',
  'Google_Service_MyBusiness' => '../tps/includes/google/mybusiness/MyBusiness.php',
  'Illuminate\\Container\\Container' => '../tps/includes/illuminate/container/Container.php',
  'League\\Event\\AbstractEvent' => '../tps/includes/league/event/src/AbstractEvent.php',
  'League\\Event\\AbstractListener' => '../tps/includes/league/event/src/AbstractListener.php',
  'League\\Event\\BufferedEmitter' => '../tps/includes/league/event/src/BufferedEmitter.php',
  'League\\Event\\CallbackListener' => '../tps/includes/league/event/src/CallbackListener.php',
  'League\\Event\\Emitter' => '../tps/includes/league/event/src/Emitter.php',
  'League\\Event\\EmitterAwareInterface' => '../tps/includes/league/event/src/EmitterAwareInterface.php',
  'League\\Event\\EmitterAwareTrait' => '../tps/includes/league/event/src/EmitterAwareTrait.php',
  'League\\Event\\EmitterInterface' => '../tps/includes/league/event/src/EmitterInterface.php',
  'League\\Event\\EmitterTrait' => '../tps/includes/league/event/src/EmitterTrait.php',
  'League\\Event\\Event' => '../tps/includes/league/event/src/Event.php',
  'League\\Event\\EventInterface' => '../tps/includes/league/event/src/EventInterface.php',
  'League\\Event\\Generator' => '../tps/includes/league/event/src/Generator.php',
  'League\\Event\\GeneratorInterface' => '../tps/includes/league/event/src/GeneratorInterface.php',
  'League\\Event\\GeneratorTrait' => '../tps/includes/league/event/src/GeneratorTrait.php',
  'League\\Event\\ListenerAcceptor' => '../tps/includes/league/event/src/ListenerAcceptor.php',
  'League\\Event\\ListenerAcceptorInterface' => '../tps/includes/league/event/src/ListenerAcceptorInterface.php',
  'League\\Event\\ListenerInterface' => '../tps/includes/league/event/src/ListenerInterface.php',
  'League\\Event\\ListenerProviderInterface' => '../tps/includes/league/event/src/ListenerProviderInterface.php',
  'League\\Event\\OneTimeListener' => '../tps/includes/league/event/src/OneTimeListener.php',
  'ZipStream\\Exception\\FileNotFoundException' => '../tps/includes/maennchen/zipstream-php/src/Exception/FileNotFoundException.php',
  'ZipStream\\Exception\\FileNotReadableException' => '../tps/includes/maennchen/zipstream-php/src/Exception/FileNotReadableException.php',
  'ZipStream\\Exception\\InvalidOptionException' => '../tps/includes/maennchen/zipstream-php/src/Exception/InvalidOptionException.php',
  'ZipStream\\Exception' => '../tps/includes/maennchen/zipstream-php/src/Exception.php',
  'ZipStream\\ZipStream' => '../tps/includes/maennchen/zipstream-php/src/ZipStream.php',

  /* ---------------------------------------------------------------------------
   |
   | PROJECTS
   | Put project specific classes here
   |
     ------------------------------------------------------------------------ */
);
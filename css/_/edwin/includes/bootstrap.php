<?php

/*******************************************************************************

Bootstrap file.
Include whenever EDWIN CMS backend classes and functionality is required.

$LastChangedDate: 2020-02-14 13:40:11 +0100 (Fr, 14 Feb 2020) $
$LastChangedBy: ulb $

@package config
@author Benjamin Ulmer
@copyright (c) 2014 Q2E GmbH

*******************************************************************************/

// set charset UTF-8 for multibyte PHP functions and throw errors if setting
// new values failed

if (!mb_internal_encoding('UTF-8')) {
  trigger_error('Could not set the multibyte internal encoding to UTF-8. Please ensure a valid PHP installation.', E_USER_ERROR);
}

if (!mb_regex_encoding('UTF-8')) {
  trigger_error('Could not set the multibyte regex encoding to UTF-8. Please ensure a valid PHP installation.', E_USER_ERROR);
}

/* -----------------------------------------------------------------------------
 | Autoloading
   -------------------------------------------------------------------------- */

$basePath = realpath(dirname(dirname(__FILE__))) . '/';

// third party libraries with...

// (1) vendor (composer) autoloading
// only available if `composer install` was called
if (is_file($basePath . '../tps/includes/vendor/autoload.php')) {
  require_once $basePath . '../tps/includes/vendor/autoload.php';
}

// (2) with custom autoloading
// Uncomment when using MailjetService ( required PHP >= 5.4 )
// require_once $basePath . 'tps/includes/mailjet/vendor/autoload.php';

// core class autoloading

require_once $basePath . '../includes/core/_autoload/autoload.php';

$classmap = include $basePath . 'includes/classmap.php';

$loader = new ClassmapAutoLoader($classmap);
$loader->setDirectory($basePath);
spl_autoload_register(array($loader, 'load'));

$loader = new DirectoryAutoLoader(array(
    $basePath . 'includes/classes/content_types',
    $basePath . 'includes/classes/modules',
));
spl_autoload_register(array($loader, 'load'));

// psr-4 based autoloading
$loader = new Psr4AutoLoader;
$loader->addNamespace('Ext', $basePath . '../includes/ext');
$loader->register();

/* -----------------------------------------------------------------------------
 | Additional manual includes
   -------------------------------------------------------------------------- */

// config requires special includes and a call to ConfigHelper::load() afterwards
include $basePath . 'config.php';
$_CONFIG['INCLUDE_DIR'] = $basePath;
ConfigHelper::load();

// set error reporting as soon as possible
error_reporting((int)ConfigHelper::get('DEBUG_PHP'));

// set project specific session save path
if (ConfigHelper::get('m_session_save_path')) {
  ini_set('session.save_path', $basePath . '../' . rtrim(ConfigHelper::get('m_session_save_path'), '/'));
}

// further scripts
include $basePath . '../includes/core/functions.php';
include $basePath . 'includes/functions/main.inc.php';
include $basePath . 'includes/db.inc.php';
include $basePath . '../tps/includes/kint-php/kint/kint.phar';
Kint::$enabled_mode = app_debug(); // @see http://kint-php.github.io/kint/

/* -----------------------------------------------------------------------------
 | IoC container setup
   -------------------------------------------------------------------------- */

// setup
$container = new Illuminate\Container\Container;
Container::setContainer($container);

// init bindings
// 1. bind existing objects
Container::instance('Db', $db);
Container::alias('Db', 'db');

// 2. bind singletons, that are initialized on request only once

Container::singleton('Core\Http\Input', function () {
  return Core\Http\Input::getInstance();
});

Container::singleton('Core\Http\ResponseCode', function () {
  return Core\Http\ResponseCode::getInstance();
});

Container::singleton('ContentItemLogService', function ($container) {
   return new ContentItemLogService($container->make('db'), ConfigHelper::get('table_prefix'));
});

Container::singleton('Core\Logging\Simple\Loggers\FileLogger', function ($container) {
  return new Core\Logging\Simple\Loggers\FileLogger(storage_path() . 'simple.log');
});

Container::singleton('Core\Logging\Simple\Loggers\DbLogger', function ($container) {
  return new Core\Logging\Simple\Loggers\DbLogger($container->make('db'));
});

Container::singleton('Core\Logging\Simple\NullLogger', function ($container) {
  return new Core\Logging\Simple\Loggers\NullLogger;
});

Container::singleton('Core\Logging\Simple\Service', function ($container) {
  $config = ConfigHelper::get('m_logging');
  $config = isset($config['Core\Logging\Simple\Service']) ? $config['Core\Logging\Simple\Service'] : array();

  $service = new Core\Logging\Simple\Service(
    $container->make('Core\Logging\Simple\Loggers\NullLogger'),
    isset($config['loggables']) ? $config['loggables'] : array()
  );

  if (   isset($config['enabled']) && $config['enabled']
      && isset($config['loggers']) && is_array($config['loggers'])
  ) {
    $chainLogger = $container->make('Core\Logging\Simple\Loggers\ChainLogger');

    foreach ($config['loggers'] as $className => $options) {
      $logger = $container->make($className);

      if (isset($options['min_level'])) {
        $logger->setMinLevel($options['min_level']);
      }

      $chainLogger->addLogger($logger);
    }

    $service->setLogger($chainLogger);
  }

  return $service;
});

Container::singleton('Core\Services\Caching\InterfaceCacheService', function ($container) {
  return new Core\Services\Caching\Simple\SimpleCacheService(
    new CacheSimple($container->make('db'), ConfigHelper::get('table_prefix'))
  );
});

Container::singleton('Core\Services\ExtendedData\ExtendedDataRepository', function ($container) {
  return new Core\Services\ExtendedData\ExtendedDataRepository(
    $container->make('db'),
    ConfigHelper::get('table_prefix')
  );
});

Container::singleton('Core\Services\ExtendedData\HandlerFactory', function ($container) {
  return new Core\Services\ExtendedData\HandlerFactory(
    $container->make('db'),
    ConfigHelper::get('table_prefix'),
    Container::make('Core\Services\ExtendedData\ExtendedDataRepository'),
    ConfigHelper::get('m_extended_data_handlers')
  );
});

Container::singleton('Core\Services\ExtendedData\ExtendedDataService', function () {
  return new Core\Services\ExtendedData\ExtendedDataService(
    Container::make('Core\Services\ExtendedData\HandlerFactory'),
    Container::make('Core\Services\ExtendedData\ExtendedDataRepository')
  );
});

Container::singleton('ModuleTypeBackendFactory', function ($container) {
  return new ModuleTypeBackendFactory($container->make('db'), ConfigHelper::get('table_prefix'));
});

Container::singleton('CmsBugtracking', function () {
  return new CmsBugtracking();
});

Container::singleton('League\Events\EmitterInterface', function () {
  return new League\Event\Emitter;
});

Container::singleton('Session', function () {
  return new Session(ConfigHelper::get('m_session_name_backend'));
});

Container::singleton('Navigation', function ($container) {
  return Navigation::getInstance($container->make('db'), ConfigHelper::get('table_prefix'));
});

/* -----------------------------------------------------------------------------
 | Edwin framework setup
   -------------------------------------------------------------------------- */

if (function_exists("date_default_timezone_set")) {
  date_default_timezone_set(ConfigHelper::get('m_default_timezone'));
}

CmsImageFactory::setQuality(ConfigHelper::get('m_image_quality'));
CmsImageFactory::setFixOrientation(ConfigHelper::get('m_image_fix_orientation'));

// htmlMimeMail5 setup for enabled application logging
if (ConfigHelper::get('m_logging')) {
  $config = ConfigHelper::get('m_logging');

  if (   isset($config['htmlMimeMail5'])
      && isset($config['htmlMimeMail5']['enabled'])
      && $config['htmlMimeMail5']['enabled']
  ) {

    htmlMimeMail5::setLogger(function ($success, $args) use ($config) {
      if ($success) {
        $level = $config['htmlMimeMail5']['level'] ?: 'debug';
      }
      else {
        $level = 'error';
      }

      app_log()->log($level, new \Core\Logging\Simple\Loggables\Mail($args, 'mail.htmlMimeMail5'));
    });
  }
}

/* -----------------------------------------------------------------------------
 | Project specific application setup stuff
   -------------------------------------------------------------------------- */

// https://edwin.q2e.at/wiki/index.php?title=Features/Caching
// When using caching features, ensure fresh new cache values
// ed_cache()->refresh();
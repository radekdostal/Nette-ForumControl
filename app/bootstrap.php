<?php
 /**
  * Nette
  */
 require_once(LIBS_DIR.'/Nette/loader.php');

 /**
  * Configure application
  */
 NEnvironment::loadConfig();

 /**
  * Prepare & setup
  */
 NDebug::enable(NDebug::DETECT, LOG_DIR, 'john.doe@yourdomain.com');

 $application = NEnvironment::getApplication();

 $application->errorPresenter = 'Error';

 if (NEnvironment::isProduction())
   $application->catchExceptions = TRUE;

 /**
  * Router
  */
 $router = $application->getRouter();

 if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()))
 {
   // Jednosměrná routa
   $router[] = new NRoute('index.php', 'Default:', NRoute::ONE_WAY);
   // Dvousměrná routa s cool-url tvarem
   $router[] = new NRoute('<presenter>/<action>[/<id [0-9]+>][/<id2 [0-9]+>]', 'Default:');
 }
 else
   $router[] = new NSimpleRouter('Default:default');

 /**
  * Run!
  */
 $application->run();
?>
<?php
 use Nette\Configurator,
     Nette\Diagnostics\Debugger,
     Nette\Application\Routers\Route;

 // Nette Framework
 require_once(LIBS_DIR.'/Nette/loader.php');

 // Enable Nette Debugger for error visualization & logging
 Debugger::$strictMode = TRUE;
 Debugger::enable(Debugger::DETECT, LOG_DIR, 'you@yourdomain.com');

 // Load configuration from config.neon file
 $configurator = new Configurator();
 $configurator->loadConfig(dirname(__FILE__).'/config.neon');

 // Setup router
 $router = $configurator->container->router;

 if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()))
 {
   // Jednosměrná routa
   $router[] = new Route('index.php', 'Default:', Route::ONE_WAY);
   // Dvousměrná routa s cool-url tvarem
   $router[] = new Route('<presenter>/<action>[/<id [0-9]+>][/<id2 [0-9]+>]', 'Default:');
 }
 else
   $router[] = new SimpleRouter('Default:default');

 // Configure and run the application!
 $application = $configurator->container->application;
 $application->errorPresenter = 'Error';

 if ($configurator->container->params['productionMode'])
   $application->catchExceptions = TRUE;

 $application->run();
?>
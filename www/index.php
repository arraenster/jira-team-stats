<?php

define( 'START_TIME',           microtime(true) );
define( 'ROOT_PATH',            realpath(__DIR__.'/../') );

define( 'WEBSITE_ALIAS',         'jiraapi' );

error_reporting(E_ALL);

require '../vendor/autoload.php';

try {

	/**
	 * Read the configuration
	 */
	$config = require __DIR__ . "/../app/config/config.php";

	/**
	 * Include loader
	 */
	require __DIR__ . '/../app/config/loader.php';

	/**
	 * Include services
	 */
	require __DIR__ . '/../app/config/services.php';

    /**
     * Debug
     */
    $debug = new \Phalcon\Debug();
    $debug->listen();

	/**
	 * Handle the request
	 */
	$application = new \Phalcon\Mvc\Application();
	$application->setDI($di);
	echo $application->handle()->getContent();

} catch (Phalcon\Exception $e) {
	echo $e->getMessage();
} catch (PDOException $e){
	echo $e->getMessage();
}
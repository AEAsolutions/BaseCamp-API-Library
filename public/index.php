<?php
	// Path to the public folder
	define('PUBLICPATH', pathinfo(__FILE__, PATHINFO_DIRNAME).'/');

	// Path to the root
	define('ROOTPATH', pathinfo(PUBLICPATH, PATHINFO_DIRNAME).'/');

	// load required files
	require_once(ROOTPATH.'application/config.php');
	require_once(ROOTPATH.'application/helper.php');
	require_once(ROOTPATH.'application/models/baseModel.php');
	require_once(ROOTPATH.'application/models/todoListItemModel.php');
	require_once(ROOTPATH.'application/models/messagesModel.php');
	require_once(ROOTPATH.'libs/BaseCampApiClassic.php');

	define('CONFIG', serialize($config));

	// set headers and mb encoding
	header('Content-type: text/html; charset=utf-8');
	mb_internal_encoding('UTF-8');
	ini_set('register_argc_argv', true);

	// load controller class
	require_once(ROOTPATH.'application/controller.php');

	// start the application
	$controller = new Controller();
	$controller->index();
?>
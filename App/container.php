<?php
// Get container
$container = $app->getContainer();

$container['config'] = function ($container) use ($config) {
	return \App\Config::get();
};

$container['mysql'] = function ($container) {
	$pdo = new \Simplon\Mysql\PDOConnector(
		$container->config['mysql']['host'], // server
		$container->config['mysql']['username'],     // user
		$container->config['mysql']['password'],      // password
		$container->config['mysql']['database']   // database
	);

	$pdoConn = $pdo->connect('utf8', []); // charset, options

	$dbConn = new \Simplon\Mysql\Mysql($pdoConn);

	return $dbConn;
};

$container['uploader'] = function ($container){
	return new \Uploader\Uploader($container->config['files_path']);
};
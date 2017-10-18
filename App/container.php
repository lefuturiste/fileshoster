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
	return new \Uploader\Uploader(	$container->config['files_path']);
};

$container['view'] = function ($container) use ($app) {
	$dir = dirname(__DIR__);
	$view = new \Slim\Views\Twig($dir . '/App/views', [
		'cache' => false //$dir . 'tmp/cache' OR '../tmp/cache'
	]);
	$twig = $view->getEnvironment();
//	$twig->addExtension(new \App\TwigExtension($container));


	$engine = new \Aptoma\Twig\Extension\MarkdownEngine\MichelfMarkdownEngine();
	$twig->addExtension(new \Aptoma\Twig\Extension\MarkdownExtension($engine));
	// Instantiate and add Slim specific extension
	$basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
	$view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

	return $view;
};

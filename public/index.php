<?php
/*
* Application's router
* Using php7, Slim3
*
* @author lefuturiste <contact@lefuturiste.fr>
* @version 1.0
*
**/
require '../vendor/autoload.php';

$config = \App\Config::get();

$app = new \Slim\App([
    'settings' => [
    	'displayErrorDetails' => $config['app_debug']
	]
]);

require '../App/container.php';

$app->get('/', \App\Controllers\ApiController::class . ':getHome')->setName('home');
$app->get('/{uuid}', \App\Controllers\FilesApiController::class . ':pretty')->setName('files.public_infos');
$app->get('/{uuid}/download', \App\Controllers\FilesApiController::class . ':download')->setName('files.public_download');
$app->get('/{uuid}.{extension}', \App\Controllers\FilesApiController::class . ':download');

$app->group('/api', function (){
	$this->group('/files', function (){
		$this->post('/', \App\Controllers\FilesApiController::class . ':store')->setName('files.store');
		$this->get('/{uuid}', \App\Controllers\FilesApiController::class . ':show')->setName('files.show');
		$this->get('/{uuid}/view', \App\Controllers\FilesApiController::class . ':view')->setName('files.view');
		$this->get('/{uuid}/download', \App\Controllers\FilesApiController::class . ':download')->setName('files.download');
		$this->delete('/{uuid}', \App\Controllers\FilesApiController::class . ':destroy')->setName('files.destroy');
	});
})->add(new \App\Middlewares\Authorisation($container));

$app->run();
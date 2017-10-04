<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiController {
	public function getHome(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $response->withJson("This is main page of your application");
	}
}
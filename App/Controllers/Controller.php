<?php

namespace App\Controllers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Controller
{
	public $container;
	public $db;

	public function __construct($container)
	{
		$this->db = $container->mysql;
		$this->container = $container;
	}
}
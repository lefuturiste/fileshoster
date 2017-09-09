<?php

namespace App\Controllers;

use App\Time;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Uploader\Uploader;

class FilesApiController extends Controller
{
	public function store(ServerRequestInterface $request, ResponseInterface $response)
	{
		$uuid = uniqid();
		$uploader = $this->container->uploader;

		$upload = $uploader
			->with($_FILES['file'])
			->setFilename($uuid)
			->setOverwrite(true)
			->setCreateDir(true)
			->setExtension(function ($upload) {
				return strtolower($upload->getExtension());
			});

		try {
			//save file
			$upload->save();

			//generated file name
			$name = $upload->getFilename() . '.' . $upload->getExtension();

			//save in database
			$this->db->insert('files', [
				'uuid' => $uuid,
				'file_name' => $name,
				'extension' => $upload->getExtension(),
				'path' => $upload->getDestination(),
				'created_at' => Time::now()
			]);

			return $response->withJson([
				'success' => true,
				'created' => true,
				'uuid' => $uuid,
				'extension' => $upload->getExtension(),
				'name' => $name,
				'path' => $upload->getDestination(),
			]);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public function file(ServerRequestInterface $request, ResponseInterface $response, $args)
	{
		//verify is file is good
		$file = $this->db->fetchRow('SELECT * FROM files WHERE uuid = :uuid', [
			'uuid' => $args['uuid']
		]);
		if (!empty($file)) {
			$newStream = new \GuzzleHttp\Psr7\LazyOpenStream($this->container->config['files_path'] . $file['path'], 'r');

			return $response->withBody($newStream);
		}else{
			return $this->container['notFoundHandler']($request, $response);
		}
	}

	public function show(ServerRequestInterface $request, ResponseInterface $response, $args)
	{
		//verify if file exist
		$file = $this->db->fetchRow('SELECT * FROM files WHERE uuid = :uuid', [
			'uuid' => $args['uuid']
		]);
		if (!empty($file)) {
			return $response->withJson([
				'uuid' => $file['uuid'],
				'name' => $file['file_name'],
				'extension' => $file['extension'],
				'path' => $file['path'],
				'created_at' => $file['created_at']
			]);
		}else{
			return $this->container['notFoundHandler']($request, $response);
		}
	}

	public function destroy(ServerRequestInterface $request, ResponseInterface $response, $args)
	{
		//verify if file exist
		$file = $this->db->fetchRow('SELECT uuid, path FROM files WHERE uuid = :uuid', [
			'uuid' => $args['uuid']
		]);
		if (!empty($file)) {
			//do delete in bdd
			$this->db->delete('files', [
				'uuid' => $args['uuid']
			]);
			//do delete in file
			unlink($file['path']);

			return $response->withJson([
				'success' => true,
				'deleted' => true
			]);
		}else{
			return $this->container['notFoundHandler']($request, $response);
		}
	}
}
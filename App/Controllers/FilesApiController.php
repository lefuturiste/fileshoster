<?php

namespace App\Controllers;

use App\Time;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Uploader\Uploader;

class FilesApiController extends Controller
{
	/**
	 * Store file
	 *
	 * request: POST
	 * body:
	 * - file
	 * - private (true|false)
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return mixed
	 */
	public function store(ServerRequestInterface $request, ResponseInterface $response)
	{
		if (isset($request->getParsedBody()['private'])){
			$private = $request->getParsedBody()['private'];
		}else{
			$private = false;
		}

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
				'path' => str_replace('/', '', $upload->getDestination()),
				'private' => $private,
				'user' => $_SERVER['PHP_AUTH_USER'],
				'created_at' => Time::now()
			]);

			return $response->withJson([
				'success' => true,
				'created' => true,
				'uuid' => $uuid,
				'user' => $_SERVER['PHP_AUTH_USER'],
				'extension' => $upload->getExtension(),
				'name' => $name,
				'path' => str_replace('/', '', $upload->getDestination()),
				'url' => $this->container->config['base_url'] . '/' . $uuid
			]);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Get true file
	 *
	 * request: GET
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param $args
	 * @return static
	 */
	public function file(ServerRequestInterface $request, ResponseInterface $response, $args)
	{
		//verify is file is good
		$file = $this->db->fetchRow('SELECT * FROM files WHERE uuid = :uuid', [
			'uuid' => $args['uuid']
		]);
		if (!empty($file)) {
			//if the file is private
			if ($file['private']) {
				return $response->withStatus(401)->withJson([
					'success' => false,
					'error' => 'Not authorised'
				]);
			}
			$newStream = new \GuzzleHttp\Psr7\LazyOpenStream($this->container->config['files_path'] . $file['path'], 'r');

			//get file type
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$fileType = finfo_file($finfo, $this->container->config['files_path'] . $file['path']);
			finfo_close($finfo);

			return $response->withBody($newStream)->withHeader('Content-Type', $fileType);
		}else{
			return $this->container['notFoundHandler']($request, $response);
		}
	}

	public function download(ServerRequestInterface $request, ResponseInterface $response, $args)
	{
		//verify is file is good
		$file = $this->db->fetchRow('SELECT * FROM files WHERE uuid = :uuid', [
			'uuid' => $args['uuid']
		]);
		if (!empty($file)) {
			//if the file is private
			if ($file['private']) {
				if (isset($_SERVER['PHP_AUTH_USER'])){
					if ($file['user'] != $_SERVER['PHP_AUTH_USER']){
						return $response->withStatus(401)->withJson([
							'success' => false,
							'error' => 'Not authorised'
						]);
					}
				}else{
					return $response->withStatus(401)->withJson([
						'success' => false,
						'error' => 'Not authorised'
					]);
				}
			}
			$newStream = new \GuzzleHttp\Psr7\LazyOpenStream($this->container->config['files_path'] . $file['path'], 'r');

			//get file type
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$fileType = finfo_file($finfo, $this->container->config['files_path'] . $file['path']);
			finfo_close($finfo);

			return $response->withBody($newStream)->withHeader('Content-Type', $fileType);
		}else{
			return $this->container['notFoundHandler']($request, $response);
		}
	}

	/***
	 * Get information about a file
	 *
	 * request: GET
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param $args
	 * @return mixed
	 */
	public function show(ServerRequestInterface $request, ResponseInterface $response, $args)
	{
		//verify if file exist
		$file = $this->db->fetchRow('SELECT * FROM files WHERE uuid = :uuid', [
			'uuid' => $args['uuid']
		]);
		if (!empty($file)) {
			//if the file is private
			if ($file['private'] AND $file['user'] != $_SERVER['PHP_AUTH_USER']){
				return $response->withStatus(401)->withJson([
					'success' => false,
					'error' => 'Not authorised'
				]);
			}
			return $response->withJson([
				'uuid' => $file['uuid'],
				'name' => $file['file_name'],
				'url' => $this->container->config['base_url'] . '/' . $file['uuid'],
				'extension' => $file['extension'],
				'path' => $file['path'],
				'created_at' => $file['created_at']
			]);
		}else{
			return $this->container['notFoundHandler']($request, $response);
		}
	}

	/**
	 * Delete a file
	 *
	 * request: DELETE
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param $args
	 * @return mixed
	 */
	public function destroy(ServerRequestInterface $request, ResponseInterface $response, $args)
	{
		//verify if file exist and if the file is owned by user
		$file = $this->db->fetchRow('SELECT uuid, path FROM files WHERE uuid = :uuid AND user = :user', [
			'uuid' => $args['uuid'],
			'user' => $_SERVER['PHP_AUTH_USER']
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
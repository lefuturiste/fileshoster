<?php
return [
	'app_debug' => getenv('APP_DEBUG'),
	'base_url' => getenv('BASE_URL'),
	'files_path' => getenv('FILES_PATH'),
	'allowed_files' => [
		'application/octet-stream',
		'image/jpeg',
		'text/plain',
		'text/html',
		'image/png',
		'image/svg+xml',
		'application/pdf',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/zip',
		'audio/mpeg',
		'image/vnd.adobe.photoshop'
	]
];
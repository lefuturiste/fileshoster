<?php
return [
	'app_debug' => getenv('APP_DEBUG'),
	'files_path' => '../files',
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
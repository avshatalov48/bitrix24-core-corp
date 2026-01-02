<?php

return [
	'value' => [
		'namespaces' => [
			'\\Bitrix\\Tasks\\Rest\\Controllers' => 'api',
			'\\Bitrix\\Tasks\\Scrum\\Controllers' => 'scrum',
			'\\Bitrix\\Tasks\\Flow\\Controllers' => 'flow',
			'\\Bitrix\\Tasks\\Deadline\\Controllers' => 'deadline',
			'\\Bitrix\\Tasks\\V2\\Infrastructure\\Controller' => 'v2',
		],
		'defaultNamespace' => '\\Bitrix\\Tasks\\Rest\\Controllers',
		'restIntegration' => [
			'enabled' => true,
		],
	],
	'readonly' => true,
];
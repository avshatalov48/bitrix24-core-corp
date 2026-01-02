<?php

return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\Baas\\Controller',
			'restIntegration' => [
				'enabled' => true
			],
			'namespaces' => [
				'\\Bitrix\\Baas\\Controller\\ServerPort' => 'Port',
			],
		],
		'readonly' => true
	],
/*	'routing' => [
		'value' => [
			'config' => ['Proxy.php']
		],
	]*/
];

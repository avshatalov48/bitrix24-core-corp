<?php
return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\Rpa\\Controller' => 'api',
			],
			'defaultNamespace' => '\\Bitrix\\Rpa\\Controller',
			'restIntegration' => [
				'enabled' => true,
			],
		],
		'readonly' => true,
	],
	'userField' => [
		'value' => [
			'access' => '\\Bitrix\\Rpa\\UserField\\UserFieldAccess',
		],
	],
];
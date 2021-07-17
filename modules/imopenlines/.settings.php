<?php
return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\ImOpenLines\\Controller',
		],
		'readonly' => true,
	],
	'userField' => [
		'value' => [
			'access' => '\\Bitrix\\ImOpenLines\\UserField\\Access',
		],
	],
	'services' => [
		'value' => [
			'imopenlines.config' => [
				'className' => '\\Bitrix\\ImOpenLines\\Config',
			],
		],
		'readonly' => true,
	],
];
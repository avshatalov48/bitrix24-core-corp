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
			'ImOpenLines.Config' => [
				'className' => '\\Bitrix\\ImOpenLines\\Config',
			],
			'ImOpenLines.Services.SessionManager' => [
				'className' => '\\Bitrix\\ImOpenLines\\Services\\SessionManager',
			],
			'ImOpenLines.Services.Message' => [
				'className' => '\\Bitrix\\ImOpenLines\\Services\\Message',
			],
			'ImOpenLines.Services.ChatDispatcher' => [
				'className' => '\\Bitrix\\ImOpenLines\\Services\\ChatDispatcher',
			],
		],
		'readonly' => true,
	],
];
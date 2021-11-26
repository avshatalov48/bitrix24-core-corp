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
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'imopenlines-crm-form',
					'provider' => [
						'moduleId' => 'imopenlines',
						'className' => '\\Bitrix\\ImOpenlines\\Integrations\\UI\\EntitySelector\\CrmFormProvider',
					],
				]
			],
			'extensions' => ['imopenlines.entity-selector']
		],
		'readonly' => true,
	],
];
<?php
return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\ImOpenLines\\V2\\Controller' => 'v2',
			],
			'defaultNamespace' => '\\Bitrix\\ImOpenLines\\Controller',
			'restIntegration' => [
				'enabled' => true,
			]
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
				'className' => \Bitrix\ImOpenLines\Config::class,
			],
			'ImOpenLines.Services.SessionManager' => [
				'className' => \Bitrix\ImOpenLines\Services\SessionManager::class,
			],
			'ImOpenLines.Services.Message' => [
				'className' => \Bitrix\ImOpenLines\Services\Message::class,
			],
			'ImOpenLines.Services.ChatDispatcher' => [
				'className' => \Bitrix\ImOpenLines\Services\ChatDispatcher::class,
			],
			'ImOpenLines.Services.Tracker' => [
				'className' => \Bitrix\ImOpenLines\Tracker::class,
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
						'className' => \Bitrix\ImOpenlines\Integrations\UI\EntitySelector\CrmFormProvider::class,
					],
				],
				[
					'entityId' => 'imol-chat',
					'provider' => [
						'moduleId' => 'imopenlines',
						'className' => \Bitrix\ImOpenlines\Integrations\UI\EntitySelector\ChatProvider::class,
					],
				],
				[
					'entityId' => 'imopenlines-recent-v2',
					'provider' => [
						'moduleId' => 'imopenlines',
						'className' => \Bitrix\ImOpenLines\V2\Integration\UI\EntitySelector\RecentProvider::class,
					],
				],
			],
			'extensions' => ['imopenlines.entity-selector']
		],
		'readonly' => true,
	],
];
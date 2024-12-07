<?php

use Bitrix\AI\Integration\Ui\EntitySelector\PromptCategoriesProvider;

return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\AI\\Controller' => 'api'
			],
			'defaultNamespace' => '\\Bitrix\\AI\\Controller',
		],
		'readonly' => true,
	],
	'aiproxy' => [
		'value' => [
			'serverListEndpoint' => 'https://ai-proxy.bitrix.info/settings/config.json',
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'prompt-category',
					'provider' => [
						'moduleId' => 'ai',
						'className' => PromptCategoriesProvider::class,
					],
				],
			'extensions' => ['ai.entity-selector'],
			],
		],
		'readonly' => true,
	]
];

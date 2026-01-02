<?php

use Bitrix\AI\Integration\Ui\EntitySelector\PromptCategoriesProvider;
use Bitrix\Main\License\UrlProvider;

$domain = (new UrlProvider())->getTechDomain();
$serverListEndpoint = "https://ai-proxy.{$domain}/settings/config.json";

return [
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\AI\\Controller' => 'api',
			],
			'defaultNamespace' => '\\Bitrix\\AI\\Controller',
		],
		'readonly' => true,
	],
	'aiproxy' => [
		'value' => [
			'serverListEndpoint' => $serverListEndpoint,
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
	],
];

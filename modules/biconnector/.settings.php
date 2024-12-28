<?php

use Bitrix\BIConnector\Integration\UI\EntitySelector\ExternalConnectionProvider;
use Bitrix\BIConnector\Integration\UI\EntitySelector\ExternalTableProvider;
use Bitrix\BIConnector\Integration\UI\EntitySelector\SupersetDashboardProvider;
use Bitrix\BIConnector\Integration\UI\EntitySelector\SupersetDashboardTagProvider;
use Bitrix\BIConnector\Integration\UI\EntitySelector\SupersetScopeProvider;

return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\BIConnector\\Controller',
			'restIntegration' => [
				'enabled' => true,
			],
		],
		'readonly' => true,
	],
	'ui.uploader' => [
		'value' => [
			'allowUseControllers' => true,
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'biconnector-superset-dashboard',
					'provider' => [
						'moduleId' => 'biconnector',
						'className' => SupersetDashboardProvider::class,
					],
				],
				[
					'entityId' => 'biconnector-superset-dashboard-tag',
					'provider' => [
						'moduleId' => 'biconnector',
						'className' => SupersetDashboardTagProvider::class,
					],
				],
				[
					'entityId' => 'biconnector-superset-scope',
					'provider' => [
						'moduleId' => 'biconnector',
						'className' => SupersetScopeProvider::class,
					],
				],
				[
					'entityId' => 'biconnector-external-connection',
					'provider' => [
						'moduleId' => 'biconnector',
						'className' => ExternalConnectionProvider::class,
					],
				],
				[
					'entityId' => 'biconnector-external-table',
					'provider' => [
						'moduleId' => 'biconnector',
						'className' => ExternalTableProvider::class,
					],
				],
			],
			'extensions' => ['biconnector.entity-selector'],
		],
		'readonly' => true,
	],
];

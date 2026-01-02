<?php

use Bitrix\TasksMobile\Integration\UI\EntitySelector\EditableTaskProvider;

return [
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\TasksMobile\\Controller',
			'restIntegration' => [
				'enabled' => true,
			],
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'editable_task',
					'provider' => [
						'moduleId' => 'tasksmobile',
						'className' => EditableTaskProvider::class,
					],
				],
			],
		],
		'readonly' => true,
	],
	'extensions' => ['tasks.entity-selector'],
];

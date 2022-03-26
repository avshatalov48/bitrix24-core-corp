<?php
return array(
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\Tasks\\Rest\\Controllers' => 'api',
				'\\Bitrix\\Tasks\\Scrum\\Controllers' => 'scrum',
			],
			'defaultNamespace' => '\\Bitrix\\Tasks\\Rest\\Controllers',
			'restIntegration' => [
				'enabled'=>true
			],
		],
		'readonly' => true,
	],
	'ui.entity-selector' => [
		'value' => [
			'entities' => [
				[
					'entityId' => 'task',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\TaskProvider',
					],
				],
				[
					'entityId' => 'task-tag',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\TaskTagProvider',
					],
				],
				[
					'entityId' => 'task-template',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\TaskTemplateProvider',
					],
				],
			],
			'extensions' => ['tasks.entity-selector'],
		],
		'readonly' => true,
	],
);
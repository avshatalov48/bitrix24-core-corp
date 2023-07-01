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
				[
					'entityId' => 'scrum-user',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\ScrumUserProvider',
					],
				],
				[
					'entityId' => 'sprint-selector',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\SprintSelectorProvider',
					],
				],
				[
					'entityId' => 'epic-selector',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\EpicSelectorProvider',
					],
				],
				[
					'entityId' => 'template-tag',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\TemplateTagProvider',
					],
				],
			],
			'extensions' => ['tasks.entity-selector'],
		],
		'readonly' => true,
	],
);
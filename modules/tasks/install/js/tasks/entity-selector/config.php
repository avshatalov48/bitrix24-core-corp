<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\UI\EntitySelector\TaskTemplateProvider;

if (!Loader::includeModule('tasks'))
{
	return [];
}

return [
	'js' => 'dist/tasks-entity-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
	],
	'skip_core' => false,
	'settings' => [
		'entities' => [
			[
				'id' => 'task',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
				],
			],
			[
				'id' => 'task-with-id',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
				],
			],
			[
				'id' => 'task-tag',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'avatar' => '/bitrix/js/tasks/entity-selector/src/images/default-tag.svg',
							'badgesOptions' => [
								'fitContent' => true,
								'maxWidth' => 100,
							],
						],
					],
				],
			],
			[
				'id' => 'flow',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'supertitle' => Loc::getMessage('TASKS_ENTITY_SELECTOR_FLOW_SUPER_TITLE'),
							'avatar' => '/bitrix/js/tasks/flow/images/flow.svg',
						],
					],
				],
			],
			[
				'id' => 'task-template',
				'options' => [
					'dynamicLoad' => true,
					'dynamicSearch' => true,
					'itemOptions' => [
						'default' => [
							'link' => TaskTemplateProvider::getTemplateUrl(),
							'linkTitle' => TaskTemplateProvider::getTemplateLinkTitle(),
						],
					],
				],
			],
		],
	],
];
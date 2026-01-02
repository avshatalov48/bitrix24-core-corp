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
					'itemOptions' => [
						'default' => [
							'avatar' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBjbGlwLXBhdGg9InVybCgjY2xpcDBfOTIzNF8zMzE1NikiPjxyZWN0IHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0iIzMwQUZGRiIvPjxwYXRoIGQ9Ik0yMC41MzY5IDguNDM5MzNDMjIuMjA3MSA4LjQzOTQ0IDIzLjU2MTMgOS43OTM0OSAyMy41NjEzIDExLjQ2MzdWMjAuNTM3QzIzLjU2MTEgMjIuMjA3MSAyMi4yMDcgMjMuNTYxMyAyMC41MzY5IDIzLjU2MTRIMTEuNDYzNkM5Ljc5MzQxIDIzLjU2MTQgOC40MzkzOSAyMi4yMDcyIDguNDM5MjEgMjAuNTM3VjExLjQ2MzdDOC40MzkyMSA5Ljc5MzQyIDkuNzkzMyA4LjQzOTMzIDExLjQ2MzYgOC40MzkzM0gyMC41MzY5Wk0xMS40NjM2IDkuODUwNDZDMTAuNTcyOCA5Ljg1MDQ2IDkuODUwMzQgMTAuNTcyOSA5Ljg1MDM0IDExLjQ2MzdWMjAuNTM3QzkuODUwNTIgMjEuNDI3NyAxMC41NzI5IDIyLjE0OTMgMTEuNDYzNiAyMi4xNDkzSDIwLjUzNjlDMjEuNDI3NSAyMi4xNDkyIDIyLjE1IDIxLjQyNzYgMjIuMTUwMSAyMC41MzdWMTEuNDYzN0MyMi4xNTAxIDEwLjU3MyAyMS40Mjc2IDkuODUwNTcgMjAuNTM2OSA5Ljg1MDQ2SDExLjQ2MzZaTTE4LjUwOTUgMTMuNTAwOUMxOC43NzYgMTMuMjE2NyAxOS4yMjIzIDEzLjIwMjMgMTkuNTA2NiAxMy40Njg2QzE5Ljc5MDkgMTMuNzM1MiAxOS44MDU0IDE0LjE4MjMgMTkuNTM4OCAxNC40NjY3TDE1Ljc1ODUgMTguNDk4OUMxNS40OTcgMTguNzc3NyAxNS4wNjEgMTguNzk3NyAxNC43NzUxIDE4LjU0MzhMMTIuNTA2NiAxNi41MjcyQzEyLjIxNTQgMTYuMjY4MyAxMi4xODkyIDE1LjgyMjQgMTIuNDQ4IDE1LjUzMTFDMTIuNzA2OSAxNS4yMzk5IDEzLjE1MjggMTUuMjEzNyAxMy40NDQxIDE1LjQ3MjVMMTUuMTk5IDE3LjAzMjFMMTguNTA5NSAxMy41MDA5WiIgZmlsbD0id2hpdGUiLz48L2c+PGRlZnM+PGNsaXBQYXRoIGlkPSJjbGlwMF85MjM0XzMzMTU2Ij48cGF0aCBkPSJNMCAxNkMwIDcuMTYzNDQgNy4xNjM0NCAwIDE2IDBDMjQuODM2NiAwIDMyIDcuMTYzNDQgMzIgMTZDMzIgMjQuODM2NiAyNC44MzY2IDMyIDE2IDMyQzcuMTYzNDQgMzIgMCAyNC44MzY2IDAgMTZaIiBmaWxsPSJ3aGl0ZSIvPjwvY2xpcFBhdGg+PC9kZWZzPjwvc3ZnPg==',
						],
					],
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

<?php

use Bitrix\Tasks\Integration\UI\EntitySelector\UserDataFilter;
use Bitrix\Tasks\Integration\UI\EntitySelector\ProjectDataFilter;
use Bitrix\Tasks\Integration\UI\EntitySelector\DistributedUserDataFilter;
use Bitrix\Tasks\Integration\UI\EntitySelector\EpicSelectorProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\FlowProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\FlowUserProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\ScrumUserProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\SprintSelectorProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\TaskProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\TaskTagProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\TaskTemplateProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\TaskWithIdProvider;
use Bitrix\Tasks\Integration\UI\EntitySelector\TemplateTagProvider;

return [
	'value' => [
		'entities' => [
			[
				'entityId' => 'task',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => TaskProvider::class,
				],
			],
			[
				'entityId' => 'task-with-id',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => TaskWithIdProvider::class,
				],
			],
			[
				'entityId' => 'task-tag',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => TaskTagProvider::class,
				],
			],
			[
				'entityId' => 'task-template',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => TaskTemplateProvider::class,
				],
			],
			[
				'entityId' => 'scrum-user',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => ScrumUserProvider::class,
				],
			],
			[
				'entityId' => 'sprint-selector',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => SprintSelectorProvider::class,
				],
			],
			[
				'entityId' => 'epic-selector',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => EpicSelectorProvider::class,
				],
			],
			[
				'entityId' => 'template-tag',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => TemplateTagProvider::class,
				],
			],
			[
				'entityId' => 'flow',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => FlowProvider::class,
				],
			],
			[
				'entityId' => 'flow-user',
				'provider' => [
					'moduleId' => 'tasks',
					'className' => FlowUserProvider::class,
				],
			],
		],
		'filters' => [
			[
				'id' => 'tasks.userDataFilter',
				'entityId' => 'user',
				'className' => UserDataFilter::class,
			],
			[
				'id' => 'tasks.projectDataFilter',
				'entityId' => 'project',
				'className' => ProjectDataFilter::class,
			],
			[
				'id' => 'tasks.distributedUserDataFilter',
				'entityId' => 'user',
				'className' => DistributedUserDataFilter::class,
			],
		],
		'extensions' => ['tasks.entity-selector'],
	],
	'readonly' => true,
];
<?php

use Bitrix\Tasks\Integration\UI\EntitySelector;

return array(
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Bitrix\\Tasks\\Rest\\Controllers' => 'api',
				'\\Bitrix\\Tasks\\Scrum\\Controllers' => 'scrum',
				'\\Bitrix\\Tasks\\Flow\\Controllers' => 'flow',
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
						'className' => EntitySelector\TaskProvider::class,
					],
				],
				[
					'entityId' => 'task-with-id',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\TaskWithIdProvider::class,
					],
				],
				[
					'entityId' => 'task-tag',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\TaskTagProvider::class,
					],
				],
				[
					'entityId' => 'task-template',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\TaskTemplateProvider::class,
					],
				],
				[
					'entityId' => 'scrum-user',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\ScrumUserProvider::class,
					],
				],
				[
					'entityId' => 'sprint-selector',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\SprintSelectorProvider::class,
					],
				],
				[
					'entityId' => 'epic-selector',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\EpicSelectorProvider::class,
					],
				],
				[
					'entityId' => 'template-tag',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\TemplateTagProvider::class,
					],
				],
				[
					'entityId' => 'flow',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\FlowProvider::class,
					],
				],
				[
					'entityId' => 'flow-user',
					'provider' => [
						'moduleId' => 'tasks',
						'className' => EntitySelector\FlowUserProvider::class,
					],
				],
			],
			'filters' => [
				[
					'id' => 'tasks.userDataFilter',
					'entityId' => 'user',
					'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\UserDataFilter',
				],
				[
					'id' => 'tasks.projectDataFilter',
					'entityId' => 'project',
					'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\ProjectDataFilter',
				],
				[
					'id' => 'tasks.distributedUserDataFilter',
					'entityId' => 'user',
					'className' => '\\Bitrix\\Tasks\\Integration\\UI\\EntitySelector\\DistributedUserDataFilter',
				],
			],
			'extensions' => ['tasks.entity-selector'],
		],
		'readonly' => true,
	],
	'services' => [
		'value' => [
			'tasks.flow.member.facade' => [
				'className' => \Bitrix\Tasks\Flow\Provider\FlowMemberFacade::class,
			],
			'tasks.flow.command.addCommandHandler' => [
				'className' => \Bitrix\Tasks\Flow\Control\Command\AddCommandHandler::class,
			],
			'tasks.flow.command.updateCommandHandler' => [
				'className' => \Bitrix\Tasks\Flow\Control\Command\UpdateCommandHandler::class,
			],
			'tasks.flow.command.deleteCommandHandler' => [
				'className' => \Bitrix\Tasks\Flow\Control\Command\DeleteCommandHandler::class,
			],
			'tasks.flow.socialnetwork.project.service' => [
				'className' => \Bitrix\Tasks\Flow\Integration\Socialnetwork\GroupService::class,
			],
			'tasks.flow.kanban.service' => [
				'className' => \Bitrix\Tasks\Flow\Kanban\KanbanService::class,
			],
			'tasks.flow.kanban.bizproc.service' => [
				'className' => \Bitrix\Tasks\Flow\Kanban\BizProcService::class,
			],
			'tasks.flow.template.permission.service' => [
				'className' => \Bitrix\Tasks\Flow\Template\Access\Permission\TemplatePermissionService::class
			],
			'tasks.flow.notification.service' => [
				'className' => \Bitrix\Tasks\Flow\Notification\NotificationService::class,
			],
			'tasks.flow.service' => [
				'className' => \Bitrix\Tasks\Flow\Control\FlowService::class,
			],
			'tasks.control.log.task.service' => [
				'className' => \Bitrix\Tasks\Control\Log\TaskLogService::class,
			],
			'tasks.flow.efficiency.service' => [
				'className' => \Bitrix\Tasks\Flow\Efficiency\EfficiencyService::class
			],

			'tasks.regular.replicator' => [
				'className' => \Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator::class,
			],

			'tasks.user.option.automute.service' => [
				'className' => \Bitrix\Tasks\Internals\UserOption\Service\AutoMuteService::class,
			],

			'tasks.flow.copilot.collected.data.service' => [
				'className' => \Bitrix\Tasks\Flow\Integration\AI\Control\CollectedDataService::class,
			],

			'tasks.flow.copilot.advice.service' => [
				'className' => \Bitrix\Tasks\Flow\Integration\AI\Control\AdviceService::class,
			],
		],
	],
);

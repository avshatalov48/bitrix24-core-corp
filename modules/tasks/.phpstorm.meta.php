<?php

namespace PHPSTORM_META
{
	registerArgumentsSet(
		'bitrix_tasks_locator_codes',
		'tasks.flow.member.facade',
		'tasks.flow.command.addCommandHandler',
		'tasks.flow.command.updateCommandHandler',
		'tasks.flow.command.deleteCommandHandler',
		'tasks.flow.socialnetwork.project.service',
		'tasks.flow.kanban.service',
		'tasks.flow.kanban.bizproc.service',
		'tasks.flow.template.permission.service',
		'tasks.flow.notification.service',
		'tasks.flow.service',
		'tasks.control.log.task.service',
		'tasks.flow.efficiency.service',
		'tasks.regular.replicator',
		'tasks.user.option.automute.service',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_tasks_locator_codes'));

	override(
		\Bitrix\Main\DI\ServiceLocator::get(0),
		map(
			[
				'tasks.flow.member.facade' => \Bitrix\Tasks\Flow\Provider\FlowMemberFacade::class,
				'tasks.flow.command.addCommandHandler' => \Bitrix\Tasks\Flow\Control\Command\AddCommandHandler::class,
				'tasks.flow.command.updateCommandHandler' => \Bitrix\Tasks\Flow\Control\Command\UpdateCommandHandler::class,
				'tasks.flow.command.deleteCommandHandler' => \Bitrix\Tasks\Flow\Control\Command\DeleteCommandHandler::class,
				'tasks.flow.socialnetwork.project.service' => \Bitrix\Tasks\Flow\Integration\Socialnetwork\GroupService::class,
				'tasks.flow.kanban.service' => \Bitrix\Tasks\Flow\Kanban\KanbanService::class,
				'tasks.flow.kanban.bizproc.service' => \Bitrix\Tasks\Flow\Kanban\BizProcService::class,
				'tasks.flow.template.permission.service' => \Bitrix\Tasks\Flow\Template\Access\Permission\TemplatePermissionService::class,
				'tasks.flow.notification.service' => \Bitrix\Tasks\Flow\Notification\NotificationService::class,
				'tasks.flow.service' => \Bitrix\Tasks\Flow\Control\FlowService::class,
				'tasks.control.log.task.service' => \Bitrix\Tasks\Control\Log\TaskLogService::class,
				'tasks.flow.efficiency.service' => \Bitrix\Tasks\Flow\Efficiency\EfficiencyService::class,
				'tasks.regular.replicator' => \Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator::class,
				'tasks.user.option.automute.service' => \Bitrix\Tasks\Internals\UserOption\Service\AutoMuteService::class,
			]
		)
	);
}
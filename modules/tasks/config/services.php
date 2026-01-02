<?php

use Bitrix\Tasks\Control\Log\TaskLogService;
use Bitrix\Tasks\Flow\Control\Command\AddCommandHandler;
use Bitrix\Tasks\Flow\Control\Command\DeleteCommandHandler;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommandHandler;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Efficiency\EfficiencyService;
use Bitrix\Tasks\Flow\Integration\AI\Control\AdviceService;
use Bitrix\Tasks\Flow\Integration\AI\Control\CollectedDataService;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\GroupService;
use Bitrix\Tasks\Flow\Kanban\BizProcService;
use Bitrix\Tasks\Flow\Provider\FlowMemberFacade;
use Bitrix\Tasks\Flow\Template\Access\Permission\TemplatePermissionService;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\UserOption\Service\AutoMuteService;
use Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator;
use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactory;
use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\GanttLinkRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryTariffRestrictionRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryTaskParameterRepository;
use Bitrix\Tasks\V2\Internal\Repository\ParentTaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TariffRestrictionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskParameterRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskTagRepositoryInterface;

return [
	'value' => [
		// region flow
		'tasks.flow.member.facade' => [
			'className' => FlowMemberFacade::class,
		],
		'tasks.flow.command.addCommandHandler' => [
			'className' => AddCommandHandler::class,
		],
		'tasks.flow.command.updateCommandHandler' => [
			'className' => UpdateCommandHandler::class,
		],
		'tasks.flow.command.deleteCommandHandler' => [
			'className' => DeleteCommandHandler::class,
		],
		'tasks.flow.socialnetwork.project.service' => [
			'className' => GroupService::class,
		],
		'tasks.flow.kanban.bizproc.service' => [
			'className' => BizProcService::class,
		],
		'tasks.flow.template.permission.service' => [
			'className' => TemplatePermissionService::class,
		],
		'tasks.flow.notification.service' => [
			'className' => \Bitrix\Tasks\Flow\Notification\NotificationService::class,
		],
		'tasks.flow.service' => [
			'className' => FlowService::class,
		],

		'tasks.flow.efficiency.service' => [
			'className' => EfficiencyService::class,
		],

		// region flow copilot
		'tasks.flow.copilot.collected.data.service' => [
			'className' => CollectedDataService::class,
		],

		'tasks.flow.copilot.advice.service' => [
			'className' => AdviceService::class,
		],
		// endregion
		// endregion

		// region task log
		'tasks.control.log.task.service' => [
			'className' => TaskLogService::class,
		],
		// endregion

		// region replicator
		'tasks.regular.replicator' => [
			'className' => RegularTemplateTaskReplicator::class,
		],
		// endregion

		// region task option
		'tasks.user.option.automute.service' => [
			'className' => AutoMuteService::class,
		],
		// endregion

		// region task access
		ControllerFactoryInterface::class => [
			'constructor' => static fn(): ControllerFactoryInterface => ControllerFactory::getInstance(),
		],
		// endregion
		// region task registry
		'tasks.task.registry' => [
			'constructor' => static fn(): TaskRegistry => TaskRegistry::getInstance(),
		],
		// endregion

		// region DI
		Bitrix\Tasks\V2\Internal\Repository\FileRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryFileRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryGroupRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryTaskRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\StageRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryStageRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryTemplateRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\FlowRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryFlowRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\SubTaskRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemorySubTaskRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\RelatedTaskRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryRelatedTaskRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\CheckListRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\CheckListRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\CheckListUserOptionRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\CheckListUserOptionRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryUserRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Service\Esg\EgressInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Service\Esg\EgressController::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\ChatRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryChatRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Integration\Disk\Repository\DiskFileRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Integration\Disk\Repository\InMemoryDiskFileRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskResultRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\TaskResultRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskLogRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\TaskLogRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Service\Consistency\ConfigConsistencyResolver::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\FavoriteTaskRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\FavoriteTaskRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskMemberRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\TaskMemberRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskUserOptionRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryTaskUserOptionRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\UserOptionRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\UserOptionRepository::class,
		],
		Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface::class => [
			'className' => Bitrix\Tasks\Deadline\Internals\Repository\Cache\Managed\CacheDeadlineUserOptionRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TimerRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\TimerRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\PlannerRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\PlannerRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\ElapsedTimeRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\ElapsedTimeRepository::class,
		],
		Bitrix\Tasks\Onboarding\Promo\Repository\InviteToMobileRepositoryInterface::class => [
			'className' => Bitrix\Tasks\Onboarding\Promo\Repository\Cache\CacheInviteToMobileRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\ViewRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\ViewRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskStageRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\TaskStageRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskReadRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\TaskReadRepository::class,
		],
		TaskParameterRepositoryInterface::class => [
			'className' => InMemoryTaskParameterRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\TaskScenarioRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\TaskScenarioRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\DeadlineChangeLogRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\DeadlineChangeLogRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\ReminderRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\ReminderRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotificationInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotification::class,
		],
		Bitrix\Tasks\V2\Internal\Integration\CRM\Repository\CrmItemRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Integration\CRM\Repository\InMemoryCrmItemRepository::class,
		],
		Bitrix\Tasks\Flow\Migration\Access\Repository\RoleRepositoryInterface::class => [
			'className' => Bitrix\Tasks\Flow\Migration\Access\Repository\RoleRepository::class,
		],
		Bitrix\Tasks\V2\Internal\Repository\OptionRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\OptionRepository::class,
		],
		TaskTagRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\InMemoryTaskTagRepository::class,
		],
		GanttLinkRepositoryInterface::class => [
			'className' => Bitrix\Tasks\V2\Internal\Repository\GanttLinkRepository::class,
		],
		TariffRestrictionRepositoryInterface::class => [
			'className' => InMemoryTariffRestrictionRepository::class,
		],
		ParentTaskRepositoryInterface::class => [
			'className' => \Bitrix\Tasks\V2\Internal\Repository\ParentTaskRepository::class,
		],
		// endregion
	],
];

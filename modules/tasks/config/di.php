<?php

use Bitrix\Tasks\Deadline\Internals\Repository\Cache\Managed\CacheDeadlineUserOptionRepository;
use Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface;
use Bitrix\Tasks\Onboarding\Promo\Repository\Cache\CacheInviteToMobileRepository;
use Bitrix\Tasks\Onboarding\Promo\Repository\InviteToMobileRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepository;
use Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ReminderRepository;
use Bitrix\Tasks\V2\Internal\Repository\ReminderRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConfigConsistencyResolver;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Repository\CheckListRepository;
use Bitrix\Tasks\V2\Internal\Repository\CheckListRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\DiskFileRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ElapsedTimeRepository;
use Bitrix\Tasks\V2\Internal\Repository\ElapsedTimeRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\FavoriteTaskRepository;
use Bitrix\Tasks\V2\Internal\Repository\FavoriteTaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\FileRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryDiskFileRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryFileRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryFlowRepository;
use Bitrix\Tasks\V2\Internal\Repository\FlowRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ChatRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryChatRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryGroupRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryStageRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryUserOptionRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryTaskRepository;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryUserRepository;
use Bitrix\Tasks\V2\Internal\Repository\TaskReadRepository;
use Bitrix\Tasks\V2\Internal\Repository\TaskReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskScenarioRepository;
use Bitrix\Tasks\V2\Internal\Repository\TaskScenarioRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskStageRepository;
use Bitrix\Tasks\V2\Internal\Repository\TaskStageRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TimerRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\PlannerRepository;
use Bitrix\Tasks\V2\Internal\Repository\PlannerRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\StageRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskLogRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskLogRepository;
use Bitrix\Tasks\V2\Internal\Repository\TaskMemberRepository;
use Bitrix\Tasks\V2\Internal\Repository\TaskMemberRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\InMemoryTemplateRepository;
use Bitrix\Tasks\V2\Internal\Repository\TimerRepository;
use Bitrix\Tasks\V2\Internal\Repository\UserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ViewRepository;
use Bitrix\Tasks\V2\Internal\Repository\ViewRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Esg\EgressInterface;
use Bitrix\Tasks\V2\Internal\Service\Esg\EgressController;
use Bitrix\Tasks\V2\Internal\Repository\TaskResultRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskResultRepository;
use Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotificationInterface;
use Bitrix\Tasks\V2\Internal\Integration\Im\ChatNotification;

return [
	'value' => [
		FileRepositoryInterface::class => InMemoryFileRepository::class,
		GroupRepositoryInterface::class => InMemoryGroupRepository::class,
		TaskRepositoryInterface::class => InMemoryTaskRepository::class,
		StageRepositoryInterface::class => InMemoryStageRepository::class,
		TemplateRepositoryInterface::class => InMemoryTemplateRepository::class,
		FlowRepositoryInterface::class => InMemoryFlowRepository::class,
		CheckListRepositoryInterface::class => CheckListRepository::class,
		UserRepositoryInterface::class => InMemoryUserRepository::class,
		EgressInterface::class => EgressController::class,
		ChatRepositoryInterface::class => InMemoryChatRepository::class,
		DiskFileRepositoryInterface::class => InMemoryDiskFileRepository::class,
		TaskResultRepositoryInterface::class => TaskResultRepository::class,
		TaskLogRepositoryInterface::class => TaskLogRepository::class,
		ConsistencyResolverInterface::class => ConfigConsistencyResolver::class,
		FavoriteTaskRepositoryInterface::class => FavoriteTaskRepository::class,
		TaskMemberRepositoryInterface::class => TaskMemberRepository::class,
		UserOptionRepositoryInterface::class => InMemoryUserOptionRepository::class,
		DeadlineUserOptionRepositoryInterface::class => CacheDeadlineUserOptionRepository::class,
		TimerRepositoryInterface::class => TimerRepository::class,
		PlannerRepositoryInterface::class => PlannerRepository::class,
		ElapsedTimeRepositoryInterface::class => ElapsedTimeRepository::class,
		InviteToMobileRepositoryInterface::class => CacheInviteToMobileRepository::class,
		ViewRepositoryInterface::class => ViewRepository::class,
		TaskStageRepositoryInterface::class => TaskStageRepository::class,
		TaskReadRepositoryInterface::class => TaskReadRepository::class,
		TaskScenarioRepositoryInterface::class => TaskScenarioRepository::class,
		ReminderRepositoryInterface::class => ReminderRepository::class,
		ReminderReadRepositoryInterface::class => ReminderReadRepository::class,
		ChatNotificationInterface::class => ChatNotification::class
	],
];
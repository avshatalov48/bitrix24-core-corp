<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\DI;

use Bitrix\Tasks\Deadline\Internals\Repository\Cache\Managed\CacheDeadlineUserOptionRepository;
use Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface;
use Bitrix\Tasks\DI\AbstractContainer;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Provider\Log\TaskLogProvider;
use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactoryInterface;
use Bitrix\Tasks\V2\Internal\Access\Service\TaskAccessService;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Access\Service\CrmAccessService;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Service\CrmItemService;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Repository\DiskFileRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Service\Task\AttachmentService;
use Bitrix\Tasks\V2\Internal\Integration\Intranet\Service\ToolService;
use Bitrix\Tasks\V2\Internal\Repository\DeadlineChangeLogRepository;
use Bitrix\Tasks\V2\Internal\Repository\DeadlineChangeLogRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\GanttLinkRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\ReminderMapper;
use Bitrix\Tasks\V2\Internal\Repository\ParentTaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\RelatedTaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ReminderReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\ReminderRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskParameterRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskReadRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskTagRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\AddTaskService;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Repository\ChatRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\FavoriteTaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\ElapsedTimeMapper;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\OrmTaskMapper;
use Bitrix\Tasks\V2\Internal\Repository\TaskStageRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskLogRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskScenarioRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TimerRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\StageRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskMemberRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TemplateRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskUserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service;
use Bitrix\Tasks\V2\Internal\Service\CheckList\CheckListService;
use Bitrix\Tasks\V2\Internal\Service\CheckList\Prepare\Save\CheckListEntityFieldService;
use Bitrix\Tasks\V2\Internal\Service\Extension\ConfigService;
use Bitrix\Tasks\V2\Internal\Service\FeatureService;
use Bitrix\Tasks\V2\Internal\Service\Link\LinkService;
use Bitrix\Tasks\V2\Internal\Service\TariffService;
use Bitrix\Tasks\V2\Internal\Service\DeleteTaskService;
use Bitrix\Tasks\V2\Internal\Service\Task\ElapsedTimeService;
use Bitrix\Tasks\V2\Internal\Service\Task\FavoriteService;
use Bitrix\Tasks\V2\Internal\Service\Task\Gantt\GanttDependenceService;
use Bitrix\Tasks\V2\Internal\Service\Task\MemberService;
use Bitrix\Tasks\V2\Internal\Service\Task\ParentService;
use Bitrix\Tasks\V2\Internal\Service\Task\PlannerService;
use Bitrix\Tasks\V2\Internal\Service\Task\RelatedTaskService;
use Bitrix\Tasks\V2\Internal\Service\Task\ReminderService;
use Bitrix\Tasks\V2\Internal\Service\Task\ResultService;
use Bitrix\Tasks\V2\Internal\Repository\Compatibility;
use Bitrix\Tasks\V2\Internal\Service\Task\TaskStageService;
use Bitrix\Tasks\V2\Internal\Service\Task\ScenarioService;
use Bitrix\Tasks\V2\Internal\Service\Task\StatusService;
use Bitrix\Tasks\V2\Internal\Service\Task\TimeManagementService;
use Bitrix\Tasks\V2\Internal\Service\Task\TimerService;
use Bitrix\Tasks\V2\Internal\Service\Task\UpdateService;
use Bitrix\Tasks\V2\Internal\Service\Task\UserOptionService;
use Bitrix\Tasks\V2\Internal\Service\Task\ViewService;
use Bitrix\Tasks\V2\Internal\Service\UpdateTaskService;
use Bitrix\Tasks\V2\Internal\Service\UrlService;
use Bitrix\Tasks\V2\Internal\Service\UserService;
use Bitrix\Tasks\V2\Public\Provider\AhaMomentProvider;
use Bitrix\Tasks\V2\Public\Provider\CheckListProvider;
use Bitrix\Tasks\V2\Public\Provider\Counter\RoleProvider;
use Bitrix\Tasks\V2\Internal\Service\TaskLegacyFeatureService;
use Bitrix\Tasks\V2\Public\Provider\TaskFromTemplateProvider;

class Container extends AbstractContainer
{
	public function getParentService(): ParentService
	{
		return $this->get(ParentService::class);
	}

	public function getGanttDependenceService(): GanttDependenceService
	{
		return $this->get(GanttDependenceService::class);
	}

	public function getRelatedTaskRepository(): RelatedTaskRepositoryInterface
	{
		return $this->get(RelatedTaskRepositoryInterface::class);
	}

	public function getRelatedTaskService(): RelatedTaskService
	{
		return $this->get(RelatedTaskService::class);
	}

	public function getFeatureService(): FeatureService
	{
		return $this->get(FeatureService::class);
	}

	public function getUrlService(): UrlService
	{
		return $this->get(UrlService::class);
	}

	public function getConfigService(): ConfigService
	{
		return $this->get(ConfigService::class);
	}

	public function getToolService(): ToolService
	{
		return $this->get(ToolService::class);
	}

	public function getAttachmentService(): AttachmentService
	{
		return $this->get(AttachmentService::class);
	}

	public function getDiskFileRepository(): DiskFileRepositoryInterface
	{
		return $this->get(DiskFileRepositoryInterface::class);
	}

	public function getTaskFromTemplateProvider(): TaskFromTemplateProvider
	{
		return $this->get(TaskFromTemplateProvider::class);
	}

	public function getCrmItemService(): CrmItemService
	{
		return $this->get(CrmItemService::class);
	}

	public function getCrmAccessService(): CrmAccessService
	{
		return $this->get(CrmAccessService::class);
	}

	public function getReminderReadRepository(): ReminderReadRepositoryInterface
	{
		return $this->get(ReminderReadRepositoryInterface::class);
	}

	public function getReminderMapper(): ReminderMapper
	{
		return $this->get(ReminderMapper::class);
	}

	public function getReminderService(): ReminderService
	{
		return $this->get(ReminderService::class);
	}

	public function getReminderRepository(): ReminderRepositoryInterface
	{
		return $this->get(ReminderRepositoryInterface::class);
	}

	public function getUserService(): UserService
	{
		return $this->get(UserService::class);
	}

	public function getTaskReadRepository(): TaskReadRepositoryInterface
	{
		return $this->get(TaskReadRepositoryInterface::class);
	}

	public function getTaskStageRepository(): TaskStageRepositoryInterface
	{
		return $this->get(TaskStageRepositoryInterface::class);
	}

	public function getTaskStageService(): TaskStageService
	{
		return $this->get(TaskStageService::class);
	}

	public function getTaskTagRepository(): TaskTagRepositoryInterface
	{
		return $this->get(TaskTagRepositoryInterface::class);
	}

	public function getTaskLogRepository(): TaskLogRepositoryInterface
	{
		return $this->get(TaskLogRepositoryInterface::class);
	}

	public function getScenarioService(): ScenarioService
	{
		return $this->get(ScenarioService::class);
	}

	public function getScenarioRepository(): TaskScenarioRepositoryInterface
	{
		return $this->get(TaskScenarioRepositoryInterface::class);
	}

	public function getDeleteTaskService(): DeleteTaskService
	{
		return $this->get(DeleteTaskService::class);
	}

	public function getAddTaskService(): AddTaskService
	{
		return $this->get(AddTaskService::class);
	}

	public function getCheckListEntityFieldService(): CheckListEntityFieldService
	{
		return $this->get(CheckListEntityFieldService::class);
	}

	public function getCheckListService(): CheckListService
	{
		return $this->get(CheckListService::class);
	}

	public function getCheckListUserOptionService(): Service\CheckList\UserOptionService
	{
		return $this->get(Service\CheckList\UserOptionService::class);
	}

	public function getTariffService(): TariffService
	{
		return $this->get(TariffService::class);
	}

	public function getViewService(): ViewService
	{
		return $this->get(ViewService::class);
	}

	public function getLinkService(): LinkService
	{
		return $this->get(LinkService::class);
	}

	public function getMemberService(): MemberService
	{
		return $this->get(MemberService::class);
	}

	public function getTimeManagementService(): TimeManagementService
	{
		return $this->get(TimeManagementService::class);
	}

	public function getPlannerService(): PlannerService
	{
		return $this->get(PlannerService::class);
	}

	public function getTimerService(): TimerService
	{
		return $this->get(TimerService::class);
	}

	public function getTimerRepository(): TimerRepositoryInterface
	{
		return $this->get(TimerRepositoryInterface::class);
	}

	public function getOrmTaskMapper(): OrmTaskMapper
	{
		return $this->get(OrmTaskMapper::class);
	}

	public function getDeadlineRepository(): DeadlineUserOptionRepositoryInterface
	{
		return $this->get(DeadlineUserOptionRepositoryInterface::class);
	}

	public function getStageRepository(): StageRepositoryInterface
	{
		return $this->get(StageRepositoryInterface::class);
	}

	public function getTaskAccessService(): TaskAccessService
	{
		return $this->get(TaskAccessService::class);
	}

	public function getTaskUserOptionRepository(): TaskUserOptionRepositoryInterface
	{
		return $this->get(TaskUserOptionRepositoryInterface::class);
	}

	public function getUserOptionService(): UserOptionService
	{
		return $this->get(UserOptionService::class);
	}

	public function getElapsedTimeMapper(): ElapsedTimeMapper
	{
		return $this->get(ElapsedTimeMapper::class);
	}

	public function getElapsedTimeService(): ElapsedTimeService
	{
		return $this->get(ElapsedTimeService::class);
	}

	public function getStatusService(): StatusService
	{
		return $this->get(StatusService::class);
	}

	public function getUpdateTaskService(): UpdateTaskService
	{
		return $this->get(UpdateTaskService::class);
	}

	public function getUpdateService(): UpdateService
	{
		return $this->get(UpdateService::class);
	}

	public function getFavoriteService(): FavoriteService
	{
		return $this->get(FavoriteService::class);
	}

	public function getTaskMemberRepository(): TaskMemberRepositoryInterface
	{
		return $this->get(TaskMemberRepositoryInterface::class);
	}

	public function getTaskParameterRepository(): TaskParameterRepositoryInterface
	{
		return $this->get(TaskParameterRepositoryInterface::class);
	}

	public function getTaskCompatabilityRepository(): Compatibility\TaskRepository
	{
		return $this->get(Compatibility\TaskRepository::class);
	}

	public function getFavoriteTaskRepository(): FavoriteTaskRepositoryInterface
	{
		return $this->get(FavoriteTaskRepositoryInterface::class);
	}

	public function getTaskLogProvider(): TaskLogProvider
	{
		return $this->get(TaskLogProvider::class);
	}

	public function getAccessControllerFactory(): ControllerFactoryInterface
	{
		return $this->get(ControllerFactoryInterface::class);
	}

	public function getTaskRepository(): TaskRepositoryInterface
	{
		return $this->get(TaskRepositoryInterface::class);
	}

	public function getRegistry(): TaskRegistry
	{
		return $this->get('tasks.task.registry');
	}

	public function getGroupRepository(): GroupRepositoryInterface
	{
		return $this->get(GroupRepositoryInterface::class);
	}

	public function getTemplateRepository(): TemplateRepositoryInterface
	{
		return $this->get(TemplateRepositoryInterface::class);
	}

	public function getChatRepository(): ChatRepositoryInterface
	{
		return $this->get(ChatRepositoryInterface::class);
	}

	public function getResultService(): ResultService
	{
		return $this->get(ResultService::class);
	}

	public function getConsistencyResolver(): ConsistencyResolverInterface
	{
		return $this->get(ConsistencyResolverInterface::class);
	}

	public function getTaskLegacyFeatureService(): TaskLegacyFeatureService
	{
		return $this->get(TaskLegacyFeatureService::class);
	}

	public function getRoleProvider(): RoleProvider
	{
		return $this->get(RoleProvider::class);
	}

	public function getDeadlineUserOptionRepository(): DeadlineUserOptionRepositoryInterface
	{
		return $this->get(CacheDeadlineUserOptionRepository::class);
	}

	public function getDeadlineLogRepository(): DeadlineChangeLogRepositoryInterface
	{
		return $this->get(DeadlineChangeLogRepository::class);
	}

	public function getAhaMomentProvider(): AhaMomentProvider
	{
		return $this->get(AhaMomentProvider::class);
	}

	public function getEgressController(): Service\Esg\EgressInterface
	{
		return $this->get(Service\Esg\EgressController::class);
	}

	public function getChecklistOperationDetector(): Service\Esg\Detector\ChecklistOperationDetector
	{
		return $this->get(Service\Esg\Detector\ChecklistOperationDetector::class);
	}

	public function getCheckListProvider(): CheckListProvider
	{
		return $this->get(CheckListProvider::class);
	}
}

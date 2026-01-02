<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Extension;

use Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface;
use Bitrix\Tasks\V2\FormV2Feature;
use Bitrix\Tasks\V2\Internal\Access\Service\FlowRightService;
use Bitrix\Tasks\V2\Internal\Access\Service\TaskRightService;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Integration\Im\Chat;
use Bitrix\Tasks\V2\Internal\Integration\Intranet\Service\ToolService;
use Bitrix\Tasks\V2\Internal\Service\AnalyticsService;
use Bitrix\Tasks\V2\Internal\Service\Link\LinkService;
use Bitrix\Tasks\V2\Internal\Service\TariffService;

class ConfigService
{
	public function __construct(
		private readonly TariffService $tariffService,
		private readonly LinkService $linkService,
		private readonly TaskRightService $taskRightService,
		private readonly FlowRightService $flowRightService,
		private readonly ToolService $toolService,
		private readonly AnalyticsService $analyticsService,
		private readonly DeadlineUserOptionRepositoryInterface $deadlineUserOptionRepository,
	)
	{

	}
	public function getCoreSettings(int $userId): array
	{
		return [
			'currentUserId' => $userId,
			'rights' => [
				'flow' => $this->flowRightService->getUserRights($userId),
				'tasks' => $this->taskRightService->getUserRights($userId),
			],
			'limits' => [
				'mailUserIntegration' => $this->tariffService->isMailUserAvailable(),
				'mailUserIntegrationFeatureId' => $this->tariffService->getMailUserFeature(),
				'project' => $this->tariffService->isProjectAvailable(),
				'projectFeatureId' => $this->tariffService->getProjectFeatureId(),
				'stakeholders' => $this->tariffService->isStakeholderAvailable(),
			],
			'defaultDeadline' => $this->deadlineUserOptionRepository->getByUserId($userId)->toArray(),
			'chatType' => Chat::ENTITY_TYPE,
			'features' => [
				'isV2Enabled' => FormV2Feature::isOn(),
				'isMiniformEnabled' => FormV2Feature::isOn('miniform'),
				'isFlowEnabled' => FlowFeature::isOn(),
				'isProjectsEnabled' => $this->toolService->isProjectsAvailable(),
				'isTemplateEnabled' => $this->toolService->isTemplatesAvailable(),
			],
			'paths' => [
				'editPath' => $this->linkService->getCreateTask($userId),
			],
			'ahaMoments' => Container::getInstance()->getAhaMomentProvider()->get($userId),
		];
	}

	public function getAnalyticsSettings(int $userId): array
	{
		return [
			'userType' => $this->analyticsService->getUserTypeParameter($userId),
			'isDemo' => $this->tariffService->isDemo(),
		];
	}
}

<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService\AbstractRemoveResponsibleService;
use Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService\HimselfRemoveResponsibleService;
use Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService\ManuallyRemoveResponsibleService;
use Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService\QueueRemoveResponsibleService;
use Bitrix\Tasks\Update\AgentInterface;

final class RemoveUserFromFlowResponsible implements AgentInterface
{
	private const STEP_SIZE = 100;

	public static function bindAgent(int $userId): void
	{
		\CAgent::AddAgent(self::getAgentName($userId), 'tasks', 'N', 5);
	}

	private static function getAgentName(int $userId): string
	{
		return self::class . '::execute(' . $userId . ');';
	}

	public static function execute(): string
	{
		$userId = (int)(func_get_args()[0] ?? null);

		if ($userId <= 0)
		{
			return '';
		}

		$doNeedToContinue = (new self($userId))->run();

		if (!$doNeedToContinue)
		{
			return '';
		}

		return self::getAgentName($userId);
	}

	public function __construct(
		private readonly int $deletedUserId,
	)
	{}

	public function run(): bool
	{
		$isNeedContinue = false;
		foreach (FlowDistributionType::cases() as $distributionType)
		{
			$removeResponsibleService = $this->getRemoveResponsibleService($distributionType);

			$userFlowIds = $removeResponsibleService->getFlowIdsByUser($this->deletedUserId, self::STEP_SIZE);
			$removeResponsibleService->removeUserFromFlowsResponsible($this->deletedUserId, $userFlowIds);

			if (count($userFlowIds) >= self::STEP_SIZE)
			{
				$isNeedContinue = true;
			}
		}

		return $isNeedContinue;
	}

	private function getRemoveResponsibleService(FlowDistributionType $distributionType): AbstractRemoveResponsibleService
	{
		return match ($distributionType)
		{
			FlowDistributionType::MANUALLY => new ManuallyRemoveResponsibleService(),
			FlowDistributionType::QUEUE => new QueueRemoveResponsibleService(),
			FlowDistributionType::HIMSELF => new HimselfRemoveResponsibleService(),
		};
	}
}
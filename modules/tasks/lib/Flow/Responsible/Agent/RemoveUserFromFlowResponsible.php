<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent;

use Bitrix\Main\UserTable;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\FlowOptionTable;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueProvider;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;
use Bitrix\Tasks\Update\AgentInterface;
use Bitrix\Tasks\Util\User;

final class RemoveUserFromFlowResponsible implements AgentInterface
{
	private const STEP_SIZE = 100;

	private const CONTINUE = true;
	private const FINISH = false;

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
		private readonly ResponsibleQueueProvider $responsibleQueueProvider = new ResponsibleQueueProvider(),
	)
	{}

	public function run(): bool
	{
		$continueManual = $this->processManualDistributionFlows();
		$continueQueue = $this->processQueueDistributionFlows();

		return $continueManual || $continueQueue;
	}

	private function processManualDistributionFlows(): bool
	{
		$flowIds = $this->getManualFlowIds();

		foreach ($flowIds as $flowId)
		{
			$this->processManualFlow($flowId);
		}

		if (count($flowIds) < self::STEP_SIZE)
		{
			return self::FINISH;
		}

		return self::CONTINUE;
	}

	private function getManualFlowIds(): array
	{
		$queryResult = FlowOptionTable::query()
			->setSelect(['FLOW_ID'])
			->where('NAME', OptionDictionary::MANUAL_DISTRIBUTOR_ID->value)
			->where('VALUE', $this->deletedUserId)
			->setLimit(self::STEP_SIZE)
			->fetchAll()
		;

		return array_map(fn($value) =>(int)$value['FLOW_ID'], $queryResult);
	}

	private function processManualFlow(int $flowId): void
	{
		$flow = FlowRegistry::getInstance()->get($flowId);

		if (!$flow)
		{
			return;
		}

		$owner = UserTable::getById($flow->getOwnerId())->fetchObject();
		$newDistributorId = $owner?->getActive() === false ? User::getAdminId() : $flow->getOwnerId();

		$command =
			(new UpdateCommand())
				->setId($flowId)
				->setDistributionType(Flow::DISTRIBUTION_TYPE_MANUALLY)
				->setManualDistributorId($newDistributorId)
		;
		(new FlowService())->update($command);

		if (FlowFeature::isOn())
		{
			(new NotificationService())->onForcedManualDistributorChange($flowId);
		}
	}

	private function processQueueDistributionFlows(): bool
	{
		$responsibleQueueService = ResponsibleQueueService::getInstance();
		$flowIds = $responsibleQueueService->getFlowIdsByUser($this->deletedUserId, self::STEP_SIZE);

		foreach ($flowIds as $flowId)
		{
			$this->processQueueFlow($flowId);
		}

		if (count($flowIds) < self::STEP_SIZE)
		{
			return self::FINISH;
		}

		return self::CONTINUE;
	}

	private function processQueueFlow(int $flowId): void
	{
		$queue = $this->responsibleQueueProvider->getResponsibleQueue($flowId);
		$userIds = $queue->getUserIds();
		$userIdsFiltered = array_values(array_filter($userIds,fn($userId) => $userId !== $this->deletedUserId));

		if (empty($userIdsFiltered))
		{
			$this->migrateFlowFormQueueToManualDistribution($flowId);
		}
		else
		{
			$this->saveFlowQueueWithoutDeletedUser($flowId, $userIdsFiltered);
		}
	}

	private function migrateFlowFormQueueToManualDistribution(int $flowId): void
	{
		$flow = FlowRegistry::getInstance()->get($flowId);

		if (!$flow)
		{
			return;
		}

		$owner = UserTable::getById($flow->getOwnerId())->fetchObject();
		$newDistributorId = $owner?->getActive() === false ? User::getAdminId() : $flow->getOwnerId();

		$command =
			(new UpdateCommand())
				->setId($flowId)
				->setDistributionType(Flow::DISTRIBUTION_TYPE_MANUALLY)
				->setManualDistributorId($newDistributorId)
		;
		(new FlowService())->update($command);

		if (FlowFeature::isOn())
		{
			(new NotificationService())->onSwitchToManualDistribution($flowId);
		}
	}

	private function saveFlowQueueWithoutDeletedUser(int $flowId, array $userIdsFiltered): void
	{
		$flow = FlowRegistry::getInstance()->get($flowId);

		if (!$flow)
		{
			return;
		}

		$command =
			(new UpdateCommand())
				->setId($flowId)
				->setResponsibleQueue($userIdsFiltered)
		;
		(new FlowService())->update($command);
	}
}
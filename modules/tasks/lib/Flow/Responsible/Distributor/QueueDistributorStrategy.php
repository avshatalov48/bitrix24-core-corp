<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Distribution\Trait\ChangeFlowDistributionTrait;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\InvalidCommandException;
use Psr\Container\NotFoundExceptionInterface;

class QueueDistributorStrategy implements DistributorStrategyInterface
{
	use ChangeFlowDistributionTrait;

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 */
	public function distribute(Flow $flow, array $fields, array $taskData): int
	{
		$isTaskAddedToFlow = false;
		if (isset($fields['FLOW_ID']) && (int)$fields['FLOW_ID'] > 0)
		{
			$isTaskAddedToFlow =
				!isset($taskData['FLOW_ID'])
				|| (int)$taskData['FLOW_ID'] !== (int)$fields['FLOW_ID']
			;
		}

		$isTaskStatusNew =
			isset($taskData['REAL_STATUS'])
			&& in_array($taskData['REAL_STATUS'], [Status::NEW, Status::PENDING])
		;

		if (empty($taskData) || ($isTaskAddedToFlow && $isTaskStatusNew))
		{
			$nextResponsibleId = ResponsibleQueueService::getInstance()->getNextResponsibleId($flow);

			if ($nextResponsibleId > 0)
			{
				$optionName = OptionDictionary::RESPONSIBLE_QUEUE_LATEST_ID->value;
				OptionService::getInstance()->save($flow->getId(), $optionName, (string)$nextResponsibleId);
			}
			else
			{
				$nextResponsibleId = $flow->getOwnerId();
				$this->migrateFlowToManualDistribution($flow->getId());
			}

			return $nextResponsibleId;
		}

		$responsibleId = $fields['RESPONSIBLE_ID'] ?? $taskData['RESPONSIBLE_ID'];

		return (int)$responsibleId;
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws ObjectNotFoundException
	 */
	protected function onMigrateToManualDistributor(int $flowId): void
	{
		/**
		 * @var NotificationService $notificationService
		 */
		$notificationService = ServiceLocator::getInstance()->get('tasks.flow.notification.service');
		$notificationService->onSwitchToManualDistributionAbsent($flowId);
	}
}
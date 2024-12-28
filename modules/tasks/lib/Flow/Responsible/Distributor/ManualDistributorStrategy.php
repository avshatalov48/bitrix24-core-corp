<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Distribution\Trait\ChangeFlowDistributionTrait;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Flow\Task\Status;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Util\User;
use Psr\Container\NotFoundExceptionInterface;

class ManualDistributorStrategy implements DistributorStrategyInterface
{
	use ChangeFlowDistributionTrait;

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 * @throws LoaderException
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
			$distributorOption =
				OptionService::getInstance()
					->getOption($flow->getId(), OptionDictionary::MANUAL_DISTRIBUTOR_ID->value)
			;

			$manualDistributorId = $distributorOption?->getValue();
			if (is_null($manualDistributorId) || $manualDistributorId <= 0 || $this->isUserAbsent($manualDistributorId))
			{
				$manualDistributorId = $flow->getOwnerId();
				$this->migrateFlowToManualDistribution($flow->getId());
			}

			return $manualDistributorId;
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
		$notificationService->onForcedManualDistributorAbsentChange($flowId);
	}

	/**
	 * @throws LoaderException
	 */
	private function isUserAbsent(int $userId): bool
	{
		return !empty(User::isAbsence([$userId]));
	}
}
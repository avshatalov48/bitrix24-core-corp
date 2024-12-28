<?php

namespace Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;

final class UpdateObserver implements UpdateObserverInterface
{
	protected ResponsibleQueueService $responsibleQueueService;

	public function __construct()
	{
		$this->responsibleQueueService = ResponsibleQueueService::getInstance();
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws LoaderException
	 */
	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		$isDistributionTypeChanged =
			$flowEntity->getDistributionType() !== $flowEntityBeforeUpdate->getDistributionType();

		$flowEntityDistributionTypeBeforeUpdateIsQueue =
			$flowEntityBeforeUpdate->getDistributionType() === FlowDistributionType::QUEUE->value;

		if ($isDistributionTypeChanged && $flowEntityDistributionTypeBeforeUpdateIsQueue)
		{
			$this->responsibleQueueService->delete($flowEntity->getId());
		}
		elseif (
			!empty($command->responsibleList)
			&& $flowEntity->getDistributionType() === FlowDistributionType::QUEUE->value
		)
		{
			$userIds = (new AccessCodeConverter(...$command->responsibleList))
				->getUserIds()
			;

			if (empty($userIds))
			{
				return;
			}

			$this->responsibleQueueService->save($flowEntity->getId(), $userIds);
		}
	}
}
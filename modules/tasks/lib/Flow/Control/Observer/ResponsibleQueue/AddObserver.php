<?php

namespace Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;

final class AddObserver implements AddObserverInterface
{
	protected ResponsibleQueueService $responsibleQueueService;

	public function __construct()
	{
		$this->responsibleQueueService = ResponsibleQueueService::getInstance();
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 */
	public function update(AddCommand $command, FlowEntity $flowEntity): void
	{
		$flowDistributionTypeIsQueue = $flowEntity->getDistributionType() === FlowDistributionType::QUEUE->value;

		if (
			!empty($command->responsibleList)
			&& $flowDistributionTypeIsQueue
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
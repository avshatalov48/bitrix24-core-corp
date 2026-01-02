<?php

namespace Bitrix\Crm\RepeatSale\Service\Handler;

use Bitrix\Crm\RepeatSale\Segment\Collector\Factory;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\Data\SegmentDataInterface;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Segment\SystemSegmentCode;
use Bitrix\Crm\RepeatSale\Service\Action\CreateActivityAction;
use Bitrix\Crm\RepeatSale\Service\Action\CreateDealAction;
use Bitrix\Crm\RepeatSale\Service\Action\LogAction;
use Bitrix\Crm\RepeatSale\Service\Context;
use Bitrix\Crm\RepeatSale\Service\Operation;
use Bitrix\Main\Error;

final class SystemHandler extends BaseHandler
{
	public function __construct(
		private readonly string $segmentCode,
		private readonly ?Context $context = null,
	)
	{

	}

 	// base handler for all predefined segments jobs
	public function execute(): Result
	{
		$segmentCode = SystemSegmentCode::tryFrom($this->segmentCode);

		$result = (new Result());
		if ($segmentCode === null)
		{
			return $result->addError(new Error('Unknown segmentCode: ' . $segmentCode));
		}

		$segmentData = Factory::getInstance()
			->getCollector($segmentCode)
			?->setLimit($this->limit)
			->setIsOnlyCalc($this->isOnlyCalc)
			->getSegmentData($this->entityTypeId, $this->lastEntityId)
		;

		if ($segmentData === null)
		{
			return $result->addError(new Error('Unknown segment collector'));
		}

		if (!$segmentData->canProcessed())
		{
			return $result->addError(new Error('Segment data is not processed'));
		}

		return $this->processSegmentData($segmentData);
	}

	private function processSegmentData(SegmentDataInterface $segmentData): Result
	{
		$result = (new Result());

		$items = $segmentData->getItems();

		$result->setSegmentData($segmentData);
		if (empty($items))
		{
			return $result;
		}

		if ($this->isOnlyCalc)
		{
			return $result;
		}

		$lastAssignmentId = $this->lastAssignmentId;
		$assignmentIds = [];
		if ($this->context)
		{
			$segmentId = $this->context->getSegmentId();
			$entity = RepeatSaleSegmentController::getInstance()->getById($segmentId, true);
			if ($entity)
			{
				$segmentItem = SegmentItem::createFromEntity($entity);
				$assignmentIds = $segmentItem->getAssignmentUserIds();
			}
		}

		foreach ($items as $item)
		{
			$lastAssignmentId = $this->findNextAssignmentId($assignmentIds, $lastAssignmentId);

			// order may be important
			$operation = (new Operation($item, $lastAssignmentId, $this->context))
				->addAction(new CreateDealAction())
				->addAction(new CreateActivityAction())
				->addAction(new LogAction())
			;

			$operation->launch();
		}

		$segmentData->setLastAssignmentId($lastAssignmentId);

		return $result;
	}

	private function findNextAssignmentId(array $userIds, ?int $userId): int
	{
		if (empty($userIds))
		{
			return 1;
		}

		$indexedArray = array_values($userIds);
		if ($userId === null)
		{
			return $indexedArray[0];
		}

		$key = array_search($userId, $indexedArray);

		if ($key === false || $key >= count($indexedArray) - 1)
		{
			return $indexedArray[0];
		}

		return $indexedArray[$key + 1];
	}
}

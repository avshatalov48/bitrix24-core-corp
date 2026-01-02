<?php

namespace Bitrix\Crm\RepeatSale\Service;

use Bitrix\Crm\Item;
use Bitrix\Crm\RepeatSale\Log\Entity\RepeatSaleLogTable;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\RepeatSale\Segment\SegmentItem;
use Bitrix\Crm\RepeatSale\Service\Action\ActionInterface;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;

final class Operation
{
	private array $actions = [];
	private ?SegmentItem $segmentItem = null;
	private static array $itemsCount = [];

	public function __construct(
		private readonly Item $item,
		private readonly int $assignmentUserId,
		private readonly ?Context $context = null,
	)
	{
		if ($this->context)
		{
			$segmentId = $this->context->getSegmentId();
			$entity = RepeatSaleSegmentController::getInstance()->getById($segmentId, true);
			if ($entity)
			{
				$this->segmentItem = SegmentItem::createFromEntity($entity);
			}
		}
	}

	public function addAction(ActionInterface $action): self
	{
		$this->actions[] = $action;

		return $this;
	}

	public function launch(): Result
	{
		$actionsResult = $this->beforeLaunch();
		if (!$actionsResult->isSuccess())
		{
			return $actionsResult;
		}

		if (empty($this->actions))
		{
			return $actionsResult;
		}

		$actionsResult = $this->processActions();
		if (!$actionsResult->isSuccess())
		{
			return $actionsResult;
		}

		return $this->afterLaunch();
	}

	private function beforeLaunch(): Result
	{
		$result = new Result();
		if (!$this->segmentItem)
		{
			return $result;
		}

		$maxOperationsTotal = $this->getMaxOperationsTotal();
		$maxOperationsTotalForSegment = $this->getMaxOperationsTotal($this->segmentItem->getCode());

		if (!$maxOperationsTotalForSegment && !$maxOperationsTotal)
		{
			return $result;
		}

		$itemsCreatedTodayCount = $this->getOperationsTotal();
		$itemsCreatedForSegmentTodayCount = $this->getOperationsTotal($this->segmentItem->getCode());

		if (
			($maxOperationsTotal && $itemsCreatedTodayCount > $maxOperationsTotal)
			|| ($maxOperationsTotalForSegment && $itemsCreatedForSegmentTodayCount > $maxOperationsTotalForSegment)
		)
		{
			return $result->addError(new Error('Too many operations.'));
		}

		return $result;
	}

	private function getMaxOperationsTotal(?string $segmentCode = null): ?int
	{
		$optionName = 'repeat_sale_max_operations_total';
		if ($segmentCode)
		{
			$optionName .= '_' . $segmentCode;
		}

		return \COption::GetOptionInt('crm', $optionName, null);
	}

	private function getOperationsTotal(string $segmentCode = 'all'): int
	{
		if (!isset(self::$itemsCount[$segmentCode]))
		{
			$params = ['>=CREATED_AT' => new Date()];
			if ($segmentCode !== 'all')
			{
				$params[] = ['=SEGMENT_ID', $this->segmentItem->getId()];
			}

			self::$itemsCount[$segmentCode] = RepeatSaleLogTable::getCount($params);
		}

		return self::$itemsCount[$segmentCode];
	}

	private function afterLaunch(): Result
	{
		if (!isset(self::$itemsCount['all']))
		{
			self::$itemsCount['all'] = 0;
		}

		if (!isset(self::$itemsCount[$this->segmentItem->getCode()]))
		{
			self::$itemsCount[$this->segmentItem->getCode()] = 0;
		}

		self::$itemsCount['all']++;
		self::$itemsCount[$this->segmentItem->getCode()]++;

		return new Result();
	}

	private function processActions(): Result
	{
		$actionResult = new Result();

		/** @var ActionInterface $action */
		foreach ($this->actions as $action)
		{
			$actionResult = $action->process(
				$this->item,
				$this->assignmentUserId,
				$actionResult,
				$this->context,
				$this->segmentItem,
			);

			if (!$actionResult->isSuccess())
			{
				return $actionResult;
			}
		}

		return new Result();
	}
}

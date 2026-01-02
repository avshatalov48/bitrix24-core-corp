<?php

namespace Bitrix\Crm\Statistics\OperationFacade;

use Bitrix\Crm\Comparer\ComparerBase;
use Bitrix\Crm\History\StageHistory\AbstractStageHistory;
use Bitrix\Crm\History\StageHistory\EntityStageHistory;
use Bitrix\Crm\History\StageHistoryWithSupposed\AbstractStageHistoryWithSupposed;
use Bitrix\Crm\History\StageHistoryWithSupposed\EntityStageHistoryWithSupposed;
use Bitrix\Crm\History\StageHistoryWithSupposed\TransitionsCalculator;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Statistics\OperationFacade;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

/**
 * @internal
 */
final class Dynamic extends OperationFacade
{
	private AbstractStageHistory $stageHistory;
	private AbstractStageHistoryWithSupposed $supposedHistory;

	public function __construct(
		private readonly Factory $factory
	)
	{
		$this->stageHistory = new EntityStageHistory($this->factory);
		$this->supposedHistory = new EntityStageHistoryWithSupposed(
			new TransitionsCalculator($this->factory),
			$this->factory->getEntityTypeId(),
		);
	}

	public function add(Item $item): Result
	{
		if (!$this->factory->isStagesEnabled())
		{
			return new Result();
		}

		$diff = ComparerBase::compareEntityFields(
			[],
			$item->getData(),
		);

		$result = $this->stageHistory->registerItemAdd($diff);
		$supposedResult = $this->supposedHistory->registerItemAdd($diff);
		if (!$supposedResult->isSuccess())
		{
			$result->addErrors($supposedResult->getErrors());
		}

		return $result;
	}

	public function update(Item $itemBeforeSave, Item $item): Result
	{
		if (!$this->factory->isStagesEnabled())
		{
			return new Result();
		}

		$diff = ComparerBase::compareEntityFields(
			$itemBeforeSave->getData(Values::ACTUAL),
			$item->getData(),
		);

		$result = $this->stageHistory->registerItemUpdate($diff);
		$supposedResult = $this->supposedHistory->registerItemUpdate($diff);
		if (!$supposedResult->isSuccess())
		{
			$result->addErrors($supposedResult->getErrors());
		}

		return $result;
	}

	public function delete(Item $itemBeforeDeletion): Result
	{
		// always delete history regardless of stages settings

		$result = $this->stageHistory->registerItemDelete($itemBeforeDeletion->getId());
		$supposedResult = $this->supposedHistory->registerItemDelete($itemBeforeDeletion->getId());
		if (!$supposedResult->isSuccess())
		{
			$result->addErrors($supposedResult->getErrors());
		}

		return $result;
	}
}

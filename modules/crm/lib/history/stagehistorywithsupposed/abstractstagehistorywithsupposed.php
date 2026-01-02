<?php

namespace Bitrix\Crm\History\StageHistoryWithSupposed;

use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\Item;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Crm\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

abstract class AbstractStageHistoryWithSupposed
{
	public function __construct(
		private readonly TransitionsCalculator $calculator,
	)
	{
	}

	final public function registerItemAdd(Difference $diff, DateTime $now = null): \Bitrix\Main\Result
	{
		$now ??= new DateTime();

		$categoryId = $this->getCategoryId($diff);

		$finish = $diff->getValue(Item::FIELD_NAME_STAGE_ID);
		if (empty($finish))
		{
			// todo decide between results, logs and exceptions. there are too many stuff
			return Result::fail('No stage id provided', 'NO_CURRENT_STAGE_ID');
		}

		$ownerId = $this->getOwnerId($diff);
		if ($ownerId <= 0)
		{
			return Result::fail('No owner id provided', 'NO_OWNER_ID');
		}

		[$transitions, $directive] = $this->calculator->calculateOnItemAdd($categoryId, $finish);

		/** @var Collection $collection */
		$collection = $this->getDataClass()::createCollection();
		foreach ($transitions as $dto)
		{
			$entry = $this->buildEntry($ownerId, $dto, $now);

			if ($directive === CloseDateDirective::SetNow)
			{
				$entry->set('CLOSE_DATE', $now);
			}

			$collection->add($entry);
		}

		return $collection->save(true);
	}

	final public function registerItemUpdate(Difference $diff, DateTime $now = null): \Bitrix\Main\Result
	{
		if (!$diff->isChanged(Item::FIELD_NAME_CATEGORY_ID) && !$diff->isChanged(Item::FIELD_NAME_STAGE_ID))
		{
			return Result::success();
		}

		$now ??= new DateTime();

		$categoryId = $this->getCategoryId($diff);

		$start = $diff->getPreviousValue(Item::FIELD_NAME_STAGE_ID);
		if (empty($start))
		{
			return Result::fail('No stage id provided', 'NO_PREVIOUS_STAGE_ID');
		}

		$finish = $diff->getCurrentValue(Item::FIELD_NAME_STAGE_ID);
		if (empty($finish))
		{
			return Result::fail('No stage id provided', 'NO_CURRENT_STAGE_ID');
		}

		$ownerId = $this->getOwnerId($diff);
		if ($ownerId <= 0)
		{
			return Result::fail('No owner id provided', 'NO_OWNER_ID');
		}

		$overallResult = new Result();

		$lastEntry = $this->getLastNotSupposedEntry($ownerId);
		if ($lastEntry)
		{
			$lastEntry->set('SPENT_TIME', $now->getTimestamp() - $lastEntry->require('CREATED_TIME')->getTimestamp());

			$spentTimeResult = $lastEntry->save();
			if (!$spentTimeResult->isSuccess())
			{
				$overallResult->addErrors($spentTimeResult->getErrors());
			}
		}

		if ($diff->isChanged(Item::FIELD_NAME_CATEGORY_ID))
		{
			[$transitions, $directive] = $this->calculator->calculateOnCategoryChange($categoryId, $start, $finish);
		}
		elseif ($diff->isChanged(Item::FIELD_NAME_STAGE_ID))
		{
			[$transitions, $directive] = $this->calculator->calculateOnStageChange(
				$categoryId,
				$start,
				$finish,
			);
		}
		else
		{
			throw new InvalidOperationException('Unknown case, should never happen');
		}

		/** @var Collection $collection */
		$collection = $this->getDataClass()::createCollection();
		foreach ($transitions as $dto)
		{
			$entry = $this->buildEntry($ownerId, $dto, $now);

			if ($directive === CloseDateDirective::SetLastKnownInNew && $lastEntry)
			{
				$entry->set('CLOSE_DATE', $lastEntry->require('CLOSE_DATE'));
			}

			$collection->add($entry);
		}

		$newEntriesResult = $collection->save(true);
		if (!$newEntriesResult->isSuccess())
		{
			$overallResult->addErrors($newEntriesResult->getErrors());
		}

		if ($directive === CloseDateDirective::SetNow || $directive === CloseDateDirective::Reset)
		{
			$closedDate = $directive === CloseDateDirective::SetNow ? $now : $this->getNullCloseDate();
			$updateResult = $this->updateClosedDateInAllOwnerEntries($ownerId, $closedDate);
			if (!$updateResult->isSuccess())
			{
				$overallResult->addErrors($updateResult->getErrors());
			}
		}

		return $overallResult;
	}

	private function getLastNotSupposedEntry(int $ownerId): ?EntityObject
	{
		$query = $this->getDataClass()::query()
			->setSelect(['ID', 'CREATED_TIME', 'CLOSE_DATE'])
			->where($this->getOwnerFilter($ownerId))
			->where('IS_SUPPOSED', false)
			->addOrder('CREATED_TIME', 'DESC')
			->setLimit(1)
		;

		return $query->fetchObject();
	}

	protected function getOwnerFilter(int $ownerId): ConditionTree
	{
		return (new ConditionTree())->where('OWNER_ID', $ownerId);
	}

	/**
	 * @param int $ownerId
	 *
	 * @return array{
	 *     0: string,
	 *     1: array,
	 * }
	 */
	protected function getOwnerFilterSql(int $ownerId): array
	{
		return ['OWNER_ID = ?i', [$ownerId]];
	}

	private function updateClosedDateInAllOwnerEntries(int $ownerId, Date $closedDate): \Bitrix\Main\Result
	{
		[$filter, $params] = $this->getOwnerFilterSql($ownerId);

		$sql = new SqlExpression(
			'UPDATE ?# SET CLOSE_DATE = ? WHERE ' . $filter,
			$this->getDataClass()::getTableName(),
			$closedDate,
			...$params,
		);

		$this->getDataClass()::getEntity()->getConnection()->queryExecute($sql->compile());
		$this->getDataClass()::cleanCache();

		return Result::success();
	}

	abstract protected function buildEntry(int $ownerId, TransitionDto $dto, DateTime $now): EntityObject;

	final public function registerItemDelete(int $itemId): \Bitrix\Main\Result
	{
		[$filter, $params] = $this->getOwnerFilterSql($itemId);

		$sql = new SqlExpression('DELETE FROM ?# WHERE ' . $filter, $this->getDataClass()::getTableName(), ...$params);

		$this->getDataClass()::getEntity()->getConnection()->queryExecute($sql->compile());
		$this->getDataClass()::cleanCache();

		return Result::success();
	}

	private function getOwnerId(Difference $diff): int
	{
		return (int)$diff->getValue(Item::FIELD_NAME_ID);
	}

	private function getCategoryId(Difference $diff): ?int
	{
		$categoryId = $diff->getValue(Item::FIELD_NAME_CATEGORY_ID);
		if ($categoryId === null)
		{
			return null;
		}

		return (int)$categoryId;
	}

	final protected function getNullCloseDate(): Date
	{
		return new Date('3000-12-12', 'Y-m-d');
	}

	/**
	 * @return class-string<DataManager>
	 */
	abstract protected function getDataClass(): string;
}

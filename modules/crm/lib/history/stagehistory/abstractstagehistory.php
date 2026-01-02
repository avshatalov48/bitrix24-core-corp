<?php

namespace Bitrix\Crm\History\StageHistory;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Comparer\Difference;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;

/**
 * @internal
 */
abstract class AbstractStageHistory
{
	private const PAGINATION_PAGE_SIZE = 500;

	public function __construct(
		private readonly Factory $factory,
	)
	{
	}

	final public function registerItemAdd(Difference $diff, DateTime $now = null): Result
	{
		$now ??= new DateTime();

		return $this->registerItemCreate($diff, $now);
	}

	final public function registerItemUpdate(Difference $diff, DateTime $now = null): Result
	{
		$now ??= new DateTime();

		$overallResult = new Result();

		if ($this->isRelevantOwnerInfoChanged($diff))
		{
			$actualizeResult = $this->actualizeOwnerInfoInExistingEntries($diff);
			if (!$actualizeResult->isSuccess())
			{
				$overallResult->addErrors($actualizeResult->getErrors());
			}
		}

		if (
			$this->hasTransitionOccurred($diff)
			&& !$this->isTransitionRegistered(
				$diff->getValue(Item::FIELD_NAME_ID),
				$diff->getValue(Item::FIELD_NAME_CATEGORY_ID),
				$diff->getValue(Item::FIELD_NAME_STAGE_ID),
			))
		{
			$registerResult = $this->registerTransition($diff, $now);
			if (!$registerResult->isSuccess())
			{
				$overallResult->addErrors($registerResult->getErrors());
			}
		}

		return $overallResult;
	}

	final public function registerItemDelete(int $itemId): Result
	{
		$this->paginateEntries($itemId, function (Collection $entries): void {
			$ids = array_map(fn(EntityObject $o): int => $o->getId(), $entries->getAll());

			$idsString = implode(',', $ids);
			$query = new SqlExpression(
				"DELETE FROM ?# WHERE ?# IN ({$idsString})",
				$this->getDataClass()::getTableName(),
				'ID',
			);

			$connection = $this->getDataClass()::getEntity()->getConnection();
			$connection->queryExecute((string)$query);

			$this->getDataClass()::cleanCache();
		});

		return new Result();
	}

	/**
	 * @return EntityObject[]
	 */
	final public function getListFilteredByPermissions(
		array $parameters,
		?int $userId = null,
		string $operation = UserPermissions::OPERATION_READ,
	): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($userPermissions->getUserId() === 0)
		{
			// no data for unauthorized user
			return [];
		}

		$parameters['filter'] = $userPermissions->itemsList()->applyAvailableItemsFilter(
			$parameters['filter'] ?? [],
			$this->getAllPermissionsEntityTypes(),
			$operation,
			'OWNER_ID'
		);

		return $this->getDataClass()::getList($parameters)->fetchCollection()->getAll();
	}

	public function getItemsCountFilteredByPermissions(
		array $filter,
		?int $userId = null,
		string $operation = UserPermissions::OPERATION_READ
	): int
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		if ($userPermissions->getUserId() === 0)
		{
			// no data for unauthorized user
			return 0;
		}

		$filter = $userPermissions->itemsList()->applyAvailableItemsFilter(
			$filter,
			$this->getAllPermissionsEntityTypes(),
			$operation,
			'OWNER_ID'
		);

		return $this->getDataClass()::getCount($filter);
	}

	private function getAllPermissionsEntityTypes(): array
	{
		$helper = new PermissionEntityTypeHelper($this->factory->getEntityTypeId());

		return $helper->getAllPermissionEntityTypesForEntity();
	}

	private function registerItemCreate(Difference $diff, DateTime $now): Result
	{
		$semantic = $this->getStageSemantics($diff->getValue(Item::FIELD_NAME_STAGE_ID));
		if (PhaseSemantics::isFinal($semantic))
		{
			return $this->createFinalizationEntry($diff, $now, $semantic)->save();
		}

		return $this->createCreationEntry($diff, $now)->save();
	}

	abstract protected function isRelevantOwnerInfoChanged(Difference $diff): bool;

	private function actualizeOwnerInfoInExistingEntries(Difference $diff): Result
	{
		$overallResult = new Result();

		$this->paginateEntries(
			$diff->getValue(Item::FIELD_NAME_ID),
			function (Collection $entries) use ($diff, $overallResult): void {
				foreach ($entries as $entry)
				{
					$this->actualizeOwnerInfo($diff, $entry);
				}

				$saveResult = $entries->save(true);
				if (!$saveResult->isSuccess())
				{
					$overallResult->addErrors($saveResult->getErrors());
				}
			},
		);

		return $overallResult;
	}

	/**
	 * In theory, we can have a lot of entries in history.
	 * Avoid dying with 'out of memory' by loading entries in chunks.
	 *
	 * @param int $ownerId
	 * @param callable(Collection): void $pageProcessor
	 *
	 * @return void
	 */
	private function paginateEntries(int $ownerId, callable $pageProcessor): void
	{
		$lastId = 0;

		while (true)
		{
			$query = $this->getDataClass()::query()
				->setSelect(['ID', 'TYPE_ID'])
				->where($this->getOwnerFilter($ownerId))
				->addOrder('ID')
				->setLimit(self::PAGINATION_PAGE_SIZE)
			;
			if ($lastId > 0)
			{
				$query->where('ID', '>', $lastId);
			}

			/** @var Collection $collection */
			$collection = $query->fetchCollection();

			// capture now in case processor removes entries
			$count = count($collection);
			if ($count <= 0)
			{
				return;
			}

			$collectionArray = $collection->getAll();
			$lastEntry = array_pop($collectionArray);
			if ($lastEntry === null)
			{
				return;
			}

			$pageProcessor($collection);

			if ($count < self::PAGINATION_PAGE_SIZE)
			{
				return;
			}

			$lastId = $lastEntry->getId();
		}
	}

	protected function getOwnerFilter(int $ownerId): ConditionTree
	{
		return (new ConditionTree())
			->where('OWNER_ID', $ownerId)
		;
	}

	abstract protected function actualizeOwnerInfo(Difference $diff, EntityObject $entry): void;

	private function hasTransitionOccurred(Difference $diff): bool
	{
		return $diff->isChanged(Item::FIELD_NAME_CATEGORY_ID) || $diff->isChanged(Item::FIELD_NAME_STAGE_ID);
	}

	private function isTransitionRegistered(int $ownerId, ?int $categoryId, string $stageId): bool
	{
		$latest = $this->getDataClass()::query()
			->where($this->getOwnerFilter($ownerId))
			->addOrder('ID', 'DESC')
			->setLimit(1)
			->fetchObject()
		;

		if (!$latest)
		{
			return false;
		}

		if ($categoryId === null)
		{
			return $latest->require($this->getStageIdField()) === $stageId;
		}

		return (
			$latest->require('CATEGORY_ID') === $categoryId
			&& $latest->require($this->getStageIdField()) === $stageId
		);
	}

	private function registerTransition(Difference $diff, DateTime $now): Result
	{
		$semantic = $this->getStageSemantics($diff->getValue(Item::FIELD_NAME_STAGE_ID));

		if ($diff->isChanged(Item::FIELD_NAME_CATEGORY_ID))
		{
			return $this->registerCategoryChange($diff, $now, $semantic);
		}

		return $this->registerStageChange($diff, $now, $semantic);
	}

	private function registerCategoryChange(Difference $diff, DateTime $now, string $semantics): Result
	{
		return $this->createCategoryChangeEntry($diff, $now, $semantics)->save();
	}

	private function registerStageChange(Difference $diff, DateTime $now, string $semantics): Result
	{
		if (PhaseSemantics::isFinal($semantics))
		{
			return $this->createFinalizationEntry($diff, $now, $semantics)->save();
		}

		return $this->createModificationEntry($diff, $now)->save();
	}

	/**
	 * Called only if item was created on PROCESS stage. If it was created on final stages,
	 * it will be handled by createFinalizationEntry.
	 */
	abstract protected function createCreationEntry(Difference $diff, DateTime $now): EntityObject;

	abstract protected function createModificationEntry(Difference $diff, DateTime $now): EntityObject;

	abstract protected function createFinalizationEntry(
		Difference $diff,
		DateTime $now,
		string $semantics,
	): EntityObject;

	abstract protected function createCategoryChangeEntry(
		Difference $diff,
		DateTime $now,
		string $semantics,
	): EntityObject;

	private function getStageSemantics(string $stageId): string
	{
		return $this->factory->getStageSemantics($stageId);
	}

	final protected function getAssigned(Difference $diff): int
	{
		return $diff->getCurrentValue(Item::FIELD_NAME_ASSIGNED) ?? 0;
	}

	final protected function getEntityTypeId(): int
	{
		return $this->factory->getEntityTypeId();
	}

	private function getStageIdField(): string
	{
		return $this->factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);
	}

	/**
	 * @return class-string<DataManager>
	 */
	abstract protected function getDataClass(): string;
}

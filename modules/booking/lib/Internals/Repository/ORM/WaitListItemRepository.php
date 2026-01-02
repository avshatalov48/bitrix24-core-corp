<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity;
use Bitrix\Booking\Internals\Exception\WaitListItem\CreateWaitListItemException;
use Bitrix\Booking\Internals\Exception\WaitListItem\RemoveWaitListItemException;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Model\WaitListItemTable;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\WaitListItemMapper;
use Bitrix\Booking\Internals\Repository\ORM\Trait\NoteTrait;
use Bitrix\Booking\Internals\Repository\WaitListItemRepositoryInterface;
use Bitrix\Booking\Provider\Params\WaitListItem\WaitListItemFilter;
use Bitrix\Booking\Provider\Params\WaitListItem\WaitListItemSelect;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\QueryHelper;

class WaitListItemRepository implements WaitListItemRepositoryInterface
{
	use NoteTrait;

	private WaitListItemMapper $mapper;

	public function __construct(WaitListItemMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	public function getList(
		int $limit = null,
		int $offset = null,
		ConditionTree|null $filter = null,
		array|null $sort = null,
		array|null $select = null,
		int|null $userId = null,
	): Entity\WaitListItem\WaitListItemCollection
	{
		$query = WaitListItemTable::query()
			->setSelect(array_merge(['*'], $select ?: []))
		;

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		if ($offset !== null)
		{
			$query->setOffset($offset);
		}

		if ($filter !== null)
		{
			$query->where($filter);
		}

		if ($sort !== null)
		{
			$query->setOrder($sort);
		}

		$ormWaitListItems = QueryHelper::decompose($query);

		$waitListItems = [];
		foreach ($ormWaitListItems as $ormWaitListItem)
		{
			$waitListItems[] = $this->mapper->convertFromOrm($ormWaitListItem);
		}

		return new Entity\WaitListItem\WaitListItemCollection(...$waitListItems);
	}

	public function save(Entity\WaitListItem\WaitListItem $waitListItem, string $exceptionClass = null): int
	{
		$ormWaitListItem = $this->mapper->convertToOrm($waitListItem);
		$result = $ormWaitListItem->save();
		if (!$result->isSuccess())
		{
			$exceptionClass = $exceptionClass ?? CreateWaitListItemException::class;

			throw new $exceptionClass($result->getErrors()[0]->getMessage());
		}

		$this->handleNote($ormWaitListItem, $waitListItem->getNote(), $result->getId(), EntityType::WaitList);

		return $result->getId();
	}

	public function getById(int $waitListItemId, int $userId = 0): Entity\WaitListItem\WaitListItem|null
	{
		$waitListItemCollection = $this->getList(
			limit: 1,
			filter: (new WaitListItemFilter(['ID' => $waitListItemId]))->prepareFilter(),
			select: (new WaitListItemSelect([
				'CLIENTS',
				'EXTERNAL_DATA',
				'NOTE',
			]))->prepareSelect(),
			userId: $userId,
		);

		return $waitListItemCollection->getFirstCollectionItem();
	}

	public function remove(int $id): void
	{
		$result = WaitListItemTable::update($id, ['IS_DELETED' => 'Y']);
		if (!$result->isSuccess())
		{
			throw new RemoveWaitListItemException($result->getErrors()[0]->getMessage());
		}
	}
}

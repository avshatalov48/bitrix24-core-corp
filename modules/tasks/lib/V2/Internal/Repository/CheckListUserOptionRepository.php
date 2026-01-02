<?php

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\DB\DuplicateEntryException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Exception\CheckList\UserOptionException;
use Bitrix\Tasks\V2\Internal\Model\CheckListUserOptionTable;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\CheckListUserOptionMapper;

class CheckListUserOptionRepository implements CheckListUserOptionRepositoryInterface
{
	public function __construct(
		private readonly CheckListUserOptionMapper $userOptionMapper,
	)
	{}

	public function get(int $itemId, int $userId): Entity\CheckList\UserOptionCollection
	{
		$list = CheckListUserOptionTable::query()
			->setSelect(['ID', 'OPTION_CODE'])
			->where('ITEM_ID', $itemId)
			->where('USER_ID', $userId)
			->exec()
			->fetchAll();

		$data = [];
		foreach ($list as $item)
		{
			$data[] = [
				'ID' => (int)$item['ID'],
				'OPTION_CODE' => (int)$item['OPTION_CODE'],
				'ITEM_ID' => (int)$item['ITEM_ID'],
				'USER_ID' => $userId,
			];
		}

		return $this->userOptionMapper->mapToCollection($data);
	}

	/**
	 * @param int $userId User id.
	 * @param array $itemIds CheckList item ids.
	 * @param array $optionCodes Option codes from Entity\CheckList\Option
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isSet(int $userId, array $itemIds, array $optionCodes = []): array
	{
		if (empty($itemIds))
		{
			return [];
		}

		$query = CheckListUserOptionTable::query()
			->setSelect(['OPTION_CODE', 'ITEM_ID'])
			->whereIn('ITEM_ID', $itemIds)
			->where('USER_ID', $userId);

		if (!empty($optionCodes))
		{
			$query->whereIn('OPTION_CODE', $optionCodes);
		}

		$list = $query->exec()->fetchAll();

		$result = [];
		foreach ($list as $item)
		{
			$itemId = (int)$item['ITEM_ID'];
			$optionCode = (int)$item['OPTION_CODE'];

			$result[$itemId][$optionCode] = true;
		}

		return $result;
	}

	public function add(Entity\CheckList\UserOption $userOption): void
	{
		$data = $this->userOptionMapper->mapFromEntity($userOption);

		try
		{
			$result = CheckListUserOptionTable::add($data);
		}
		catch (DuplicateEntryException)
		{
			return;
		}

		if (!$result->isSuccess())
		{
			throw new UserOptionException($result->getError()?->getMessage());
		}
	}

	public function delete(int $userId, int $itemId = 0, array $codes = []): void
	{
		$filter = [
			'=USER_ID' => $userId,
		];

		Collection::normalizeArrayValuesByInt($codes, false);
		if (!empty($codes))
		{
			$filter['@OPTION_CODE'] = $codes;
		}

		if ($itemId > 0)
		{
			$filter['=ITEM_ID'] = $itemId;
		}

		try
		{
			CheckListUserOptionTable::deleteByFilter($filter);
		}
		catch (SqlQueryException $e)
		{
			throw new UserOptionException($e->getMessage());
		}
	}
}

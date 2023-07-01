<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\DB;
use CCrmOwnerType;
use CDBResult;

class CrmBase extends \Bitrix\Main\UI\Selector\EntityBase
{
	public const PREFIX_SHORT = '';
	public const PREFIX_FULL = '';

	protected static function getOwnerType()
	{
		return CCrmOwnerType::Undefined;
	}

	protected static function getOwnerTypeName()
	{
		return CCrmOwnerType::ResolveName(static::getOwnerType());
	}

	protected static function getHandlerType()
	{
		return '';
	}

	protected function getEntityClassName(): string
	{
		$ownerTypeName = mb_strtolower($this->getOwnerTypeName());

		return 'CCrm' . mb_strtoupper(mb_substr($ownerTypeName, 0, 1)) . mb_substr($ownerTypeName, 1);
	}

	protected static function getPrefix($options = []): string
	{
		return
			(
				is_array($options)
				&& isset($options['prefixType'])
				&& mb_strtolower($options['prefixType']) === 'short'
			)
				? static::PREFIX_SHORT
				: static::PREFIX_FULL
		;
	}

	protected function getSearchOrder(): array
	{
		return [];
	}

	protected function getByIdsOrder(): array
	{
		return $this->getSearchOrder();
	}

	protected function getSearchSelect(): array
	{
		return ['*'];
	}

	protected function getByIdsSelect(): array
	{
		return $this->getSearchSelect();
	}

	protected function prepareOptionalFilter(array $filter, array $options): array
	{
		return $filter;
	}

	protected function getByIdsFilter(array $ids, array $options): array
	{
		return $this->prepareOptionalFilter(['@ID' => $ids], $options);
	}

	protected function getByIdsListMethodName(): string
	{
		return 'getListEx';
	}

	protected function getByIdsRes(array $ids, array $options)
	{
		$listMethodName = $this->getByIdsListMethodName();
		$result = $this->getEntityClassName()::$listMethodName(
			$this->getByIdsOrder(),
			$this->getByIdsFilter($ids, $options),
			false,
			false,
			$this->getByIdsSelect()
		);

		if (!is_object($result))
		{
			return null;
		}

		return $result;
	}

	public function processResultItems(array $items, array $options = []): array
	{
		return $items;
	}

	protected function getByIdsResultItems(array $ids, array $options): array
	{
		$result = [];

		$prefix = static::getPrefix($options);
		$res = $this->getByIdsRes($ids, $options);
		if (is_object($res))
		{
			while ($row = $res->fetch())
			{
				$result[$prefix . $row['ID']] = static::prepareEntity($row, $options);
			}
		}

		return $result;
	}

	public function getByIds(array $ids, array $options): array
	{
		$result = [];

		$verifiedIds = [];
		foreach ($ids as $idValue)
		{
			$id = (int)$idValue;
			if ($id > 0)
			{
				$verifiedIds[] = $id;
			}
		}
		$ids = $verifiedIds;
		unset($verifiedIds);

		if (empty($ids))
		{
			return $result;
		}

		return $this->getByIdsResultItems($ids, $options);
	}

	protected function appendItemsByIds(array $items, string $search, array $options): array
	{
		$itemsById = [];
		$searchId = (int)$search;
		if (
			$searchId > 0
			&& isset($options['searchById'])
			&& $options['searchById'] === 'Y'
			&& (string)$searchId === $search
		)
		{
			$itemsById = $this->getByIds([$searchId], $options);
		}

		if (!empty($itemsById))
		{
			$items = array_merge($items, $itemsById);
		}

		return $items;
	}
}

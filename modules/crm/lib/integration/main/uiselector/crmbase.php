<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\DB;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use CCrmCompany;
use CCrmContact;
use CCrmDeal;
use CCrmLead;
use CCrmOwnerType;
use CDBResult;

class CrmBase extends \Bitrix\Main\UI\Selector\EntityBase
{
	public const PREFIX_SHORT = '';
	public const PREFIX_FULL = '';

	protected const DATA_CLASS = '';
	private const CACHE_TIME = 3600;
	protected const CACHE_DIR = '';

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::Undefined;
	}

	protected static function getOwnerTypeName(): string
	{
		return CCrmOwnerType::ResolveName(static::getOwnerType());
	}

	protected static function getHandlerType(): string
	{
		return '';
	}

	protected function getEntityClassName(): string
	{
		$ownerTypeName = mb_strtolower(static::getOwnerTypeName());

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

	protected function getEntitiesListEx
	(
		$order = [],
		$filter = [],
		$groupBy = false,
		$navParams = [],
		$select = [],
		$entityOptions = [],
	): array
	{
		/* @var CCrmContact|CCrmLead|CCrmCompany|CCrmDeal $dataClass */
		$dataClass = static::DATA_CLASS;
		if (!method_exists($dataClass, 'getListEx'))
		{
			return [];
		}

		$entitiesList = [];
		$useCache = empty($filter['ID']) && empty($filter['@ID']) && empty($filter['=ID']);
		if ($useCache)
		{
			$userId = Container::getInstance()->getContext()->getUserId();
			$cache = Application::getInstance()->getManagedCache();
			$cacheId = "crm_uiselector_data_{$userId}_" . md5(serialize([
				$dataClass,
				$order,
				$filter,
				$select,
				$groupBy,
				$navParams,
			]));

			if ($cache->read(self::CACHE_TIME, $cacheId, static::CACHE_DIR))
			{
				return $cache->get($cacheId);
			}

			$ids = [];
			$idsResult = $dataClass::getListEx($order, $filter, $groupBy, $navParams, ['ID']);
			while($idsFields = $idsResult->fetch())
			{
				$ids[] = $idsFields['ID'];
			}

			if (empty($ids))
			{
				return [];
			}

			$filter = [
				'@ID' => $ids,
				'CHECK_PERMISSION' => 'N',
			];
		}

		$prefix = static::getPrefix($entityOptions);
		$entityResult = $dataClass::getListEx($order, $filter, $groupBy, $navParams, $select);
		while ($entityFields = $entityResult->fetch())
		{
			$entitiesList[$prefix . $entityFields['ID']] =
				static::prepareEntity($entityFields, $entityOptions)
			;
		}

		if ($useCache)
		{
			$cache->set($cacheId, $entitiesList);
		}

		return $entitiesList;
	}
}

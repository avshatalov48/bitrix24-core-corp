<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\Integration\Main\UISelector\EntitySelection;
use Bitrix\Main\ArgumentException;
use CCrmCompany;
use CCrmContact;
use CCrmLead;

class CrmEntity extends CrmBase
{
	protected const SORT_SELECTED = 100;
	protected const ENTITIES_MAX_COUNT = 20;
	protected const COUNT_TO_ADD_ENTITIES = 3;

	protected const SELECTED_ITEMS_STRATEGY = EntitySelection\Preparer::SELECTED_ITEMS_STRATEGY;
	protected const LAST_ITEMS_STRATEGY = EntitySelection\Preparer::LAST_ITEMS_STRATEGY;
	protected const HIDDEN_ITEMS_STRATEGY = EntitySelection\Preparer::HIDDEN_ITEMS_STRATEGY;

	public static function getMultiKey($key, $email): string
	{
		return $key . ':'.mb_substr(md5($email), 0, 8);
	}

	protected static function processMultiFields(array $entityList = [], array $entityOptions = []): array
	{
		$result = $entityList;

		if (
			empty($entityOptions['returnMultiEmail'])
			|| $entityOptions['returnMultiEmail'] !== 'Y'
		)
		{
			return $result;
		}

		foreach ($result as $key => $entity)
		{
			if (!empty($entity['multiEmailsList']))
			{
				foreach($entity['multiEmailsList'] as $email)
				{
					$newKey = static::getMultiKey($key, $email);
					$result[$newKey] = $entity;
					$result[$newKey]['id'] = $newKey;
					$result[$newKey]['email'] = $email;

					if (
						isset($entityOptions['onlyWithEmail'])
						&& $entityOptions['onlyWithEmail'] === 'Y'
					)
					{
						$result[$newKey]['desc'] = $email;
					}

					unset($result[$newKey]['multiEmailsList']);
				}
			}

			unset($result[$key]);
		}

		return $result;
	}

	public static function prepareToken($str): string
	{
		return str_rot13($str);
	}

	public function processResultItems(array $items, array $options = []): array
	{
		return static::processMultiFields($items, $options);
	}

	/**
	 * @throws ArgumentException
	 */
	public function getData(array $params = []): array
	{
		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => $this->getGroupsList(),
				'SORT_SELECTED' => static::SORT_SELECTED,
			],
		];

		$paramsEntityOptions = $params['options'] ?? [];
		$entityOptions = is_array($paramsEntityOptions) ? $paramsEntityOptions : [];

		$preparer = $this->getEntitiesPreparer($entityOptions);

		$paramsLastItems = $params['lastItems'] ?? [];
		$lastItems = is_array($paramsLastItems) ? $paramsLastItems : [];
		[$lastEntities, $lastEntitiesIds] = $preparer
			->prepare($lastItems, static::LAST_ITEMS_STRATEGY)
			->toArray()
		;

		$paramsSelectedItems = $params['selectedItems'] ?? [];
		$selectedItems = is_array($paramsSelectedItems) ? $paramsSelectedItems : [];
		[$selectedEntities, $selectedEntitiesIds] = $preparer
			->prepare($selectedItems,static::SELECTED_ITEMS_STRATEGY)
			->toArray()
		;

		$entitiesIdMaxCount = max(count($selectedEntitiesIds), static::ENTITIES_MAX_COUNT);
		$entitiesIdList = array_unique(array_merge($selectedEntitiesIds, $lastEntitiesIds));
		$entitiesIdList = array_slice($entitiesIdList, 0, $entitiesIdMaxCount);

		$order = empty($entitiesIdList) ? ['ID' => 'DESC'] : [];
		$navParams = empty($entitiesIdList) ? ['nTopCount' => 10] : false;
		$select = $this->getSearchSelect();
		$filter = $this->getInitFilter($entitiesIdList, $selectedEntitiesIds);
		$filter = $this->prepareOptionalFilter($filter, $entityOptions);

		$entitiesList = $this->getEntitiesListEx(
			$order,
			$filter,
			false,
			$navParams,
			$select,
			$entityOptions,
		);

		$addEntitiesList = [];
		if (
			!empty($entitiesIdList)
			&& count($entitiesList) < static::COUNT_TO_ADD_ENTITIES
		)
		{
			unset($filter['ID']);
			$addEntitiesList = $this->getEntitiesListEx(
				['ID' => 'DESC'],
				$filter,
				false,
				['nTopCount' => 10],
				$select,
				$entityOptions,
			);
		}

		$entitiesList = array_merge($addEntitiesList, $entitiesList);
		$entitiesListKeys = array_keys($entitiesList);

		$entitiesList = static::processMultiFields($entitiesList, $entityOptions);

		$result['ITEMS'] = $entitiesList;
		$result['ITEMS_LAST'] = empty($lastEntitiesIds) ? $entitiesListKeys : $lastEntities;

		if (!empty($selectedEntities))
		{
			$hiddenItems = array_diff($selectedEntities, $entitiesListKeys);
			$hiddenEntitiesIds = $preparer
				->prepare($hiddenItems, static::HIDDEN_ITEMS_STRATEGY)
				->getEntitiesIDs()
			;

			$this->setHiddenItems($result, $hiddenEntitiesIds, $entityOptions);
		}

		return $result;
	}

	protected function getEntitiesPreparer(array $entityOptions): EntitySelection\Preparer
	{
		$entityForPrepare = (new EntitySelection\Entity())
			->setType(static::getHandlerType())
			->setPrefix(static::getPrefix($entityOptions))
			->setFullPrefix(static::PREFIX_FULL)
		;

		return (new EntitySelection\Preparer($entityForPrepare));
	}

	protected function getInitFilter(array $entitiesIds = [], array $selectedEntitiesIds = []): array
	{
		$filter = [
			'CHECK_PERMISSIONS' => 'Y',
		];

		if (empty($entitiesIds))
		{
			$filter['@CATEGORY_ID'] = 0;
		}
		else
		{
			$filter['ID'] = $entitiesIds;
			if (empty($selectedEntitiesIds))
			{
				$filter['@CATEGORY_ID'] = 0;
			}
		}

		return $filter;
	}

	protected function getGroupsList(): array
	{
		return [];
	}

	protected function setHiddenItems(array &$result, array $hiddenItemIds, $entityOptions = []): void
	{
		if (empty($hiddenItemIds))
		{
			return;
		}

		$filter = [
			'@ID' => $hiddenItemIds,
			'CHECK_PERMISSIONS' => 'N',
		];

		if (
			isset($entityOptions['onlyWithEmail'])
			&& $entityOptions['onlyWithEmail'] === 'Y'
		)
		{
			$filter['=HAS_EMAIL'] = 'Y';
		}

		/**@var CCrmCompany|CCrmContact|CCrmLead $entityDataClass*/
		$entityDataClass = static::DATA_CLASS;
		$prefix = static::getPrefix($entityOptions);
		if (method_exists($entityDataClass, 'getListEx'))
		{
			$hiddenEntitiesResult = $entityDataClass::getListEx(
				[],
				$filter,
				false,
				false,
				['ID'],
			);

			while ($hiddenEntity = $hiddenEntitiesResult->fetch())
			{
				$result['ITEMS_HIDDEN'][] = $prefix . $hiddenEntity['ID'];
			}
		}
	}
}

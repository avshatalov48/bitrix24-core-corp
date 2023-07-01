<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Filter\ItemSettings;
use Bitrix\Crm\Item\Dynamic;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DB;
use Bitrix\Main\Text\HtmlFilter;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;
use CDBResult;

class CrmDynamics extends CrmEntity
{
	public const PREFIX_FULL = 'CRMDYNAMIC-';

	protected static function getHandlerType()
	{
		return Handler::ENTITY_TYPE_CRMDYNAMICS;
	}

	protected static function getPrefix($options = []): string
	{
		$prefix = (
			is_array($options)
			&& isset($options['prefixType'])
			&& mb_strtolower($options['prefixType']) === 'short'
				? CCrmOwnerTypeAbbr::ResolveByTypeID($options['typeId'])
				: self::PREFIX_FULL . $options['typeId']
		);

		return $prefix . '_';
	}

	protected static function prepareEntity(Dynamic $item, ?array $options = [])
	{
		$prefix = static::getPrefix($options);
		$result = [
			'id' => $prefix . $item->getId(),
			'entityType' => 'dynamic_' . $item->getEntityTypeId(),
			'entityId' => $item->getId(),
			'name' => HtmlFilter::encode($item->getTitle()),
			'desc' => '',
			'date' => $item->getCreatedTime()->getTimestamp()
		];

		if (isset($options['returnItemUrl']) && $options['returnItemUrl'] === 'Y')
		{
			$result['url'] = Container::getInstance()->getRouter()->getItemDetailUrl(
				$item->getEntityTypeId(),
				$item->getId()
			)->getUri();
			$result['urlUseSlider'] = 'Y';
		}

		return $result;
	}

	public function getData($params = [])
	{
		if (empty($params['options']['title']))
		{
			return [];
		}

		$entityTypeId = (int)$params['options']['typeId'];
		$entityType = static::getHandlerType() . '_' . $entityTypeId;

		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => [
					'crmdynamics_' . $entityTypeId => [
						'TITLE' => $params['options']['title'],
						'TYPE_LIST' => [$entityType],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 40
					]
				],
				'SORT_SELECTED' => 400
			]
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$prefix = static::getPrefix($entityOptions);

		$lastItemIds = [];
		$selectedItemIds = [];

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : []);
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : []);

		if(!empty($lastItems[$entityType]))
		{
			$result['ITEMS_LAST'] = array_map(
				static function($code) use ($prefix) {
					return preg_replace('/^'.self::PREFIX_FULL . '(\d+)$/', $prefix . '$1', $code);
				},
				array_values($lastItems[$entityType])
			);
			foreach ($lastItems[$entityType] as $value)
			{
				$lastItemIds[] = str_replace(self::PREFIX_FULL, '', $value);
			}
		}
		if (!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedItemIds[] = str_replace($prefix, '', $value);
			}
		}

		$itemIds = array_merge($lastItemIds, $selectedItemIds);
		if (count($itemIds) > 20)
		{
			$itemIds = array_slice($itemIds, 0, 20);
		}
		$itemIds = array_unique($itemIds);

		$entitiesList = [];

		$list = [];
		$factory = Container::getInstance()->getFactory($params['options']['typeId']);
		if ($factory)
		{
			$parameters = [
				'order' => ['ID' => 'DESC'],
				'limit' => 10,
			];
			if (!empty($itemIds))
			{
				$parameters = [
					'filter' => ['@ID' => $itemIds],
				];
			}
			$list = $factory->getItemsFilteredByPermissions($parameters);
		}

		foreach ($list as $item)
		{
			$entitiesList[$prefix . $item['ID']] = static::prepareEntity($item, $entityOptions);
		}

		if (empty($lastItemIds))
		{
			$result['ITEMS_LAST'] = array_keys($entitiesList);
		}

		$result['ITEMS'] = $entitiesList;

		return $result;
	}

	public function getTabList($params = [])
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (empty($params['options']['title']))
		{
			return $result;
		}

		if (isset($options['addTab']) && $options['addTab'] === 'Y')
		{
			$result = [
				[
					'id' => 'dynamics_' . (int) $params['options']['typeId'],
					'name' => $params['options']['title'],
					'sort' => 50
				]
			];
		}

		return $result;
	}

	public function search($params = [])
	{
		$result = [
			'ITEMS' => [],
			'ADDITIONAL_INFO' => []
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : []);
		$search = $requestFields['searchString'];
		$prefix = static::getPrefix($entityOptions);
		$entityTypeId = (int)$params['options']['typeId'];

		if (
			$search <> ''
			&& (empty($entityOptions['enableSearch']) || $entityOptions['enableSearch'] !== 'N')
		)
		{
			$filter = $this->getSearchFilter($search, $entityOptions);

			if ($filter === false)
			{
				return $result;
			}

			$list = Container::getInstance()->getFactory($entityTypeId)->getItemsFilteredByPermissions(
				[
					'order' => $this->getSearchOrder(),
					'select' => $this->getSearchSelect(),
					'limit' => 20,
					'filter' => $filter,
				]
			);

			$resultItems = [];

			foreach ($list as $item)
			{
				$resultItems[$prefix . $item->getId()] = static::prepareEntity($item, $entityOptions);
			}

			$resultItems = $this->appendItemsByIds($resultItems, $search, $entityOptions);

			$resultItems = $this->processResultItems($resultItems, $entityOptions);

			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}

	protected function getSearchFilter(string $search, array $options)
	{
		$filter = [];
		$entityTypeId = (int)$options['typeId'];
		$type = Container::getInstance()->getTypeByEntityTypeId($entityTypeId);
		$settings = new ItemSettings(['ID' => 'crm-element-field-' . $entityTypeId], $type);
		$factory = Container::getInstance()->getFactory($entityTypeId);
		$provider = new ItemDataProvider($settings, $factory);
		$provider->prepareListFilter($filter, ['FIND' => $search]);

		return
			empty($filter)
				? false
				: $this->prepareOptionalFilter($filter, $options)
		;
	}

	protected function getByIdsRes(array $ids, array $options)
	{
		return null;
	}

	protected function getByIdsResultItems(array $ids, array $options): array
	{
		$result = [];

		$prefix = static::getPrefix($options);
		$entityTypeId = (int)($options['typeId'] ?? CCrmOwnerType::Undefined);

		$list = Container::getInstance()->getFactory($entityTypeId)->getItemsFilteredByPermissions(
			[
				'order' => $this->getByIdsOrder(),
				'select' => $this->getByIdsSelect(),
				'filter' => $this->getByIdsFilter($ids, $options),
			]
		);

		foreach ($list as $item)
		{
			$result[$prefix . $item->getId()] = static::prepareEntity($item, $options);
		}

		return $result;
	}
}

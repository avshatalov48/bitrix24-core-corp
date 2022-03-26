<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\Filter\ItemDataProvider;
use Bitrix\Crm\Filter\ItemSettings;
use Bitrix\Crm\Item\Dynamic;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Text\HtmlFilter;

class CrmDynamics extends CrmEntity
{
	public const PREFIX_FULL = 'CRMDYNAMIC-';

	protected static function getPrefix($options = [])
	{
		$prefix = (
			is_array($options)
			&& isset($options['prefixType'])
			&& mb_strtolower($options['prefixType']) === 'short'
				? \CCrmOwnerTypeAbbr::ResolveByTypeID($options['typeId'])
				: self::PREFIX_FULL . $options['typeId']
		);

		return $prefix . '_';
	}

	protected static function prepareEntity(Dynamic $item, ?array $options = [])
	{
		$prefix = self::getPrefix($options);
		$result = [
			'id' => $prefix.$item->getId(),
			'entityType' => 'dynamic_'.$item->getEntityTypeId(),
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
		$entityType = Handler::ENTITY_TYPE_CRMDYNAMICS . '_' . $entityTypeId;

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
		$prefix = self::getPrefix($entityOptions);

		$lastItemIds = [];
		$selectedItemIds = [];

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : []);
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : []);

		if(!empty($lastItems[$entityType]))
		{
			$result['ITEMS_LAST'] = array_map(
				static function($code) use ($prefix) {
					return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code);
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
					'filter' => [
						'@ID' => $itemIds,
					],
				];
			}
			$list = $factory->getItemsFilteredByPermissions($parameters);
		}

		foreach ($list as $item)
		{
			$entitiesList[$prefix.$item['ID']] = self::prepareEntity($item, $entityOptions);
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
					'id' => 'dynamics_'.(int) $params['options']['typeId'],
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
		$prefix = self::getPrefix($entityOptions);
		$entityTypeId = (int)$params['options']['typeId'];

		if (
			$search <> ''
			&& (empty($entityOptions['enableSearch']) || $entityOptions['enableSearch'] !== 'N')
			&& ($type = Container::getInstance()->getTypeByEntityTypeId($entityTypeId))
		)
		{
			$filter = [];

			$settings = new ItemSettings([
				'ID' => 'crm-element-field-'.$entityTypeId,
			], $type);
			$factory = Container::getInstance()->getFactory($entityTypeId);
			$provider = new ItemDataProvider($settings, $factory);
			$provider->prepareListFilter($filter, ['FIND' => $search]);

			$list = Container::getInstance()->getFactory($entityTypeId)->getItemsFilteredByPermissions([
				'select' => ['*'],
				'limit' => 20,
				'filter' => $filter,
			]);

			$resultItems = [];
			foreach ($list as $item)
			{
				$resultItems[$prefix.$item->getId()] = self::prepareEntity($item, $entityOptions);
			}

			$result['ITEMS'] = $resultItems;
		}

		return $result;
	}
}

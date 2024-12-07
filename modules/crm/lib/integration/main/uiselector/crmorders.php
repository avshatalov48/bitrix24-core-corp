<?php

namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Crm\Order\Order;
use Bitrix\Main\DB;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use CCrmOwnerType;
use CCrmPerms;
use CDBResult;

class CrmOrders extends CrmBase
{
	public const PREFIX_SHORT = 'O_';
	public const PREFIX_FULL = 'CRMORDER';

	protected static function getOwnerType(): int
	{
		return CCrmOwnerType::Order;
	}

	protected static function getHandlerType(): string
	{
		return Handler::ENTITY_TYPE_CRMORDERS;
	}

	protected static function prepareEntity($data, $options = []): array
	{
		$prefix = static::getPrefix($options);
		$result = [
			'id' => $prefix . $data['ID'],
			'entityType' => 'orders',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx(
				$data['ACCOUNT_NUMBER'] . ($data['ORDER_TOPIC'] <> '' ? ' "' . $data['ORDER_TOPIC'] . '"' : '')
			),
			'desc' => ''
		];

		if (array_key_exists('DATE_INSERT', $data))
		{
			$result['date'] = MakeTimeStamp($data['DATE_INSERT']);
		}

		if (
			isset($options['returnItemUrl'])
			&& $options['returnItemUrl'] == 'Y'
		)
		{
			$result['url'] = CCrmOwnerType::getEntityShowPath(CCrmOwnerType::Order, $data['ID']);
			$result['urlUseSlider'] = (CCrmOwnerType::isSliderEnabled(CCrmOwnerType::Order) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = []): array
	{
		global $USER;

		$entityType = static::getHandlerType();

		$result = [
			'ITEMS' => [],
			'ITEMS_LAST' => [],
			'ITEMS_HIDDEN' => [],
			'ADDITIONAL_INFO' => [
				'GROUPS_LIST' => [
					'crmorders' => [
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMORDERS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 60,
					],
				],
				'SORT_SELECTED' => 400,
			],
		];

		$userPermissions = CCrmPerms::getCurrentUserPermissions();

		if (!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $userPermissions))
		{
			return $result;
		}

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$prefix = static::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : []);
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : []);

		$lastOrdersIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(
				function($code) use ($prefix)
				{
					return preg_replace('/^'.self::PREFIX_FULL . '(\d+)$/', $prefix . '$1', $code);
				},
				array_values($lastItems[$entityType])
			);
			foreach ($lastItems[$entityType] as $value)
			{
				$lastOrdersIdList[] = intval(str_replace(self::PREFIX_FULL, '', $value));
			}
		}

		$selectedOrdersIdList = [];

		if(!empty($selectedItems[$entityType]))
		{
			foreach ($selectedItems[$entityType] as $value)
			{
				$selectedOrdersIdList[] = intval(str_replace($prefix, '', $value));
			}
		}

		$ordersIdList = array_merge($selectedOrdersIdList, $lastOrdersIdList);
		$ordersIdList = array_slice($ordersIdList, 0, max(count($selectedOrdersIdList), 20));
		$ordersIdList = array_unique($ordersIdList);

		$ordersList = [];

		$filter = [];
		$permissionSql = '';

		if (!(is_object($USER) && $USER->isAdmin()))
		{
			$options = [
				'RAW_QUERY' => true,
				'PERMS'=> CCrmPerms::getCurrentUserPermissions()
			];

			if (!empty($ordersIdList))
			{
				$options['RESTRICT_BY_IDS'] = $ordersIdList;
			}

			$permissionSql = CCrmPerms::buildSql(
				CCrmOwnerType::OrderName,
				'',
				'READ',
				$options
			);
		}

		if($permissionSql <> '')
		{
			$filter['@ID'] = new SqlExpression($permissionSql);
		}
		elseif (!empty($ordersIdList))
		{
			$filter['@ID'] = $ordersIdList;
		}

		if (empty($ordersIdList))
		{
			$order = ['ID' => 'DESC'];
			$limit = 10;
		}
		else
		{
			$order = [];
			$limit = 0;
		}

		$res = Order::getList([
			'order' =>  $order,
			'select' =>  $this->getSearchSelect(),
			'filter' =>  $filter,
			'limit' => $limit
		]);

		while ($orderFields = $res->fetch())
		{
			$ordersList[$prefix . $orderFields['ID']] = static::prepareEntity($orderFields, $entityOptions);
		}

		if (empty($lastOrdersIdList))
		{
			$result["ITEMS_LAST"] = array_keys($ordersList);
		}

		$result['ITEMS'] = $ordersList;

		return $result;
	}

	public function getTabList($params = []): array
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] == 'Y'
		)
		{
			$result = [
				[
					'id' => 'orders',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMORDERS'),
					'sort' => 60,
				],
			];
		}

		return $result;
	}

	public function search($params = []): array
	{
		$result = [
			'ITEMS' => [],
			'ADDITIONAL_INFO' => [],
		];

		$entityOptions = (!empty($params['options']) ? $params['options'] : []);
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : []);
		$search = $requestFields['searchString'];
		$prefix = static::getPrefix($entityOptions);

		if (
			$search <> ''
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = $this->getSearchFilter($search, $entityOptions);

			if ($filter === false)
			{
				return $result;
			}

			$res = Order::getList(
				[
					'order' => $this->getSearchOrder(),
					'select' => $this->getSearchSelect(),
					'filter' => $filter,
					'limit' => 20,
				]
			);

			$resultItems = [];

			while ($orderFields = $res->fetch())
			{
				$resultItems[$prefix . $orderFields['ID']] =
					static::prepareEntity($orderFields, $entityOptions)
				;
			}

			$resultItems = $this->appendItemsByIds($resultItems, $search, $entityOptions);

			$resultItems = $this->processResultItems($resultItems, $entityOptions);

			$result["ITEMS"] = $resultItems;
		}

		return $result;
	}

	protected function getSearchSelect(): array
	{
		return [
			'ID',
			'ACCOUNT_NUMBER',
			'DATE_INSERT',
			'ORDER_TOPIC',
		];
	}

	protected function getSearchFilter(string $search, array $options)
	{
		global $USER;

		$operation = Loader::includeModule('sale') ? '*' : '*%';

		$filter = [$operation . 'SEARCH_CONTENT' => CrmEntity::prepareToken($search)];

		if (!(is_object($USER) && $USER->isAdmin()))
		{
			$queryOptions = [
				'RAW_QUERY' => true,
				'PERMS'=> CCrmPerms::getCurrentUserPermissions()
			];

			$permissionSql = CCrmPerms::buildSql(
				static::getOwnerTypeName(),
				'',
				'READ',
				$queryOptions
			);

			if($permissionSql <> '')
			{
				$filter['@ID'] = new SqlExpression($permissionSql);
			}
		}

		$subFilter = [
			'%ORDER_TOPIC' => $search,
			'LOGIC' => 'OR'
		];

		if (is_numeric($search))
		{
			$subFilter['ID'] = (int)$search;
			$subFilter['%ACCOUNT_NUMBER'] = $search;
		}
		else if (preg_match('/( . *)\[(\d+?)\]/iu', $search, $matches))
		{
			$subFilter['ID'] = (int)$matches[2];
			$subFilter['%ACCOUNT_NUMBER'] = trim($matches[1]);
		}
		else
		{
			$subFilter['%ACCOUNT_NUMBER'] = $search;
		}

		if (!empty($filter))
		{
			$filter[] = $subFilter;
		}

		return empty($filter) ? false : $this->prepareOptionalFilter($filter, $options);
	}

	protected function getByIdsRes(array $ids, array $options)
	{
		$result = Order::getList(
			[
				'order' => $this->getByIdsOrder(),
				'select' => $this->getByIdsSelect(),
				'filter' => $this->getByIdsFilter($ids, $options),
			]
		);

		if (!is_object($result))
		{
			return null;
		}

		return $result;
	}
}

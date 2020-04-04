<?
namespace Bitrix\Crm\Integration\Main\UISelector;

use Bitrix\Main\Localization\Loc;

class CrmOrders extends \Bitrix\Main\UI\Selector\EntityBase
{
	const PREFIX_SHORT = 'O_';
	const PREFIX_FULL = 'CRMORDER';

	private static function getPrefix($options = [])
	{
		return (
			is_array($options)
			&& isset($options['prefixType'])
			&& strtolower($options['prefixType']) == 'short'
				? self::PREFIX_SHORT
				: self::PREFIX_FULL
		);
	}

	private static function prepareEntity($data, $options = [])
	{
		$prefix = self::getPrefix($options);
		$result = [
			'id' => $prefix.$data['ID'],
			'entityType' => 'orders',
			'entityId' => $data['ID'],
			'name' => htmlspecialcharsbx($data['ACCOUNT_NUMBER'].(strlen($data['ORDER_TOPIC']) > 0 ? ' "'.$data['ORDER_TOPIC'].'"' : '')),
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
			$result['url'] = \CCrmOwnerType::getEntityShowPath(\CCrmOwnerType::Order, $data['ID']);
			$result['urlUseSlider'] = (\CCrmOwnerType::isSliderEnabled(\CCrmOwnerType::Order) ? 'Y' : 'N');
		}

		return $result;
	}

	public function getData($params = array())
	{
		global $USER;

		$entityType = Handler::ENTITY_TYPE_CRMORDERS;

		$result = array(
			'ITEMS' => array(),
			'ITEMS_LAST' => array(),
			'ITEMS_HIDDEN' => array(),
			'ADDITIONAL_INFO' => array(
				'GROUPS_LIST' => array(
					'crmorders' => array(
						'TITLE' => Loc::getMessage('MAIN_UI_SELECTOR_TITLE_CRMORDERS'),
						'TYPE_LIST' => [ $entityType ],
						'DESC_LESS_MODE' => 'N',
						'SORT' => 60
					)
				),
				'SORT_SELECTED' => 400
			)
		);

		$userPermissions = \CCrmPerms::getCurrentUserPermissions();

		if (!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $userPermissions))
		{
			return $result;
		}

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$prefix = self::getPrefix($entityOptions);

		$lastItems = (!empty($params['lastItems']) ? $params['lastItems'] : array());
		$selectedItems = (!empty($params['selectedItems']) ? $params['selectedItems'] : array());

		$lastOrdersIdList = [];
		if(!empty($lastItems[$entityType]))
		{
			$result["ITEMS_LAST"] = array_map(function($code) use ($prefix) { return preg_replace('/^'.self::PREFIX_FULL.'(\d+)$/', $prefix.'$1', $code); }, array_values($lastItems[$entityType]));
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
		$ordersIdList = array_slice($ordersIdList, 0, count($selectedOrdersIdList) > 20 ? count($selectedOrdersIdList) : 20);
		$ordersIdList = array_unique($ordersIdList);

		$ordersList = [];

		$filter = [];
		$permissionSql = '';

		if (!(is_object($USER) && $USER->isAdmin()))
		{
			$options = [
				'RAW_QUERY' => true,
				'PERMS'=> \CCrmPerms::getCurrentUserPermissions()
			];

			if (!empty($ordersIdList))
			{
				$options['RESTRICT_BY_IDS'] = $ordersIdList;
			}

			$permissionSql = \CCrmPerms::buildSql(
				\CCrmOwnerType::OrderName,
				'',
				'READ',
				$options
			);
		}

		if(strlen($permissionSql) > 0)
		{
			$filter['@ID'] = new \Bitrix\Main\DB\SqlExpression($permissionSql);
		}
		elseif (!empty($ordersIdList))
		{
			$filter['@ID'] = $ordersIdList;
		}

		if (empty($ordersIdList))
		{
			$order = [
				'ID' => 'DESC'
			];
			$limit = 10;
		}
		else
		{
			$order = [];
			$limit = 0;
		}

		$res = \Bitrix\Crm\Order\Order::getList([
			'order' =>  $order,
			'select' =>  [ 'ID', 'ACCOUNT_NUMBER', 'DATE_INSERT', 'ORDER_TOPIC' ],
			'filter' =>  $filter,
			'limit' => $limit
		]);

		while ($orderFields = $res->fetch())
		{
			$ordersList[$prefix.$orderFields['ID']] = self::prepareEntity($orderFields, $entityOptions);
		}

		if (empty($lastOrdersIdList))
		{
			$result["ITEMS_LAST"] = array_keys($ordersList);
		}

		$result['ITEMS'] = $ordersList;

		return $result;
	}

	public function getTabList($params = [])
	{
		$result = [];

		$options = (!empty($params['options']) ? $params['options'] : []);

		if (
			isset($options['addTab'])
			&& $options['addTab'] == 'Y'
		)
		{
			$result = array(
				array(
					'id' => 'orders',
					'name' => Loc::getMessage('MAIN_UI_SELECTOR_TAB_CRMORDERS'),
					'sort' => 60
				)
			);
		}

		return $result;
	}

	public function search($params = array())
	{
		global $USER;

		$result = array(
			'ITEMS' => array(),
			'ADDITIONAL_INFO' => array()
		);

		$entityOptions = (!empty($params['options']) ? $params['options'] : array());
		$requestFields = (!empty($params['requestFields']) ? $params['requestFields'] : array());
		$search = $requestFields['searchString'];
		$prefix = self::getPrefix($entityOptions);

		if (
			strlen($search) > 0
			&& (
				empty($entityOptions['enableSearch'])
				|| $entityOptions['enableSearch'] != 'N'
			)
		)
		{
			$filter = [
				'SEARCH_CONTENT' => $search
			];

			if (!(is_object($USER) && $USER->isAdmin()))
			{
				$options = [
					'RAW_QUERY' => true,
					'PERMS'=> \CCrmPerms::getCurrentUserPermissions()
				];

				$permissionSql = \CCrmPerms::buildSql(
					\CCrmOwnerType::OrderName,
					'',
					'READ',
					$options
				);

				if(strlen($permissionSql) > 0)
				{
					$filter['@ID'] = new \Bitrix\Main\DB\SqlExpression($permissionSql);
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
			else if (preg_match('/(.*)\[(\d+?)\]/i' . BX_UTF_PCRE_MODIFIER, $search, $matches))
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

			$res = \Bitrix\Crm\Order\Order::getList([
				'select' => [ 'ID', 'ACCOUNT_NUMBER', 'DATE_INSERT', 'ORDER_TOPIC' ],
				'filter' => $filter,
				'limit' => 20
			]);

			while ($orderFields = $res->fetch())
			{
				$result["ITEMS"][$prefix.$orderFields['ID']] = self::prepareEntity($orderFields, $entityOptions);
			}
		}

		return $result;
	}
}
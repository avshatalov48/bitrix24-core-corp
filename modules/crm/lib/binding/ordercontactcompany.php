<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Crm\Order;

/**
 * Class OrderContactCompanyTable
 * @package Bitrix\Crm\Binding
 */
class OrderContactCompanyTable extends Entity\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_order_contact_company';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'primary' => true,
				'data_type' => 'integer'
			),
			'ORDER_ID' => array(
				'data_type' => 'integer'
			),
			'ORDER' => array(
				'data_type' => '\Bitrix\Sale\Order',
				'reference' => array(
					'=this.ORDER_ID' => 'ref.ID'
				)
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer'
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer'
			),
			'SORT' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'ROLE_ID' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'IS_PRIMARY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default_value' => 'N'
			)
		);
	}
	/**
	 * Execute UPSERT operation.
	 * @param array $data Field data.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	public static function upsert(array $data)
	{
		$orderId = isset($data['ORDER_ID']) ? (int)$data['ORDER_ID'] : 0;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must contains ORDER_ID field.', 'data');
		}

		$contactID = isset($data['ENTITY_ID']) ? (int)$data['ENTITY_ID'] : 0;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must contains ENTITY_ID field.', 'data');
		}

		$sort = isset($data['SORT']) ? (int)$data['SORT'] : 0;
		$roleID = isset($data['ROLE_ID']) ? (int)$data['ROLE_ID'] : 0;
		$primary = isset($data['IS_PRIMARY']) && mb_strtoupper($data['IS_PRIMARY']) === 'Y' ? 'Y' : 'N';

		$connection = Main\Application::getConnection();
		$queries = $connection->getSqlHelper()->prepareMerge(
			'b_crm_order_contact',
			array('ORDER_ID', 'ENTITY_ID'),
			array('ORDER_ID' => $orderId, 'ENTITY_ID' => $contactID, 'SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary),
			array('SORT' => $sort, 'ROLE_ID' => $roleID, 'IS_PRIMARY' => $primary)
		);

		foreach($queries as $query)
		{
			$connection->queryExecute($query);
		}
	}
	/**
	 * Get order IDs are bound to specified contact.
	 * @param int $contactID Contact ID.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	public static function getContactOrderIDs($contactID)
	{
		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$dbResult =  Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT ORDER_ID FROM b_crm_order_contact_company WHERE ENTITY_ID = {$contactID}"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['ORDER_ID'];
		}
		return $results;
	}

	/**
	 * Get contact IDs are bound to specified order.
	 * @param int $orderId order ID.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	public static function getOrderContactIDs($orderId)
	{
		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'orderId');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT ENTITY_ID FROM b_crm_order_contact_company WHERE ORDER_ID = {$orderId} ORDER BY SORT ASC"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = (int)$ary['ENTITY_ID'];
		}
		return $results;
	}

	/**
	 * Get order's bindings.
	 * @param int $orderId Order ID.
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	public static function getOrderBindings($orderId)
	{
		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'order');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT ENTITY_ID, SORT, ROLE_ID, IS_PRIMARY FROM b_crm_order_contact_company WHERE ORDER_ID = ".$orderId." AND ENTITY_TYPE_ID = ".\CCrmOwnerType::Contact." ORDER BY SORT"
		);

		$results = array();
		while($ary = $dbResult->fetch())
		{
			$results[] = array(
				'CONTACT_ID' => (int)$ary['ENTITY_ID'],
				'SORT' => (int)$ary['SORT'],
				'ROLE_ID' => (int)$ary['ROLE_ID'],
				'IS_PRIMARY' => $ary['IS_PRIMARY']
			);
		}
		return $results;
	}

	/**
	 * Get order's binding count.
	 * @param int $orderId order ID.
	 * @return int
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 */
	public static function getOrderBindingCount($orderId)
	{
		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'orderId');
		}

		$dbResult = Main\Application::getConnection()->query(
			/** @lang text*/
			"SELECT COUNT(*) CNT FROM b_crm_order_contact_company WHERE ORDER_ID = {$orderId}"
		);

		$ary = $dbResult->fetch();
		return is_array($ary) ? (int)$ary['CNT'] : 0;
	}

	/**
	 * Check if order has contacts.
	 * @param int $orderId Order ID.
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function hasContacts($orderId)
	{
		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'orderId');
		}

		$result = self::getList(
			array(
				'select' => array('ORDER_ID'),
				'filter' => array('=ORDER_ID' => $orderId),
				'limit' => 1
			)
		);

		return is_array($result->fetch());
	}

	/**
	 * Bind order to contacts are specified by ID.
	 * @param int $orderId Order ID.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\NotSupportedException
	 * @throws Main\SystemException
	 */
	public static function bindContactIDs($orderId, array $contactIDs)
	{
		$bindings = EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, $contactIDs);
		$qty = count($bindings);
		if($qty > 0)
		{
			for($i = 0; $i < $qty; $i++)
			{
				if($i === 0)
				{
					$bindings[$i]['IS_PRIMARY'] = 'Y';
				}
				$bindings[$i]['SORT'] = 10 * ($i + 1);
			}
			self::bindContacts($orderId, $bindings);
		}
	}

	/**
	 * Bind order to contacts.
	 * @param int $orderId order id.
	 * @param array $bindings Array of contact bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\SystemException
	 * @throws Main\LoaderException
	 */
	public static function bindContacts($orderId, array $bindings)
	{
		if(!Main\Loader::includeModule('sale'))
			throw new Main\SystemException('Can\'t include module "sale"');

		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'orderId');
		}

		$qty = count($bindings);
		if($qty === 0)
		{
			return;
		}

		$processed = 0;
		for($i = 0; $i < $qty; $i++)
		{
			$binding = $bindings[$i];

			$contactID = isset($binding['ENTITY_ID']) ? (int)$binding['ENTITY_ID'] : 0;
			if($contactID <= 0)
			{
				continue;
			}

			self::upsert(
				array(
					'ORDER_ID' => $orderId,
					'ENTITY_ID' => $contactID,
					'SORT' => isset($binding['SORT']) ? (int)$binding['SORT'] : (10 * ($i + 1)),
					'ROLE_ID' => isset($binding['ROLE_ID']) ? (int)$binding['ROLE_ID'] : EntityBinding::ROLE_UNDEFINED,
					'IS_PRIMARY' => isset($binding['IS_PRIMARY']) ? $binding['IS_PRIMARY'] : ''
				)
			);
			$processed++;
		}

		if($processed > 0)
		{
			Main\Application::getConnection()->queryExecute(
				/** @lang text*/
				"UPDATE b_sale_order SET ENTITY_ID =
				(SELECT MIN(ENTITY_ID) FROM b_crm_order_contact_company WHERE IS_PRIMARY = 'Y' AND ORDER_ID = {$orderId})
				WHERE ID = {$orderId}"
			);
		}
	}

	/**
	 * Unbind specified order from specified contacts.
	 * @param int $orderId order id.
	 * @param array $contactIDs Array of contact IDs.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public static function unbindContactIDs($orderId, array $contactIDs)
	{
		if(!Main\Loader::includeModule('sale'))
			throw new Main\SystemException('Can\'t include module "sale"');

		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'orderId');
		}

		$contactIDs = array_filter($contactIDs);
		if(empty($contactIDs))
		{
			return;
		}

		$connection = Main\Application::getConnection();

		$values = implode(',', $contactIDs);
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_order_contact_company WHERE ORDER_ID = {$orderId} AND ENTITY_ID IN({$values})"
		);

		$connection->queryExecute(
			/** @lang text*/
			"UPDATE b_sale_order SET ENTITY_ID =
			(SELECT MIN(ENTITY_ID) FROM b_crm_order_contact_company WHERE IS_PRIMARY = 'Y' AND ORDER_ID = {$orderId})
			WHERE ID = {$orderId}"
		);
	}

	/**
	 * Unbind specified order from specified contacts.
	 * @param int $orderId Order ID.
	 * @param array $bindings Array of bindings.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\NotSupportedException
	 * @throws Main\SystemException
	 */
	public static function unbindContacts($orderId, array $bindings)
	{
		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'orderId');
		}

		self::unbindContactIDs($orderId, EntityBinding::prepareEntityIDs(\CCrmOwnerType::Contact, $bindings));
	}

	/**
	 * Unbind specified order from all contacts.
	 * @param int $orderId Order ID.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public static function unbindAllContacts($orderId)
	{
		if(!Main\Loader::includeModule('sale'))
			throw new Main\SystemException('Can\'t include module "sale"');

		$orderId = (int)$orderId;
		if($orderId <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'orderId');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
			/** @lang text */
			"DELETE FROM b_crm_order_contact_company WHERE ORDER_ID = {$orderId}"
		);
		$connection->queryExecute(
			/** @lang text */
			"UPDATE b_sale_order SET ENTITY_ID = NULL WHERE ID = {$orderId}"
		);
	}

	/**
	 * Unbind specified contact from all orders.
	 * @param int $contactID Contact ID.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public static function unbindAllOrders($contactID)
	{
		if(!Main\Loader::includeModule('sale'))
			throw new Main\SystemException('Can\'t include module "sale"');

		$contactID = (int)$contactID;
		if($contactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'contactID');
		}

		$connection = Main\Application::getConnection();
		$connection->queryExecute(
		/** @lang text */
			"DELETE FROM b_crm_order_contact_company WHERE ENTITY_ID = {$contactID}"
		);
		$connection->queryExecute(
		/** @lang text */
			"UPDATE b_sale_order SET ENTITY_ID =
			(SELECT MIN(ENTITY_ID) FROM b_crm_order_contact_company t WHERE t.ORDER_ID = b_sale_order.ID)
			WHERE ENTITY_ID = {$contactID}"
		);
	}

	/**
	 * Prepage SQL join filter condition for specified entity.
	 * @param int $entityTypeID Entity type ID for filter.
	 * @param int $entityID Entity ID for filter.
	 * @param string $tableAlias Alias of primary table.
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 */
	public static function prepareFilterJoinSql($entityTypeID, $entityID, $tableAlias)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentOutOfRangeException('entityTypeID',
				\CCrmOwnerType::FirstOwnerType,
				\CCrmOwnerType::LastOwnerType
			);
		}

		if($entityTypeID !== \CCrmOwnerType::Contact && $entityTypeID !== \CCrmOwnerType::Order)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
			throw new Main\NotSupportedException("Entity type: '{$entityTypeName}' is not supported in current context");
		}

		$entityIDs = is_array($entityID) ? $entityID : array($entityID);
		$effectiveIDs = array();
		foreach($entityIDs as $ID)
		{
			$ID = (int)$ID;
			if($ID > 0)
			{
				$effectiveIDs[] = $ID;
			}
		}

		$qty = count($effectiveIDs);
		if($qty > 1)
		{
			$slug = implode(',', $effectiveIDs);
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				return "INNER JOIN b_crm_order_contact_company QC ON QC.ENTITY_ID IN({$slug}) AND QC.ORDER_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Order)
			{
				return "INNER JOIN b_crm_order_contact_company QC ON QC.ORDER_ID IN({$slug}) AND QC.ENTITY_ID = {$tableAlias}.ID";
			}
		}
		elseif($qty === 1)
		{
			if($entityTypeID === \CCrmOwnerType::Contact)
			{
				return "INNER JOIN b_crm_order_contact_company QC ON QC.ENTITY_ID = {$effectiveIDs[0]} AND QC.ORDER_ID = {$tableAlias}.ID";
			}
			else//if($entityTypeID === \CCrmOwnerType::Order)
			{
				return "INNER JOIN b_crm_order_contact_company QC ON QC.ORDER_ID = {$effectiveIDs[0]} AND QC.ENTITY_ID = {$tableAlias}.ID";
			}
		}
		return "";
	}

	/**
	 * Unbind all orders from seed contact and bind to target contact
	 * @param int $seedContactID Seed contact ID.
	 * @param int $targContactID Target contact ID.
	 * @throws Main\ArgumentException
	 * @throws Main\Db\SqlQueryException
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */

	public static function rebindAllOrders($seedContactID, $targContactID)
	{
		$seedContactID = (int)$seedContactID;
		if($seedContactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'seedContactID');
		}

		$targContactID = (int)$targContactID;
		if($targContactID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'targContactID');
		}

		if($seedContactID === $targContactID)
		{
			return;
		}

		$connection = Main\Application::getConnection();
		$dbResult = $connection->query(
		/** @lang text */
			"SELECT ORDER_ID FROM b_crm_order_contact_company WHERE ENTITY_ID = {$seedContactID}"
		);

		while($fields = $dbResult->fetch())
		{
			$orderId = (int)$fields['ORDER_ID'];
			$bindings = self::getOrderBindings($orderId);
			$seedIndex = $targIndex = -1;
			for($i = 0, $l = count($bindings); $i < $l; $i++)
			{
				$binding = $bindings[$i];
				$contactID = (int)$binding['ENTITY_ID'];
				if($contactID === $seedContactID)
				{
					$seedIndex = $i;
				}
				elseif($contactID === $targContactID)
				{
					$targIndex = $i;
				}

				if($seedIndex >= 0 && $targIndex >= 0)
				{
					break;
				}
			}

			$seedBinding = $seedIndex >= 0 ? $bindings[$seedIndex] : null;
			$targBinding = $targIndex >= 0 ? $bindings[$targIndex] : null;

			if(!is_array($seedBinding))
			{
				continue;
			}

			self::unbindContactIDs($orderId, array($seedContactID));

			$isPrimary = isset($seedBinding['IS_PRIMARY']) && $seedBinding['IS_PRIMARY'] === 'Y';
			if(!is_array($targBinding))
			{
				$seedBinding['ENTITY_ID'] = $targContactID;
				self::bindContacts($orderId, array($seedBinding));
			}
			elseif($isPrimary)
			{
				$targBinding['IS_PRIMARY'] = 'Y';
				self::bindContacts($orderId, array($targBinding));
			}
		}
	}

	/**
	 * Get orders by entities.
	 * <p>$entities:
	 * [
	 *    ['TYPE_ID' => \CCrmOwnerType::Contact, 'ID' => 123],
	 *    ['TYPE_ID' => \CCrmOwnerType::Company, 'ID' => 321],
	 * ]
	 * </p>
	 *
	 * @param array $entities Entity list with type ID and entity ID.
	 * @param bool $isPrimary Return only primary results.
	 * @return int[]
	 */
	public static function getOrdersByEntities(array $entities, $isPrimary = true)
	{
		$list = [];
		foreach ($entities as $entity)
		{
			$typeId = isset($entity['TYPE_ID']) ? (int) $entity['TYPE_ID'] : null;
			$id = isset($entity['ID']) ? (int) $entity['ID'] : null;

			if (!$typeId || !$id)
			{
				continue;
			}

			if (!isset($list[$typeId]))
			{
				$list[$typeId] = [];
			}
			$list[$typeId][] = $id;
		}

		if (empty($list))
		{
			return [];
		}

		$result = [];

		$filter = [
			'=ENTITY_TYPE_ID' => array_keys($list),
			'=ENTITY_ID' => array_unique(array_reduce(array_values($list), 'array_merge', [])),
			'=ORDER.STATUS_ID' => Order\OrderStatus::getSemanticProcessStatuses(),
		];
		if ($isPrimary)
		{
			$filter['=IS_PRIMARY'] = 'Y';
		}
		$orders = static::getList([
			'select' => ['ORDER_ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'],
			'filter' => $filter
		])->fetchAll();
		foreach ($orders as $item)
		{
			$typeId = (int) $item['ENTITY_TYPE_ID'];
			$id = (int) $item['ENTITY_ID'];

			if (!in_array($id, $list[$typeId]))
			{
				continue;
			}

			$result[] = (int) $item['ORDER_ID'];
		}

		return $result;
	}
}
<?php


namespace Bitrix\Sale;


use Bitrix\Main;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\Internals\OrderChangeTable;

class OrderHistory
{
	protected static $pool = array();
	protected static $poolFields = array();

	const SALE_ORDER_HISTORY_UPDATE = 'UPDATE';

	const SALE_ORDER_HISTORY_RECORD_TYPE_ACTION = 'ACTION';
	const SALE_ORDER_HISTORY_RECORD_TYPE_FIELD = 'FIELD';
	const SALE_ORDER_HISTORY_RECORD_TYPE_DEBUG = 'DEBUG';

	const FIELD_TYPE_NAME = 'NAME';
	const FIELD_TYPE_TYPE = 'TYPE';

	const SALE_ORDER_HISTORY_LOG_LEVEL_0 = 0;
	const SALE_ORDER_HISTORY_LOG_LEVEL_1 = 1;

	const SALE_ORDER_HISTORY_ACTION_LOG_LEVEL_0 = 0;
	const SALE_ORDER_HISTORY_ACTION_LOG_LEVEL_1 = 1;

	protected function __construct()
	{

	}

	/**
	 * @param string $entityName
	 * @param int $orderId
	 * @param string $field
	 * @param null|string $oldValue
	 * @param null|string $value
	 * @param int $id
	 * @param $entity
	 * @param array $fields
	 */
	public static function addField($entityName, $orderId, $field, $oldValue = null, $value = null, $id = null, $entity = null, array $fields = array())
	{
		if ($field == "ID")
			return;

		if ($value !== null && static::isDate($value))
		{
			$value = $value->toString();
		}

		if ($oldValue !== null && static::isDate($oldValue))
		{
			$oldValue = $oldValue->toString();
		}

		if (!empty($fields))
		{
			foreach($fields as $fieldName => $fieldValue)
			{
				if (static::isDate($fieldValue))
				{
					$fields[$fieldName] = $fieldValue->toString();
				}
			}
		}

		static::$pool[$entityName][$orderId][$id][$field][] = array(
			'RECORD_TYPE' => static::SALE_ORDER_HISTORY_RECORD_TYPE_FIELD,
			'ENTITY_NAME' => $entityName,
			'ENTITY' => $entity,
			'ORDER_ID' => $orderId,
			'ID' => $id,
			'NAME' => $field,
			'OLD_VALUE' => $oldValue,
			'VALUE' => $value,
			'DATA' => $fields
		);
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param $type
	 * @param null $id
	 * @param null $entity
	 * @param array $fields
	 * @param null $level
	 *
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function addAction($entityName, $orderId, $type, $id = null, $entity = null, array $fields = array(), $level = null)
	{
		if ($level === null)
		{
			$level = static::SALE_ORDER_HISTORY_ACTION_LOG_LEVEL_0;
		}

		if (!static::checkActionLogLevel($level))
			return;

		static::$pool[$entityName][$orderId][$id][$type][] = array(
			'RECORD_TYPE' => static::SALE_ORDER_HISTORY_RECORD_TYPE_ACTION,
			'ENTITY_NAME' => $entityName,
			'ENTITY' => $entity,
			'ID' => $id,
			'TYPE' => $type,
			'DATA' => $fields
		);
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param null|int $id
	 * @return bool
	 */
	public static function collectEntityFields($entityName, $orderId, $id = null)
	{
		if (!$poolEntity = static::getPoolByEntity($entityName, $orderId))
		{
			return false;
		}

		if ($id !== null)
		{
			$found = false;
			foreach ($poolEntity as $entityId => $fieldValue)
			{
				if ($entityId == $id)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
				return false;
		}

		foreach ($poolEntity as $entityId => $fieldValue)
		{
			if ($id !== null && $entityId != $id)
				continue;

			$entity = null;

			$dataFields = array();
			$oldFields = array();
			$fields = array();

			foreach ($fieldValue as $dataList)
			{
				foreach ($dataList as $key => $data)
				{
					if ($data['RECORD_TYPE'] == static::SALE_ORDER_HISTORY_RECORD_TYPE_ACTION
						|| $data['RECORD_TYPE'] == static::SALE_ORDER_HISTORY_RECORD_TYPE_DEBUG)
					{
						static::addRecord(
							$entityName,
							$orderId,
							$data['TYPE'],
							$data['ID'],
							$data['ENTITY'],
							static::prepareDataForAdd($entityName, $data['TYPE'], $data['ENTITY'], $data['DATA'])
						);
						unset(static::$pool[$entityName][$orderId][$data['ID']][$data['TYPE']][$key]);

						if (empty(static::$pool[$entityName][$orderId][$data['ID']][$data['TYPE']]))
							unset(static::$pool[$entityName][$orderId][$data['ID']][$data['TYPE']]);

						continue;
					}

					$value = $data['VALUE'];
					$oldValue = $data['OLD_VALUE'];

					if (static::isDate($value))
						$value = static::convertDateField($value);

					if (static::isDate($oldValue))
						$oldValue = static::convertDateField($oldValue);

					$oldFields[$data['NAME']] = $oldValue;
					$fields[$data['NAME']] = $value;

					if (!empty($data['DATA']) && is_array($data['DATA']))
					{
						$dataFields = array_merge($dataFields, $data['DATA']);
					}

					$dataType = static::FIELD_TYPE_TYPE;
					if (isset($data['RECORD_TYPE']) && $data['RECORD_TYPE'] == static::SALE_ORDER_HISTORY_RECORD_TYPE_FIELD)
					{
						$dataType = static::FIELD_TYPE_NAME;
					}

					if (isset($data[$dataType]))
					{
						unset(static::$pool[$entityName][$orderId][$data['ID']][$data[$dataType]][$key]);

						if (empty(static::$pool[$entityName][$orderId][$data['ID']][$data[$dataType]]))
							unset(static::$pool[$entityName][$orderId][$data['ID']][$data[$dataType]]);
					}

					if ($entity === null && array_key_exists('ENTITY', $data))
					{
						$entity = $data['ENTITY'];
					}

				}

			}

			if ($entityName === "") // for order
			{
				if (isset($fields["ID"]))
					unset($fields["ID"]);
			}

			foreach ($fields as $key => $val)
			{
				if (is_array($val))
				{
					continue;
				}

				if (!array_key_exists($key, $oldFields)
					|| (
						array_key_exists($key, $oldFields)
						&& $val <> '' && $val != $oldFields[$key]
					)
				)
				{
					$arRecord = \CSaleOrderChange::MakeRecordFromField($key, $dataFields, $entityName, $entity);
					if ($arRecord)
					{
						$result = $arRecord["DATA"];
						foreach ($arRecord["DATA"] as $fieldKey => $fieldValue)
						{
							if (!isset($result['OLD_'.$fieldKey]) && isset($dataFields['OLD_'.$fieldKey]))
							{
								$result['OLD_'.$fieldKey] = TruncateText($dataFields['OLD_'.$key], 128);
							}
						}

						static::addRecord(
							$entityName,
							$orderId,
							$arRecord["TYPE"],
							$entityId,
							$entity,
							static::prepareDataForAdd($entityName, $arRecord["TYPE"], $entity, $result)
						);
					}
				}
			}

			if (empty(static::$pool[$entityName][$orderId][$entityId]))
				unset(static::$pool[$entityName][$orderId][$entityId]);
		}

		if (empty(static::$pool[$entityName][$orderId]))
			unset(static::$pool[$entityName][$orderId]);

		if (empty(static::$pool[$entityName]))
			unset(static::$pool[$entityName]);

		return true;
	}

	/**
	 * @param $entity
	 * @param $orderId
	 * @return bool|array
	 */
	protected static function getPoolByEntity($entity, $orderId)
	{
		if (empty(static::$pool[$entity])
			|| empty(static::$pool[$entity][$orderId])
			|| !is_array(static::$pool[$entity][$orderId]))
		{
			return false;
		}

		return static::$pool[$entity][$orderId];
	}

	/**
	 * @param $entityName
	 * @param $type
	 * @param Entity $entity
	 * @param array $data
	 * @return array
	 */
	protected static function prepareDataForAdd($entityName, $type, $entity = null, array $data = array())
	{
		if ($entity !== null
			&& ($operationType = static::getOperationType($entityName, $type))
			&& (!empty($operationType["DATA_FIELDS"]) && is_array($operationType["DATA_FIELDS"]))
		)
		{
			foreach ($operationType["DATA_FIELDS"] as $fieldName)
			{
				if (!array_key_exists($fieldName, $data) && ($value = $entity->getField($fieldName)))
				{
					$data[$fieldName] = TruncateText($value, 128);
				}
			}
		}

		return $data;
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param $type
	 * @param null $id
	 * @param null|Entity $entity
	 * @param array $data
	 */
	protected static function addRecord($entityName, $orderId, $type, $id = null, $entity = null, array $data = array())
	{
		global $USER;
		$userId = (is_object($USER)) ? intval($USER->GetID()) : 0;

		$fields = array(
			"ORDER_ID" => intval($orderId),
			"TYPE" => $type,
			"DATA" => (is_array($data) ? serialize($data) : $data),
			"USER_ID" => $userId,
			"ENTITY" => $entityName,
			"ENTITY_ID" => $id,
		);

		static::addInternal($fields);
	}

	/**
	 * @param $fields
	 * @return Main\Entity\AddResult
	 * @throws \Exception
	 */
	protected static function addInternal($fields)
	{
		return OrderChangeTable::add($fields);
	}

	/**
	 * @param $entityName
	 * @param $type
	 *
	 * @return bool
	 */
	protected static function getOperationType($entityName, $type)
	{
		if (!empty(\CSaleOrderChangeFormat::$operationTypes)
			&& !empty(\CSaleOrderChangeFormat::$operationTypes[$type])
		)
		{
			if (!empty(\CSaleOrderChangeFormat::$operationTypes[$type]['ENTITY'])
				&& $entityName == \CSaleOrderChangeFormat::$operationTypes[$type]['ENTITY'])
			{
				return \CSaleOrderChangeFormat::$operationTypes[$type];
			}
		}

		return false;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	private static function isDate($value)
	{
		return ($value instanceof Main\Type\DateTime) || ($value instanceof Main\Type\Date);
	}

	/**
	 * @param $value
	 * @return string
	 */
	private static function convertDateField($value)
	{
		if (($value instanceof Main\Type\DateTime)
			|| ($value instanceof Main\Type\Date))
		{
			return $value->toString();
		}

		return $value;
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	public static function deleteByOrderId($id)
	{
		if (intval($id) <= 0)
			return false;

		$dbRes = static::getList(array(
			'select' => array('ID'),
			'filter' => array('=ORDER_ID' => $id)
		));

		while ($data = $dbRes->fetch())
		{
			static::deleteInternal($data['ID']);
		}

		return true;
	}

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected static function getList(array $parameters = array())
	{
		return OrderChangeTable::getList($parameters);
	}

	/**
	 * @param $primary
	 * @return Main\Entity\DeleteResult
	 * @throws \Exception
	 */
	protected static function deleteInternal($primary)
	{
		return OrderChangeTable::delete($primary);
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param $type
	 * @param null $id
	 * @param null $entity
	 * @param array $fields
	 * @param null $level
	 *
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function addLog($entityName, $orderId, $type, $id = null, $entity = null, array $fields = array(), $level = null)
	{
		if ($level === null)
		{
			$level = static::SALE_ORDER_HISTORY_LOG_LEVEL_0;
		}

		if (!static::checkLogLevel($level))
			return;

		if (!empty($fields))
		{
			foreach($fields as $fieldName => $fieldValue)
			{
				if (static::isDate($fieldValue))
				{
					$fields[$fieldName] = $fieldValue->toString();
				}
			}
		}

		static::$pool[$entityName][$orderId][$id][$type][] = array(
			'RECORD_TYPE' => static::SALE_ORDER_HISTORY_RECORD_TYPE_DEBUG,
			'ENTITY_NAME' => $entityName,
			'ENTITY' => $entity,
			'ID' => $id,
			'TYPE' => $type,
			'DATA' => $fields,
			'LEVEL' => $level
		);
	}

	/**
	 * @param $level
	 *
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function checkLogLevel($level)
	{
		$orderHistoryLogLevel = Main\Config\Option::get('sale', 'order_history_log_level', static::SALE_ORDER_HISTORY_LOG_LEVEL_0);

		if ($level > $orderHistoryLogLevel)
			return false;

		return true;
	}

	/**
	 * @param $level
	 *
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function checkActionLogLevel($level)
	{
		$orderHistoryActionLogLevel = Main\Config\Option::get(
			'sale',
			'order_history_action_log_level',
			static::SALE_ORDER_HISTORY_ACTION_LOG_LEVEL_0
		);

		if ($level > $orderHistoryActionLogLevel)
			return false;

		return true;
	}

	/**
	 * @param $days
	 * @param null $limit
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	protected static function deleteOldInternal($days, $limit = null)
	{
		$days = (int)($days);

		if ($days <= 0)
			return false;

		$expired = new Main\Type\DateTime();
		$expired->add('-'.$days.' days');

		$parameters = array(
			'filter' => array('<DATE_CREATE' => $expired->toString())
		);

		if ($limit > 0)
		{
			$parameters['limit'] = $limit;
		}

		$dbRes = static::getList($parameters);
		while ($data = $dbRes->fetch())
		{
			static::deleteInternal($data['ID']);
		}

		return true;
	}

	/**
	 * Delete old records on an agent
	 *
	 * @param $days
	 * @param null $hitLimit
	 * @return string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	public static function deleteOldAgent($days, $hitLimit = null)
	{
		$calledClass = '\\'.static::class;

		$days = (int)$days;

		static::deleteOldInternal($days, $hitLimit);

		if ($days)
		{
			$expired = new Main\Type\DateTime();
			$expired->add("-$days days");
			$dbRes = static::getList(array(
				'filter' => array('<DATE_CREATE' => $expired->toString()),
				'limit' => 1
			));

			if ($dbRes->fetch())
			{
				$interval = 60;
			}
			else
			{
				$interval = 24 * 60 * 60;
			}

			$agentsList = \CAgent::GetList(array("ID"=>"DESC"), array(
				"MODULE_ID" => "sale",
				"NAME" => $calledClass."::deleteOldAgent(%"
			));
			if ($agent = $agentsList->Fetch())
			{
				\CAgent::Update($agent['ID'], array("AGENT_INTERVAL" => $interval));
			}
		}

		return $calledClass."::deleteOldAgent(\"$days\", \"$hitLimit\");";
	}

	/**
	 * @return array
	 */
	public static function getManagerLogItems()
	{
		return array(
			"ORDER_SYNCHRONIZATION_IMPORT",
			"ORDER_SYNCHRONIZATION_EXPORT",
			"ORDER_SYNCHRONIZATION_EXPORT_ERROR",
			"ORDER_ADDED",
			"ORDER_DEDUCTED",
			"ORDER_MARKED",
			"ORDER_RESERVED",
			"ORDER_CANCELED",
			"ORDER_COMMENTED",
			"ORDER_STATUS_CHANGED",
			"ORDER_DELIVERY_ALLOWED",
			"ORDER_DELIVERY_DOC_CHANGED",
			"ORDER_PAYMENT_SYSTEM_CHANGED",
			"ORDER_PAYMENT_VOUCHER_CHANGED",
			"ORDER_DELIVERY_SYSTEM_CHANGED",
			"ORDER_PERSON_TYPE_CHANGED",
			"ORDER_PAYED",
			"ORDER_TRACKING_NUMBER_CHANGED",
			"ORDER_USER_DESCRIPTION_CHANGED",
			"ORDER_PRICE_DELIVERY_CHANGED",
			"ORDER_PRICE_CHANGED",
			"ORDER_RESPONSIBLE_CHANGE",

			"BASKET_ADDED",
			"BASKET_REMOVED",
			"BASKET_QUANTITY_CHANGED",
			"BASKET_PRICE_CHANGED",
			"PAYMENT_ADDED",
			"PAYMENT_REMOVED",
			"PAYMENT_PAID",
			"PAYMENT_SYSTEM_CHANGED",
			"PAYMENT_VOUCHER_CHANGED",
			"PAYMENT_PRICE_CHANGED",

			"SHIPMENT_ADDED",
			"SHIPMENT_REMOVED",
			"SHIPMENT_ITEM_BASKET_ADDED",
			"SHIPMENT_ITEM_BASKET_REMOVED",
			"SHIPMENT_DELIVERY_ALLOWED",
			"SHIPMENT_SHIPPED",
			"SHIPMENT_MARKED",
			"SHIPMENT_RESERVED",
			"SHIPMENT_CANCELED",
			"SHIPMENT_STATUS_CHANGED",
			"SHIPMENT_DELIVERY_DOC_CHANGED",
			"SHIPMENT_TRACKING_NUMBER_CHANGED",
			"SHIPMENT_PRICE_DELIVERY_CHANGED",
			"SHIPMENT_AMOUNT_CHANGED",
			"SHIPMENT_QUANTITY_CHANGED",
			"SHIPMENT_RESPONSIBLE_CHANGE",

			"ORDER_UPDATE_ERROR",
			"BASKET_ITEM_ADD_ERROR",
			"BASKET_ITEM_UPDATE_ERROR",
			"SHIPMENT_ADD_ERROR",
			"SHIPMENT_UPDATE_ERROR",
			"SHIPMENT_ITEM_ADD_ERROR",
			"SHIPMENT_ITEM_UPDATE_ERROR",
			"SHIPMENT_ITEM_STORE_ADD_ERROR",
			"SHIPMENT_ITEM_STORE_UPDATE_ERROR",
			"SHIPMENT_ITEM_BASKET_ITEM_EMPTY_ERROR",

		);
	}

}
<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Binding;
use Bitrix\Main;
use Bitrix\Sale;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class EntityBinding
 * @package Bitrix\Crm\Order
 */
class EntityBinding extends Sale\Internals\Entity
{
	/** @var Order null */
	private $order = null;

	private $isNewEntity =	false;

	private $isDeleted = false;

	/**
	 * @param Order $order
	 * @return static
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function create(Order $order)
	{
		$binding = new static();
		$binding->setOrder($order);

		if ($order->getId() > 0)
		{
			$binding->setFieldNoDemand('ORDER_ID', $order->getId());
		}

		return $binding;
	}

	/**
	 * @return int
	 */
	public function getOwnerTypeId() : int
	{
		return (int)$this->getField('OWNER_TYPE_ID');
	}

	/**
	 * @return int
	 */
	public function getOwnerId() : int
	{
		return (int)$this->getField('OWNER_ID');
	}

	/**
	 * @return void
	 */
	public function markEntityAsNew() : void
	{
		$this->isNewEntity = true;
	}

	/**
	 * @return void
	 */
	public function unmarkEntityAsNew() : void
	{
		$this->isNewEntity = false;
	}

	/**
	 * @return bool
	 */
	public function isNewEntity(): bool
	{
		return $this->isNewEntity;
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields() : array
	{
		return ['ORDER_ID', 'OWNER_ID', 'OWNER_TYPE_ID'];
	}

	/**
	 * @return array
	 */
	protected static function getMeaningfulFields()
	{
		return [];
	}

	/**
	 * @return string
	 */
	public static function getRegistryType() : string
	{
		return Sale\Registry::REGISTRY_TYPE_ORDER;
	}

	/**
	 * @return string
	 */
	public static function getRegistryEntity() : string
	{
		return ENTITY_CRM_ORDER_ENTITY_BINDING;
	}

	/**
	 * @param bool $isMeaningfulField
	 * @return bool
	 */
	public function isStartField($isMeaningfulField = false) : bool
	{
		if ($this->order)
		{
			$this->order->isStartField($isMeaningfulField);
		}

		return false;
	}

	/**
	 * @return void
	 */
	public function clearStartField() : void
	{
		if ($this->order)
		{
			$this->order->clearStartField();
		}
	}

	/**
	 * @return bool
	 */
	public function hasMeaningfulField() : bool
	{
		if ($this->order)
		{
			return $this->order->hasMeaningfulField();
		}

		return false;
	}

	/**
	 * @param bool $hasMeaningfulField
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	public function doFinalAction($hasMeaningfulField = false) : Sale\Result
	{
		if ($this->order)
		{
			return $this->order->doFinalAction($hasMeaningfulField);
		}

		return new Sale\Result();
	}

	/**
	 * @param bool $value
	 */
	public function setMathActionOnly($value = false) : void
	{
		if ($this->order)
		{
			$this->order->setMathActionOnly($value);
		}
	}

	/**
	 * @return bool
	 */
	public function isMathActionOnly() : bool
	{
		if ($this->order)
		{
			$this->order->isMathActionOnly();
		}

		return false;
	}

	/**
	 * @internal
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function save() : Sale\Result
	{
		$this->checkCallingContext();

		$result = new Sale\Result();

		if (!$this->isChanged() && !$this->isDeleted())
		{
			return $result;
		}

		$r = Binding\OrderEntityTable::deleteByOrderId($this->getField('ORDER_ID'));

		if ($r->isSuccess())
		{
			if ($this->isDeleted())
			{
				return $result;
			}

			if (!$this->getField('ORDER_ID'))
			{
				$this->setFieldNoDemand('ORDER_ID', $this->order->getId());
			}

			$r = Binding\OrderEntityTable::add($this->getFieldValues());
		}

		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @return void
	 */
	private function checkCallingContext() : void
	{
		if (!$this->order->isSaveRunning())
		{
			trigger_error("Incorrect call to the save process. Use method save() on \Bitrix\Sale\Order entity", E_USER_WARNING);
		}
	}

	/**
	 * @param Order $order
	 * @return static|null
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function load(Order $order)
	{
		if ($order->getId() <= 0)
		{
			return null;
		}

		$dbRes = static::getList([
			'filter' => [
				'=ORDER_ID' => $order->getId()
			]
		]);

		$data = $dbRes->fetch();
		if (!$data)
		{
			return null;
		}

		$binding = new static($data);
		$binding->setOrder($order);

		return $binding;
	}

	/**
	 * @param Order $order
	 */
	public function setOrder(Order $order) : void
	{
		$this->order = $order;
	}

	/**
	 * @param array $parameters
	 * @return Main\ORM\Query\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getList(array $parameters = [])
	{
		return Binding\OrderEntityTable::getList($parameters);
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap() : array
	{
		return Binding\OrderEntityTable::getMap();
	}

	/**
	 * @internal
	 * @return string
	 */
	public static function getEntityEventName()
	{
		return 'CrmOrderEntityBinding';
	}

	/**
	 * @return void
	 */
	public function delete() : void
	{
		$this->isDeleted = true;
	}

	/**
	 * @return bool
	 */
	public function isDeleted() : bool
	{
		return $this->isDeleted;
	}

	/**
	 * @param $orderId
	 * @return Main\ORM\Data\DeleteResult
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function deleteNoDemand($orderId)
	{
		return Binding\OrderEntityTable::deleteByOrderId($orderId);
	}
}
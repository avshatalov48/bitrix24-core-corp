<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm\Binding\OrderDealTable;
use Bitrix\Main;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale;
use Bitrix\Sale\Result;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class DealBinding
 * @package Bitrix\Crm\Order
 */
class DealBinding extends Entity
{
	/** @var Order null */
	private $order = null;

	private $isNewCrmDeal =	false;

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
	public function getDealId() : int
	{
		return (int)$this->getField('DEAL_ID');
	}

	/**
	 * @return void
	 */
	public function markCrmDealAsNew() : void
	{
		$this->isNewCrmDeal = true;
	}

	/**
	 * @return void
	 */
	public function unmarkCrmDealAsNew() : void
	{
		$this->isNewCrmDeal = false;
	}

	/**
	 * @return bool
	 */
	public function isNewCrmDeal(): bool
	{
		return $this->isNewCrmDeal;
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields() : array
	{
		return ['ORDER_ID', 'DEAL_ID'];
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
		return ENTITY_CRM_ORDER_DEAL_BINDING;
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
	 * @return Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectNotFoundException
	 */
	public function doFinalAction($hasMeaningfulField = false) : Result
	{
		if ($this->order)
		{
			return $this->order->doFinalAction($hasMeaningfulField);
		}

		return new Result();
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
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function save() : Result
	{
		$this->checkCallingContext();

		$result = new Result();

		if (!$this->isChanged() && !$this->isDeleted())
		{
			return $result;
		}

		$r = OrderDealTable::deleteByOrderId($this->getField('ORDER_ID'));

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

			$r = OrderDealTable::add($this->getFieldValues());
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

		$dealBinding = new static($data);
		$dealBinding->setOrder($order);

		return $dealBinding;
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
		return OrderDealTable::getList($parameters);
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap() : array
	{
		return OrderDealTable::getMap();
	}

	/**
	 * @internal
	 * @return string
	 */
	public static function getEntityEventName()
	{
		return 'CrmOrderDealBinding';
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
		return OrderDealTable::deleteByOrderId($orderId);
	}
}
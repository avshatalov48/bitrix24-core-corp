<?php

namespace Bitrix\DocumentGenerator\Entity\Sale;

use Bitrix\DocumentGenerator\Entity;
use Bitrix\Sale\OrderStatus;
use Bitrix\Main\Loader;

class Payment implements Entity
{
	protected $payment;

	public function __construct($payment, array $options = [])
	{
		if(Loader::includeModule('sale'))
		{
			if($payment instanceof \Bitrix\Sale\Payment)
			{
				$this->payment = $payment;
			}
		}
	}

	/**
	 * Returns list of value names for this entity.
	 *
	 * @return array
	 */
	public function getFields()
	{
		$fields = [];

		if($this->isLoaded())
		{
			$fields = array_keys($this->payment->getFieldValues());
		}
		elseif(Loader::includeModule('sale'))
		{
			$fields = \Bitrix\Sale\Payment::getAvailableFields();
		}

		return $fields;
	}

	/**
	 * Returns value by its name.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getValue($name)
	{
		if($this->payment)
		{
			return $this->payment->getField($name);
		}

		return '';
	}

	/**
	 * @return bool
	 */
	public function isLoaded()
	{
		return $this->payment !== null;
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function hasAccess($userId)
	{
		if($this->isLoaded())
		{
			$allowedStatuses = OrderStatus::getStatusesUserCanDoOperations($userId, ['view']);
			$orderId = $this->payment->getOrderId();
			$order = \Bitrix\Sale\Order::load($orderId);
			if(in_array($order->getField('STATUS_ID'), $allowedStatuses))
			{
				return true;
			}
		}

		return false;
	}

	public function getFieldTitle($fieldName)
	{
		return $fieldName;
	}
}
<?php

namespace Bitrix\DocumentGenerator\Entity\Sale;

use Bitrix\DocumentGenerator\Entity\ArrayEntity;
use Bitrix\DocumentGenerator\Entity\OrmEntity;
use Bitrix\DocumentGenerator\Entity;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Sale\OrderBase;
use Bitrix\Sale\OrderStatus;

class Order implements Entity, Nameable
{
	/** @var \Bitrix\Sale\Order */
	protected $order;
	protected $payments;
	protected $id;
	protected $user;

	public function __construct($id, array $options = [])
	{
		if(intval($id) <= 0)
		{
			return;
		}
		if(Loader::includeModule('sale'))
		{
			$this->id = $id;
			$this->order = \Bitrix\Sale\Order::load($this->id);
			$payments = $this->order->getPaymentCollection();
			if(!empty($payments))
			{
				$paymentsData = [];
				foreach($payments as $payment)
				{
					$paymentsData[] = new Payment($payment);
				}
				$this->payments = new ArrayEntity($paymentsData, $this->getFields()['PAYMENTS']['ENTITY_OPTIONS']);
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

		if(Loader::includeModule('sale'))
		{
			$fields = OrderBase::getAvailableFields();
			$fields['PAYMENTS'] = [
				'ENTITY_TYPE' => ArrayEntity::class,
				'ENTITY_OPTIONS' => [
					'CHILD_ENTITY' => Payment::class,
					'ITEM_NAME' => 'PAYMENT',
				],
			];
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
		$values = [];

		if($this->order)
		{
			if($name == 'PAYMENTS')
			{
				return $this->payments;
			}
			elseif($name == 'USER')
			{
				return $this->user;
			}
			elseif($name == 'PRICE_WORDS')
			{
				return Number2Word_Rus($this->order->getPrice());
			}

			$values = $this->order->getFieldValues();
		}

		return $values[$name];
	}

	public static function getLangName()
	{
		return 'Заказ';
	}

	/**
	 * @return bool
	 */
	public function isLoaded()
	{
		return $this->order !== null;
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
			if(in_array($this->order->getField('STATUS_ID'), $allowedStatuses))
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
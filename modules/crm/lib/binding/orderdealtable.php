<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;

/**
 * Class OrderContactCompanyTable
 * @package Bitrix\Crm\Binding
 */
class OrderDealTable extends Main\ORM\Data\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_order_deal';
	}
	/**
	 * Get table fields map.
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField('DEAL_ID', [
				'primary' => true,
				'unique' => true,
			]),
			new IntegerField('ORDER_ID', [
				'primary' => true,
			]),
			new Reference('ORDER', '\Bitrix\Sale\Internals\Order',
				['=this.ORDER_ID' => 'ref.ID']
			),
			new Reference('DEAL', '\Bitrix\Crm\Deal',
				['=this.DEAL_ID' => 'ref.ID']
			),
		];
	}

	/**
	 * @param $orderId
	 * @return int|false
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getDealIdByOrderId($orderId)
	{
		$orderId = intval($orderId);
		if($orderId > 0)
		{
			$item = static::getList([
				'select' => ['DEAL_ID'],
				'filter' => [
					'=ORDER_ID' => $orderId,
				],
			])->fetch();
			if($item)
			{
				return $item['DEAL_ID'];
			}
		}

		return false;
	}

	/**
	 * @param $dealId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getDealOrders($dealId)
	{
		$result = [];

		$dealId = intval($dealId);
		if($dealId > 0)
		{
			$items = static::getList([
				'select' => ['ORDER_ID'],
				'filter' => [
					'=DEAL_ID' => $dealId,
				],
			]);
			while($item = $items->fetch())
			{
				$result[] = $item['ORDER_ID'];
			}
		}

		return $result;
	}

	/**
	 * @param $orderId
	 * @return Main\ORM\Data\DeleteResult
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function deleteByOrderId($orderId)
	{
		$dealId = static::getDealIdByOrderId($orderId);
		if($dealId)
		{
			return static::delete([
				'ORDER_ID' => $orderId,
				'DEAL_ID' => $dealId,
			]);
		}

		return new Main\ORM\Data\DeleteResult();
	}

	/**
	 * @param $dealId
	 * @return Main\ORM\Data\DeleteResult
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function deleteByDealId($dealId)
	{
		$orderIds = static::getDealOrders($dealId);
		foreach($orderIds as $orderId)
		{
			static::delete([
				'ORDER_ID' => $orderId,
				'DEAL_ID' => $dealId,
			]);
		}

		return new Main\ORM\Data\DeleteResult();
	}
}
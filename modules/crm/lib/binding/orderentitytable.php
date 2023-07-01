<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Binding;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;

/**
 * @package Bitrix\Crm\Binding
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_OrderEntity_Query query()
 * @method static EO_OrderEntity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_OrderEntity_Result getById($id)
 * @method static EO_OrderEntity_Result getList(array $parameters = [])
 * @method static EO_OrderEntity_Entity getEntity()
 * @method static \Bitrix\Crm\Binding\EO_OrderEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Binding\EO_OrderEntity_Collection createCollection()
 * @method static \Bitrix\Crm\Binding\EO_OrderEntity wakeUpObject($row)
 * @method static \Bitrix\Crm\Binding\EO_OrderEntity_Collection wakeUpCollection($rows)
 */
class OrderEntityTable extends Main\ORM\Data\DataManager
{
	/**
	 * Get table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_order_entity';
	}

	/**
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new IntegerField('OWNER_ID', [
				'primary' => true,
			]),
			new IntegerField('OWNER_TYPE_ID', [
				'primary' => true,
			]),
			new IntegerField('ORDER_ID', [
				'primary' => true,
				'unique' => true,
			]),
			new Reference('ORDER', '\Bitrix\Sale\Internals\Order',
				['=this.ORDER_ID' => 'ref.ID']
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
	public static function getOwnerByOrderId($orderId)
	{
		$orderId = intval($orderId);
		if ($orderId > 0)
		{
			return static::getList([
				'select' => ['OWNER_ID', 'OWNER_TYPE_ID'],
				'filter' => [
					'=ORDER_ID' => $orderId,
				],
			])->fetch();
		}

		return false;
	}

	/**
	 * @param int $ownerId
	 * @param int $ownerTypeId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getOrderIdsByOwner(int $ownerId, int $ownerTypeId)
	{
		$result = [];

		if ($ownerId > 0 && $ownerTypeId > 0)
		{
			$dbRes = static::getList([
				'select' => ['ORDER_ID'],
				'filter' => [
					'=OWNER_ID' => $ownerId,
					'=OWNER_TYPE_ID' => $ownerTypeId,
				],
				'order' => ['ORDER_ID' => 'DESC']
			]);

			while ($item = $dbRes->fetch())
			{
				$result[] = (int)$item['ORDER_ID'];
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
		$owner = static::getOwnerByOrderId($orderId);
		if ($owner)
		{
			return static::delete([
				'ORDER_ID' => $orderId,
				'OWNER_ID' => $owner['OWNER_ID'],
				'OWNER_TYPE_ID' => $owner['OWNER_TYPE_ID'],
			]);
		}

		return new Main\ORM\Data\DeleteResult();
	}

	public static function deleteByOwner(int $entityTypeId, int $entityId): Main\ORM\Data\DeleteResult
	{
		$result = new Main\ORM\Data\DeleteResult();

		$links = static::getList([
			'filter' => [
				'=OWNER_ID' => $entityId,
				'=OWNER_TYPE_ID' => $entityTypeId,
			],
		]);
		while ($link = $links->fetchObject())
		{
			$deleteResult = $link->delete();
			if (!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function rebind(int $entityTypeId, int $oldEntityId, int $newEntityId): void
	{
		$sql = "UPDATE IGNORE b_crm_order_entity SET  OWNER_ID = {$newEntityId} WHERE OWNER_TYPE_ID = {$entityTypeId} AND OWNER_ID = {$oldEntityId}";
		Application::getConnection()->query($sql);
	}
}

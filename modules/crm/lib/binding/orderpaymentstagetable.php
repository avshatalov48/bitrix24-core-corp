<?php

namespace Bitrix\Crm\Binding;

use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type;
use Bitrix\Crm\Order\PaymentStage;
use Bitrix\Sale\Internals\PaymentTable;

/**
 * ORM class represents current payment stage
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_OrderPaymentStage_Query query()
 * @method static EO_OrderPaymentStage_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_OrderPaymentStage_Result getById($id)
 * @method static EO_OrderPaymentStage_Result getList(array $parameters = array())
 * @method static EO_OrderPaymentStage_Entity getEntity()
 * @method static \Bitrix\Crm\Binding\EO_OrderPaymentStage createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Binding\EO_OrderPaymentStage_Collection createCollection()
 * @method static \Bitrix\Crm\Binding\EO_OrderPaymentStage wakeUpObject($row)
 * @method static \Bitrix\Crm\Binding\EO_OrderPaymentStage_Collection wakeUpCollection($rows)
 */
class OrderPaymentStageTable extends ORM\Data\DataManager
{
	/**
	 * Returns table name.
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_order_payment_stage';
	}

	/**
	 * Returns table structure
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField('PAYMENT_ID', [
				'primary' => true,
				'required' => true,
			]),
			new EnumField('STAGE', [
				'values' => PaymentStage::getValues(),
				'default_value' => PaymentStage::NOT_PAID,
				'required' => true,
			]),
			new DatetimeField('UPDATED_AT', [
				'default_value' => new Type\DateTime(),
			]),
			new Reference(
				'PAYMENT',
				PaymentTable::class,
				Join::on('this.PAYMENT_ID', 'ref.ID')
			),
		];
	}

	/**
	 * Simple helper creates new record or updates existing.
	 * Returns null if payment already in needed stage.
	 * 
	 * @param int $paymentId
	 * @param string $stage
	 * @return Bitrix\Main\ORM\Data\Result | null
	 */
	public static function setStage(int $paymentId, string $stage)
	{
		$row = static::getById($paymentId)->fetch();
		if ($row)
		{
			if ($row['STAGE'] !== $stage)
			{
				return static::update($paymentId, ['STAGE' => $stage]);
			}
			else
			{
				return null;
			}
		}
		else
		{
			return static::add([
				'PAYMENT_ID' => $paymentId,
				'STAGE' => $stage,
			]);
		}
	}
}

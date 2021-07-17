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

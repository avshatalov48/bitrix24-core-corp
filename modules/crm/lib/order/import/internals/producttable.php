<?php

namespace Bitrix\Crm\Order\Import\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

class ProductTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_order_import_product';
	}

	public static function getMap()
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
			],
			'PRODUCT_ID' => [
				'data_type' => 'integer',
				'required' => true,
			],
			'SOURCE_NAME' => [
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSourceName'),
				'required' => true,
			],
			'SOURCE_ID' => [
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSourceId'),
				'required' => true,
			],
			'SETTINGS' => [
				'data_type' => 'text',
				'serialized' => true,
			],
			'DATE_CREATE' => [
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => [__CLASS__, 'getCurrentDate'],
			],
			'DATE_MODIFY' => [
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => [__CLASS__, 'getCurrentDate'],
			],
		];
	}

	public static function validateSourceName()
	{
		return array(
			new LengthValidator(null, 100),
		);
	}

	public static function validateSourceId()
	{
		return array(
			new LengthValidator(null, 100),
		);
	}

	public static function getCurrentDate()
	{
		return new \Bitrix\Main\Type\DateTime();
	}

	public static function deleteByProductId($productId)
	{
		$list = static::getList([
			'select' => ['ID'],
			'filter' => ['=PRODUCT_ID' => $productId],
		]);

		foreach ($list as $item)
		{
			static::delete($item['ID']);
		}
	}
}

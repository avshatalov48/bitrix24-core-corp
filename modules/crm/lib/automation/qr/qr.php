<?php

namespace Bitrix\Crm\Automation\QR;

use Bitrix\Main;

/**
 * Class QrTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Qr_Query query()
 * @method static EO_Qr_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Qr_Result getById($id)
 * @method static EO_Qr_Result getList(array $parameters = [])
 * @method static EO_Qr_Entity getEntity()
 * @method static \Bitrix\Crm\Automation\QR\EO_Qr createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Automation\QR\EO_Qr_Collection createCollection()
 * @method static \Bitrix\Crm\Automation\QR\EO_Qr wakeUpObject($row)
 * @method static \Bitrix\Crm\Automation\QR\EO_Qr_Collection wakeUpCollection($rows)
 */
class QrTable extends Main\Entity\DataManager
{
	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_automation_qr';
	}

	/**
	 * Get table fields map.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			'ID' => [
				'primary' => true,
				'data_type' => 'string',
				'default_value' => function()
				{
					return str_replace('.' ,'', uniqid('', true));
				},
			],
			'OWNER_ID' => ['data_type' => 'string'],
			'ENTITY_TYPE_ID' => ['data_type' => 'integer'],
			'ENTITY_ID' => ['data_type' => 'integer'],
			'DESCRIPTION' => ['data_type' => 'string'],
			'COMPLETE_ACTION_LABEL' => ['data_type' => 'string'],
		];
	}

	public static function deleteByEntity(int $entityTypeId, int $entityId)
	{
		$iterator = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ENTITY_TYPE_ID' => $entityTypeId,
				'=ENTITY_ID' => $entityId,
			],
		]);

		if ($iterator)
		{
			while ($row = $iterator->fetch())
			{
				static::delete($row['ID']);
			}
		}
	}
}

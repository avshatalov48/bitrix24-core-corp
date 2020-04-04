<?php
namespace Bitrix\Crm\Widget\Custom\Entity;

use Bitrix\Main;
use Bitrix\Crm\Widget\Custom\SaleTarget;

class SaleTargetTable  extends Main\Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_widget_saletarget';
	}
	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'TYPE_ID' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => array(
					SaleTarget::TYPE_COMPANY,
					SaleTarget::TYPE_CATEGORY,
					SaleTarget::TYPE_USER
				)
			),
			'PERIOD_TYPE' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => array(
					SaleTarget::PERIOD_TYPE_YEAR,
					SaleTarget::PERIOD_TYPE_HALF,
					SaleTarget::PERIOD_TYPE_QUARTER,
					SaleTarget::PERIOD_TYPE_MONTH
				)
			),
			'PERIOD_YEAR' => array('data_type' => 'integer', 'required' => true),
			'PERIOD_HALF' => array('data_type' => 'integer'),
			'PERIOD_QUARTER' => array('data_type' => 'integer'),
			'PERIOD_MONTH' => array('data_type' => 'integer'),
			'TARGET_TYPE' => array(
				'data_type' => 'enum',
				'required' => true,
				'values' => array(
					SaleTarget::TARGET_TYPE_SUM,
					SaleTarget::TARGET_TYPE_QUANTITY
				)
			),
			'TARGET_GOAL' => array('data_type' => 'string', 'required' => true, 'serialized' => true),
			'CREATED' => array('data_type' => 'datetime', 'required' => true),
			'MODIFIED' => array('data_type' => 'datetime', 'required' => true),
			'AUTHOR_ID' => array('data_type' => 'integer'),
			'EDITOR_ID' => array('data_type' => 'integer'),
			'LEFT_BORDER' => array('data_type' => 'integer', 'default_value' => 0),
			'RIGHT_BORDER' => array('data_type' => 'integer', 'default_value' => 0)
		);
	}

	public static function deleteConflicted($periodType)
	{
		$result = static::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'!PERIOD_TYPE' => $periodType,
				'>=RIGHT_BORDER' => time()
			)
		));

		foreach ($result as $row)
		{
			static::delete($row['ID']);
		}

		return true;
	}
}
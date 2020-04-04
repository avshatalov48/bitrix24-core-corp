<?php
namespace Bitrix\Crm\Activity\Entity;
use Bitrix\Main;
use Bitrix\Main\Entity;
class CustomTypeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_custom_type';
	}
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'CREATED_DATE' => array(
				'data_type' => 'date',
				'required' => true
			),
			'NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateName')
			),
			'SORT' => array('data_type' => 'integer')
		);
	}
	/**
	 * Create validators for NAME field.
	 * @return array
	 */
	public static function validateName()
	{
		return array(new Main\Entity\Validator\Length(null, 255));
	}
}
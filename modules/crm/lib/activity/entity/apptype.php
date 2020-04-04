<?php
namespace Bitrix\Crm\Activity\Entity;
use Bitrix\Main;
use Bitrix\Main\Entity;
class AppTypeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_act_app_type';
	}
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'APP_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TYPE_ID' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateType')
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName')
			),
			'ICON_ID' => array('data_type' => 'integer')
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
	/**
	 * Create validators for TYPE_ID field.
	 * @return array
	 */
	public static function validateType()
	{
		return array(new Main\Entity\Validator\Length(null, 100));
	}
}
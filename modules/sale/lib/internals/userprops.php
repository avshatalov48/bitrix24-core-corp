<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Internals;

use	Bitrix\Main\Entity\DataManager,
	Bitrix\Main\Entity\Validator;

/**
 * Class UserPropsTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UserProps_Query query()
 * @method static EO_UserProps_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_UserProps_Result getById($id)
 * @method static EO_UserProps_Result getList(array $parameters = array())
 * @method static EO_UserProps_Entity getEntity()
 * @method static \Bitrix\Sale\Internals\EO_UserProps createObject($setDefaultValues = true)
 * @method static \Bitrix\Sale\Internals\EO_UserProps_Collection createCollection()
 * @method static \Bitrix\Sale\Internals\EO_UserProps wakeUpObject($row)
 * @method static \Bitrix\Sale\Internals\EO_UserProps_Collection wakeUpCollection($rows)
 */
class UserPropsTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_user_props';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'primary' => true,
				'autocomplete' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'NAME' => array(
				'required' => true,
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getNameValidators'),
			),
			'USER_ID' => array(
				'required' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'PERSON_TYPE_ID' => array(
				'required' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime',
			),
			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getXmlValidators'),
			),
			'VERSION_1C' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'get1CValidators'),
			),
		);
	}

	public static function getNameValidators()
	{
		return array(
			new Validator\Length(1, 255),
		);
	}

	public static function getXmlValidators()
	{
		return array(
			new Validator\Length(0, 50),
		);
	}

	public static function get1CValidators()
	{
		return array(
			new Validator\Length(0, 15),
		);
	}
}

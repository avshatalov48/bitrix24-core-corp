<?php
namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PresetTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_preset';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'required' => true),
			'COUNTRY_ID' => array('data_type' => 'integer', 'default_value' => 0),
			'DATE_CREATE' => array('data_type' => 'datetime', 'default_value' => new Main\Type\DateTime()),
			'DATE_MODIFY' => array('data_type' => 'datetime'),
			'CREATED_BY_ID' => array('data_type' => 'integer'),
			'MODIFY_BY_ID' => array('data_type' => 'integer'),
			'NAME' => array('data_type' => 'string', 'required' => true, 'validation' => array(__CLASS__, 'validateName')),
			'XML_ID' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateXmlId')),
			'ACTIVE' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'default_value' => 'Y'),
			'SORT' => array('data_type' => 'integer', 'default_value' => 500),
			'SETTINGS' => array('data_type' => 'text', 'serialized' => true)
		);
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255)
		);
	}

	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 45)
		);
	}

	/**
	 * Returns count of presets using filter.
	 *
	 * @param array $filter
	 * @return int
	 * @throws Main\ArgumentException
	 */
	public static function getCountByFilter($filter = array())
	{
		$params = array(
			'runtime' => array(
				'CNT' => array(
					'data_type' => 'integer',
					'expression' => array('COUNT(*)')
				)
			),
			'select' => array('CNT')
		);

		if(is_array($filter))
			$params['filter'] = $filter;

		$res = static::getList($params)->fetch();

		return intval($res['CNT']);
	}
}
<?php

namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BankDetailTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_bank_detail';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'required' => true),
			'ENTITY_ID' => array('data_type' => 'integer', 'required' => true),
			'COUNTRY_ID' => array('data_type' => 'integer', 'required' => true, 'default_value' => 0),
			'DATE_CREATE' => array('data_type' => 'datetime', 'default_value' => new Main\Type\DateTime()),
			'DATE_MODIFY' => array('data_type' => 'datetime'),
			'CREATED_BY_ID' => array('data_type' => 'integer'),
			'MODIFY_BY_ID' => array('data_type' => 'integer'),
			'NAME' => array('data_type' => 'string', 'required' => true, 'validation' => array(__CLASS__, 'validateName')),
			'CODE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateCode')),
			'XML_ID' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateXmlId')),
			'ORIGINATOR_ID' => array('data_type' => 'string'),
			'ACTIVE' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'default_value' => 'Y'),
			'SORT' => array('data_type' => 'integer', 'default_value' => 500),
			'RQ_BANK_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_BANK_ADDR' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_BANK_ROUTE_NUM' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField9')),
			'RQ_BIK' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField11')),
			'RQ_MFO' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField6')),
			'RQ_ACC_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField150')),
			'RQ_ACC_NUM' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField34')),
			'RQ_IIK' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField20')),
			'RQ_ACC_CURRENCY' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField100')),
			'RQ_COR_ACC_NUM' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField34')),
			'RQ_IBAN' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField34')),
			'RQ_SWIFT' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField11')),
			'RQ_BIC' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField11')),
			'COMMENTS' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateComments'))
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
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 45)
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
	 * Returns validators for RQ_... field with max length 255 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField255()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 150 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField150()
	{
		return array(
			new Main\Entity\Validator\Length(null, 150)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 100 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField100()
	{
		return array(
			new Main\Entity\Validator\Length(null, 100)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 34 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField34()
	{
		return array(
			new Main\Entity\Validator\Length(null, 34)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 20 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField20()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 11 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField11()
	{
		return array(
			new Main\Entity\Validator\Length(null, 11)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 9 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField9()
	{
		return array(
			new Main\Entity\Validator\Length(null, 9)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 6 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField6()
	{
		return array(
			new Main\Entity\Validator\Length(null, 6)
		);
	}

	/**
	 * Returns validators for COMMENTS field.
	 *
	 * @return array
	 */
	public static function validateComments()
	{
		return array(
			new Main\Entity\Validator\Length(null, 500)
		);
	}

	/**
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

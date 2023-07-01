<?php

namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class BankDetailTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_BankDetail_Query query()
 * @method static EO_BankDetail_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_BankDetail_Result getById($id)
 * @method static EO_BankDetail_Result getList(array $parameters = [])
 * @method static EO_BankDetail_Entity getEntity()
 * @method static \Bitrix\Crm\EO_BankDetail createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_BankDetail_Collection createCollection()
 * @method static \Bitrix\Crm\EO_BankDetail wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_BankDetail_Collection wakeUpCollection($rows)
 */
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
		return [
			'ID' => ['data_type' => 'integer', 'primary' => true, 'autocomplete' => true],
			'ENTITY_TYPE_ID' => ['data_type' => 'integer', 'required' => true],
			'ENTITY_ID' => ['data_type' => 'integer', 'required' => true],
			'COUNTRY_ID' => ['data_type' => 'integer', 'required' => true, 'default_value' => 0],
			'DATE_CREATE' => ['data_type' => 'datetime', 'default_value' => new Main\Type\DateTime()],
			'DATE_MODIFY' => ['data_type' => 'datetime'],
			'CREATED_BY_ID' => ['data_type' => 'integer'],
			'MODIFY_BY_ID' => ['data_type' => 'integer'],
			'NAME' => ['data_type' => 'string', 'required' => true, 'validation' => [__CLASS__, 'validateName']],
			'CODE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateCode']],
			'XML_ID' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateXmlId']],
			'ORIGINATOR_ID' => ['data_type' => 'string'],
			'ACTIVE' => ['data_type' => 'boolean', 'values' => ['N', 'Y'], 'default_value' => 'Y'],
			'SORT' => ['data_type' => 'integer', 'default_value' => 500],
			'RQ_BANK_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_BANK_CODE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'RQ_BANK_ADDR' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_BANK_ROUTE_NUM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField9']],
			'RQ_BIK' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField11']],
			'RQ_MFO' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField6']],
			'RQ_ACC_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField150']],
			'RQ_ACC_NUM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField34']],
			'RQ_ACC_TYPE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'RQ_IIK' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField20']],
			'RQ_ACC_CURRENCY' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField100']],
			'RQ_COR_ACC_NUM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField34']],
			'RQ_IBAN' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField34']],
			'RQ_SWIFT' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField11']],
			'RQ_BIC' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField11']],
			'RQ_CODEB' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField5']],
			'RQ_CODEG' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField5']],
			'RQ_RIB' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField2']],
			'RQ_AGENCY_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'COMMENTS' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateComments']]
		];
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
	 * Returns validators for RQ_... field with max length 50 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField50()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50)
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
	 * Returns validators for RQ_... field with max length 5 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField5()
	{
		return array(
			new Main\Entity\Validator\Length(null, 5)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 2 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField2()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2)
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

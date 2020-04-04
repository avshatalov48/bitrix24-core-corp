<?php

namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class RequisiteTable extends Entity\DataManager
{
	public static function getUfId()
	{
		return 'CRM_REQUISITE';
	}

	public static function getTableName()
	{
		return 'b_crm_requisite';
	}

	public static function getMap()
	{
		return array(
			'ID' => array('data_type' => 'integer', 'primary' => true, 'autocomplete' => true),
			'ENTITY_TYPE_ID' => array('data_type' => 'integer', 'required' => true),
			'ENTITY_ID' => array('data_type' => 'integer', 'required' => true),
			'PRESET_ID' => array('data_type' => 'integer', 'required' => true, 'default_value' => 0),
			'PRESET' => array(
				'data_type' => '\\Bitrix\\Crm\\Preset',
				'reference' => array('=this.PRESET_ID' => 'ref.ID')
			),
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
			'RQ_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField150')),
			'RQ_FIRST_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField50')),
			'RQ_LAST_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField50')),
			'RQ_SECOND_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField50')),
			'RQ_COMPANY_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_COMPANY_FULL_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField300')),
			'RQ_COMPANY_REG_DATE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField30')),
			'RQ_DIRECTOR' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField150')),
			'RQ_ACCOUNTANT' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField150')),
			'RQ_CEO_NAME' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField150')),
			'RQ_CEO_WORK_POS' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField150')),
			'RQ_ADDR' => array(
				'data_type' => 'Address',
				'reference' => array(
					'=this.ID' => 'ref.ENTITY_ID',
					'=ref.ENTITY_TYPE_ID' => array('?', \CCrmOwnerType::Requisite),
					'=ref.TYPE_ID' => array('?', EntityAddress::Primary)
				)
			),
			'RQ_CONTACT' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField150')),
			'RQ_EMAIL' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_PHONE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField30')),
			'RQ_FAX' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField30')),
			'RQ_IDENT_DOC' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_IDENT_DOC_SER' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField25')),
			'RQ_IDENT_DOC_NUM' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField25')),
			'RQ_IDENT_DOC_PERS_NUM' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField25')),
			'RQ_IDENT_DOC_DATE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField30')),
			'RQ_IDENT_DOC_ISSUED_BY' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_IDENT_DOC_DEP_CODE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField25')),
			'RQ_INN' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField15')),
			'RQ_KPP' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField9')),
			'RQ_USRLE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField20')),
			'RQ_IFNS' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_OGRN' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField13')),
			'RQ_OGRNIP' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField15')),
			'RQ_OKPO' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField12')),
			'RQ_OKTMO' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField11')),
			'RQ_OKVED' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255')),
			'RQ_EDRPOU' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField10')),
			'RQ_DRFO' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField10')),
			'RQ_KBE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField2')),
			'RQ_IIN' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField12')),
			'RQ_BIN' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField12')),
			'RQ_ST_CERT_SER' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField10')),
			'RQ_ST_CERT_NUM' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField15')),
			'RQ_ST_CERT_DATE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField30')),
			'RQ_VAT_PAYER' => array('data_type' => 'boolean', 'values' => array('N', 'Y'), 'default_value' => 'N'),
			'RQ_VAT_ID' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField20')),
			'RQ_VAT_CERT_SER' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField10')),
			'RQ_VAT_CERT_NUM' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField15')),
			'RQ_VAT_CERT_DATE' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField30')),
			'RQ_RESIDENCE_COUNTRY' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField128')),
			'RQ_BASE_DOC' => array('data_type' => 'string', 'validation' => array(__CLASS__, 'validateRqStringField255'))
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
	 * Returns validators for RQ_... field with max length 300 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField300()
	{
		return array(
			new Main\Entity\Validator\Length(null, 300)
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
	 * Returns validators for RQ_... field with max length 128 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField128()
	{
		return array(
			new Main\Entity\Validator\Length(null, 128)
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
	 * Returns validators for RQ_... field with max length 30 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField30()
	{
		return array(
			new Main\Entity\Validator\Length(null, 30)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 25 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField25()
	{
		return array(
			new Main\Entity\Validator\Length(null, 25)
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
	 * Returns validators for RQ_... field with max length 15 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField15()
	{
		return array(
			new Main\Entity\Validator\Length(null, 15)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 13 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField13()
	{
		return array(
			new Main\Entity\Validator\Length(null, 13)
		);
	}

	/**
	 * Returns validators for RQ_... field with max length 12 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField12()
	{
		return array(
			new Main\Entity\Validator\Length(null, 12)
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
	 * Returns validators for RQ_... field with max length 10 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField10()
	{
		return array(
			new Main\Entity\Validator\Length(null, 10)
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
	 * Returns validators for RQ_... field with max length 8 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField8()
	{
		return array(
			new Main\Entity\Validator\Length(null, 8)
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

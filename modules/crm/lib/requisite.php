<?php

namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

Loc::loadMessages(__FILE__);

/**
 * Class RequisiteTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Requisite_Query query()
 * @method static EO_Requisite_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Requisite_Result getById($id)
 * @method static EO_Requisite_Result getList(array $parameters = [])
 * @method static EO_Requisite_Entity getEntity()
 * @method static \Bitrix\Crm\EO_Requisite createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_Requisite_Collection createCollection()
 * @method static \Bitrix\Crm\EO_Requisite wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_Requisite_Collection wakeUpCollection($rows)
 */
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
		return [
			'ID' => ['data_type' => 'integer', 'primary' => true, 'autocomplete' => true],
			'ENTITY_TYPE_ID' => ['data_type' => 'integer', 'required' => true],
			'ENTITY_ID' => ['data_type' => 'integer', 'required' => true],
			'PRESET_ID' => ['data_type' => 'integer', 'required' => true, 'default_value' => 0],
			'PRESET' => [
				'data_type' => '\\Bitrix\\Crm\\Preset',
				'reference' => ['=this.PRESET_ID' => 'ref.ID']
			],
			'DATE_CREATE' => ['data_type' => 'datetime', 'default_value' => new Main\Type\DateTime()],
			'DATE_MODIFY' => ['data_type' => 'datetime'],
			'CREATED_BY_ID' => ['data_type' => 'integer'],
			'MODIFY_BY_ID' => ['data_type' => 'integer'],
			'NAME' => ['data_type' => 'string', 'required' => true, 'validation' => [__CLASS__, 'validateName']],
			'CODE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateCode']],
			'XML_ID' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateXmlId']],
			'ORIGINATOR_ID' => ['data_type' => 'string'],
			'ACTIVE' => ['data_type' => 'boolean', 'values' => ['N', 'Y'], 'default_value' => 'Y'],
			'ADDRESS_ONLY' => ['data_type' => 'boolean', 'values' => ['N', 'Y'], 'default_value' => 'N'],
			'SORT' => ['data_type' => 'integer', 'default_value' => 500],
			'RQ_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField150']],
			'RQ_FIRST_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'RQ_LAST_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'RQ_SECOND_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'RQ_COMPANY_ID' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_COMPANY_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_COMPANY_FULL_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField300']],
			'RQ_COMPANY_REG_DATE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField30']],
			'RQ_DIRECTOR' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField150']],
			'RQ_ACCOUNTANT' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField150']],
			'RQ_CEO_NAME' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField150']],
			'RQ_CEO_WORK_POS' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField150']],
			'RQ_ADDR' => [
				'data_type' => 'Address',
				'reference' => [
					'=this.ID' => 'ref.ENTITY_ID',
					'=ref.ENTITY_TYPE_ID' => ['?', CCrmOwnerType::Requisite],
					'=ref.TYPE_ID' => ['?', EntityAddressType::Primary]
				]
			],
			'RQ_CONTACT' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField150']],
			'RQ_EMAIL' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_PHONE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField30']],
			'RQ_FAX' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField30']],
			'RQ_IDENT_TYPE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'RQ_IDENT_DOC' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_IDENT_DOC_SER' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField25']],
			'RQ_IDENT_DOC_NUM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField25']],
			'RQ_IDENT_DOC_PERS_NUM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField25']],
			'RQ_IDENT_DOC_DATE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField30']],
			'RQ_IDENT_DOC_ISSUED_BY' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_IDENT_DOC_DEP_CODE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField25']],
			'RQ_INN' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField15']],
			'RQ_KPP' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField9']],
			'RQ_USRLE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField20']],
			'RQ_IFNS' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_OGRN' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField13']],
			'RQ_OGRNIP' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField15']],
			'RQ_OKPO' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField12']],
			'RQ_OKTMO' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField11']],
			'RQ_OKVED' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_EDRPOU' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField10']],
			'RQ_DRFO' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField10']],
			'RQ_KBE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField2']],
			'RQ_IIN' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField12']],
			'RQ_BIN' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField12']],
			'RQ_ST_CERT_SER' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField10']],
			'RQ_ST_CERT_NUM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField15']],
			'RQ_ST_CERT_DATE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField30']],
			'RQ_VAT_PAYER' => ['data_type' => 'boolean', 'values' => ['N', 'Y'], 'default_value' => 'N'],
			'RQ_VAT_ID' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField20']],
			'RQ_VAT_CERT_SER' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField10']],
			'RQ_VAT_CERT_NUM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField15']],
			'RQ_VAT_CERT_DATE' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField30']],
			'RQ_RESIDENCE_COUNTRY' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField128']],
			'RQ_BASE_DOC' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField255']],
			'RQ_REGON' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField9']],
			'RQ_KRS' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField10']],
			'RQ_PESEL' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField11']],
			'RQ_LEGAL_FORM' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField80']],
			'RQ_SIRET' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField20']],
			'RQ_SIREN' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField15']],
			'RQ_CAPITAL' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField30']],
			'RQ_RCS' => ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField50']],
			'RQ_CNPJ' =>  ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField20']],
			'RQ_STATE_REG' =>  ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField25']],
			'RQ_MNPL_REG' =>  ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField20']],
			'RQ_CPF' =>  ['data_type' => 'string', 'validation' => [__CLASS__, 'validateRqStringField20']],
			'RQ_SIGNATURE' => ['data_type' => 'integer'],
			'RQ_STAMP' => ['data_type' => 'integer'],
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
	 * Returns validators for RQ_... field with max length 80 chars.
	 *
	 * @return array
	 */
	public static function validateRqStringField80()
	{
		return array(
			new Main\Entity\Validator\Length(null, 80)
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

	public static function getUsedCountries(): array
	{
		$result = [];

		$entityTypeId = CCrmOwnerType::Requisite;
		$connection = Main\Application::getConnection();
		$sql =
			"SELECT DISTINCT P.COUNTRY_ID "
			. "FROM b_crm_requisite R "
			. "INNER JOIN b_crm_preset P "
			. "ON R.PRESET_ID = P.ID AND P.ENTITY_TYPE_ID = $entityTypeId"
		;
		$res = $connection->query($sql);
		if (is_object($res))
		{
			while($fields = $res->fetch())
			{
				$result[] = (int)$fields['COUNTRY_ID'];
			}
		}

		return $result;
	}
}

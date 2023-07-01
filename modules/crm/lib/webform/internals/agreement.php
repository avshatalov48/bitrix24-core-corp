<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2019 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\ORM;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class AgreementTable
 *
 * @package Bitrix\Crm\WebForm\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Agreement_Query query()
 * @method static EO_Agreement_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Agreement_Result getById($id)
 * @method static EO_Agreement_Result getList(array $parameters = [])
 * @method static EO_Agreement_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Agreement createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Agreement_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Agreement wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Agreement_Collection wakeUpCollection($rows)
 */
class AgreementTable extends ORM\Data\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_agreement';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'AGREEMENT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CHECKED' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'N',
				'values' => array('N','Y')
			),
			'REQUIRED' => array(
				'data_type' => 'boolean',
				'required' => true,
				'default_value' => 'Y',
				'values' => array('N','Y')
			),
		);
	}
}

<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class DuplicateOrganizationMatchCodeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_DuplicateOrganizationMatchCode_Query query()
 * @method static EO_DuplicateOrganizationMatchCode_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_DuplicateOrganizationMatchCode_Result getById($id)
 * @method static EO_DuplicateOrganizationMatchCode_Result getList(array $parameters = [])
 * @method static EO_DuplicateOrganizationMatchCode_Entity getEntity()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateOrganizationMatchCode createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateOrganizationMatchCode_Collection createCollection()
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateOrganizationMatchCode wakeUpObject($row)
 * @method static \Bitrix\Crm\Integrity\EO_DuplicateOrganizationMatchCode_Collection wakeUpCollection($rows)
 */
class DuplicateOrganizationMatchCodeTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_dp_org_mcd';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'ENTITY_TYPE_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'TITLE' => array(
				'data_type' => 'string',
				'required' => true
			)
		);
	}
}
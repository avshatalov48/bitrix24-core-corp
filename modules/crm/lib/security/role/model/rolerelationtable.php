<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class RoleRelationTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RoleRelation_Query query()
 * @method static EO_RoleRelation_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RoleRelation_Result getById($id)
 * @method static EO_RoleRelation_Result getList(array $parameters = [])
 * @method static EO_RoleRelation_Entity getEntity()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RoleRelation createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RoleRelation_Collection createCollection()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RoleRelation wakeUpObject($row)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RoleRelation_Collection wakeUpCollection($rows)
 */
class RoleRelationTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_crm_role_relation';
	}

	public static function getMap()
	{
		return [
			'ID' => (new IntegerField('ID'))
				->configurePrimary(true)
				->configureAutocomplete(true)
			,
			'ROLE_ID' => (new IntegerField('ROLE_ID'))
				->configureRequired(true)
			,
			'RELATION' => (new StringField('RELATION'))
				->configureRequired(true)
			,
		];
	}
}

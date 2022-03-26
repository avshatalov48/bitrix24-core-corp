<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;

/**
 * Class RoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Role_Query query()
 * @method static EO_Role_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Role_Result getById($id)
 * @method static EO_Role_Result getList(array $parameters = array())
 * @method static EO_Role_Entity getEntity()
 * @method static \Bitrix\Voximplant\Model\EO_Role createObject($setDefaultValues = true)
 * @method static \Bitrix\Voximplant\Model\EO_Role_Collection createCollection()
 * @method static \Bitrix\Voximplant\Model\EO_Role wakeUpObject($row)
 * @method static \Bitrix\Voximplant\Model\EO_Role_Collection wakeUpCollection($rows)
 */
class RoleTable extends Base
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_voximplant_role';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			'NAME' => new Entity\StringField('NAME', array(
				'required' => true,
			)),
		);
	}
}
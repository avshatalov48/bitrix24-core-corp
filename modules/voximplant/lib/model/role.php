<?php

namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Entity;

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
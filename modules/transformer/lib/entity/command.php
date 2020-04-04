<?php

namespace Bitrix\Transformer\Entity;

use Bitrix\Main;

/**
 * Class CommandTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> GUID string(32) mandatory
 * <li> STATUS int mandatory
 * <li> COMMAND string(255) mandatory
 * <li> MODULE string(255) mandatory
 * <li> CALLBACK string(255) mandatory
 * <li> PARAMS string mandatory
 * <li> FILE string
 * <li> ERROR string(255)
 * <li> UPDATE_TIME datetime mandatory
 * </ul>
 *
 * @package Bitrix\Transformer
 **/

class CommandTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_transformer_command';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			new Main\Entity\StringField('GUID', array('required' => true)),
			new Main\Entity\IntegerField('STATUS', array('required' => true)),
			new Main\Entity\StringField('COMMAND', array('required' => true)),
			new Main\Entity\StringField('MODULE', array('required' => true)),
			new Main\Entity\StringField('CALLBACK', array('required' => true)),
			new Main\Entity\StringField('PARAMS', array('required' => true)),
			new Main\Entity\StringField('FILE'),
			new Main\Entity\StringField('ERROR'),
			new Main\Entity\DatetimeField('UPDATE_TIME', array('default_value' => new Main\Type\DateTime())),
		);
	}
}
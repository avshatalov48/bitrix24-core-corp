<?php
namespace Bitrix\Forum;

use Bitrix\Main\Entity;

/**
 * Class ForumTable
 *
 * Fields:
 * <ul>
 * <li> ID int not null auto_increment,
 * <li> SORT int not null default '150',
 * <li> PARENT_ID int null,
 * <li> LEFT_MARGIN int null,
 * <li> RIGHT_MARGIN int null,
 * <li> DEPTH_LEVEL int null,
 * <li> XML_ID varchar(255)
 * </ul>
 *
 * @package Bitrix\Forum
 */
class GroupTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_forum_group';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Entity\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
			new Entity\IntegerField('SORT', ['default_value' => 150]),
			new Entity\IntegerField('PARENT_ID'),
			new Entity\IntegerField('LEFT_MARGIN'),
			new Entity\IntegerField('RIGHT_MARGIN'),
			new Entity\IntegerField('DEPTH_LEVEL'),
			new Entity\StringField('XML_ID', ['size' => 255]),
		];
	}
}

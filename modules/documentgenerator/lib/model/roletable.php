<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

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
 * @method static \Bitrix\DocumentGenerator\Model\Role createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Role_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\Role wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_Role_Collection wakeUpCollection($rows)
 */
class RoleTable extends DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_role';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new StringField('NAME', [
				'required' => true,
			]),
			new StringField('CODE'),
		];
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onBeforeDelete(Event $event)
	{
		$roleId = $event->getParameter('primary')['ID'];
		RoleAccessTable::deleteByRoleId($roleId);
		RolePermissionTable::deleteByRoleId($roleId);

		return new EventResult();
	}

	/**
	 * @return \Bitrix\Main\ORM\Objectify\EntityObject|string
	 */
	public static function getObjectClass()
	{
		return Role::class;
	}
}
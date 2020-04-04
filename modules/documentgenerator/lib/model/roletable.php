<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

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
<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Event;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;

/**
 * Class RoleTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Role_Query query()
 * @method static EO_Role_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Role_Result getById($id)
 * @method static EO_Role_Result getList(array $parameters = [])
 * @method static EO_Role_Entity getEntity()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role_Collection createCollection()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role wakeUpObject($row)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_Role_Collection wakeUpCollection($rows)
 */
class RoleTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_role';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,

			(new StringField('NAME'))
				->configureRequired()
				->configureSize(255)
			,

			(new StringField('IS_SYSTEM'))
				->configureRequired(false)
				->configureSize(1)
			,

			(new StringField('CODE'))
				->configureRequired(false)
				->configureSize(64)
			,

			(new StringField('GROUP_CODE'))
				->configureRequired(false)
				->configureSize(64)
			,

			(new OneToMany(
				'PERMISSIONS',
				RolePermissionTable::class,
				'ROLE',
			)),

			(new OneToMany(
				'RELATIONS',
				RoleRelationTable::class,
				'ROLE',
			)),
		];
	}

	public static function onAfterAdd(Event $event)
	{
		parent::onAfterAdd($event);

		if (!RolePermissionLogContext::getInstance()->isOrmEventsLogEnabled())
		{
			return;
		}

		$fields = $event->getParameters()['fields'] ?? [];
		$id = $event->getParameters()['id'] ?? 0;
		$fields['ID'] = $id;

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Added role #{ID}",
			RolePermissionLogContext::getInstance()->appendTo($fields)
		);
	}

	public static function onAfterUpdate(Event $event)
	{
		parent::onAfterUpdate($event);

		if (!RolePermissionLogContext::getInstance()->isOrmEventsLogEnabled())
		{
			return;
		}

		$fields = $event->getParameters()['fields'] ?? [];
		$id = $event->getParameters()['id'] ?? 0;
		$fields['ID'] = $id['ID'];

		foreach ($fields as $fieldId => $fieldValue)
		{
			$fields[$fieldId . '_OLD'] = $event->getParameters()['object']?->remindActual($fieldId);
		}

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Updated role #{ID}",
			RolePermissionLogContext::getInstance()->appendTo($fields)
		);
	}

	public static function onAfterDelete(Event $event)
	{
		parent::onAfterDelete($event);

		if (!RolePermissionLogContext::getInstance()->isOrmEventsLogEnabled())
		{
			return;
		}

		$fields = $event->getParameters()['fields'] ?? [];
		$id = $event->getParameters()['id'] ?? 0;
		$fields['ID'] = $id['ID'];

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Deleted role #{ID}",
			RolePermissionLogContext::getInstance()->appendTo($fields)
		);
	}
}

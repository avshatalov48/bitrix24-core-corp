<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Event;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;

/**
 * Class RolePermissionTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_RolePermission_Query query()
 * @method static EO_RolePermission_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_RolePermission_Result getById($id)
 * @method static EO_RolePermission_Result getList(array $parameters = [])
 * @method static EO_RolePermission_Entity getEntity()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection createCollection()
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission wakeUpObject($row)
 * @method static \Bitrix\Crm\Security\Role\Model\EO_RolePermission_Collection wakeUpCollection($rows)
 */
class RolePermissionTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_role_perms';
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,

			(new IntegerField('ROLE_ID'))
				->configureRequired()
			,

			(new StringField('ENTITY'))
				->configureRequired()
				->configureSize(20)
			,

			(new StringField('FIELD'))
				->configureDefaultValue('-')
				->configureSize(30)
			,

			(new StringField('FIELD_VALUE'))
				->configureNullable()
				->configureSize(255)
			,

			(new StringField('PERM_TYPE'))
				->configureRequired()
				->configureSize(20)
			,

			(new StringField('ATTR'))
				->configureDefaultValue('')
				->configureSize(1)
				->addFetchDataModifier([static::class, 'prepareAttr'])
			,

			(new ArrayField('SETTINGS'))
				->configureNullable()
				->configureDefaultValue('')
				->configureSerializationJson()
			,

			new ReferenceField(
				'ROLE',
				RoleTable::class,
				Join::on('this.ROLE_ID', 'ref.ID'),
			),
		];
	}

	/**
	 * @param int $roleId
	 * @param PermissionModel[] $permissionModels
	 * @throws Exception
	 */
	public static function appendPermissions(int $roleId, array $permissionModels): void
	{
		if (empty($permissionModels))
		{
			return;
		}

		self::removePermissions($roleId, $permissionModels);

		foreach ($permissionModels as $model)
		{
			if (!$model->isValidIdentifier())
			{
				continue;
			}
			if (\Bitrix\Crm\Security\Role\Utils\RolePermissionChecker::isPermissionEmpty($model))
			{
				continue;
			}

			RolePermissionTable::add([
				'ROLE_ID' => $roleId,
				'ENTITY' => $model->entity(),
				'FIELD' => $model->field(),
				'FIELD_VALUE' => $model->filedValue(),
				'PERM_TYPE' => $model->permissionCode(),
				'ATTR' => $model->attribute(),
				'SETTINGS' => $model->settings(),
			]);
		}
	}

	/**
	 * @param int $roleId
	 * @param PermissionModel[] $permissionModels
	 * @return void
	 */
	public static function removePermissions(int $roleId, array $permissionModels): void
	{
		if (empty($permissionModels))
		{
			return;
		}

		$entity = RolePermissionTable::getEntity();
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$logger = \Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions');
		foreach ($permissionModels as $model)
		{
			if (!$model->isValidIdentifier())
			{
				continue;
			}

			$ct = new ConditionTree();
			$ct
				->where('ROLE_ID', $roleId)
				->where('ENTITY', $model->entity())
				->where('FIELD_VALUE', $model->filedValue())
				->where('FIELD', $model->field())
				->where('PERM_TYPE', $model->permissionCode());

			$sql = sprintf(
				'DELETE FROM %s WHERE %s;',
				$sqlHelper->quote(self::getTableName()),
				Query::buildFilterSql($entity, $ct)
			);

			static::cleanCache();

			$somethingExists = self::query()
				->where($ct)
				->setLimit(1)
				->setSelect(['ID'])
				->fetch()
			;

			if ($somethingExists)
			{
				$connection->queryExecute($sql);
				if (RolePermissionLogContext::getInstance()->isOrmEventsLogEnabled())
				{
					$logger->info(
						"Deleted permissions in role #{ROLE_ID}",
						RolePermissionLogContext::getInstance()->appendTo([
							'ROLE_ID' => $roleId,
							'ENTITY' => $model->entity(),
							'PERM_TYPE' => $model->permissionCode(),
							'FIELD' => $model->field(),
							'FIELD_VALUE' => $model->filedValue(),
						])
					);
				}
			}
		}
	}

	public static function onAfterAdd(Event $event)
	{
		parent::onAfterAdd($event);

		if (!RolePermissionLogContext::getInstance()->isOrmEventsLogEnabled())
		{
			return;
		}

		$fields = $event->getParameters()['fields'] ?? [];

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Added permissions in role #{ROLE_ID}",
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
		foreach ($fields as $fieldId => $fieldValue)
		{
			$fields[$fieldId . '_OLD'] = $event->getParameters()['object']?->remindActual($fieldId);
		}

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Updated permissions in role #{ROLE_ID}",
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
		if (empty($fields) && ($event->getParameters()['object'] ?? null) && $event->getParameters()['object'] instanceof EO_RolePermission)
		{
			$fields = $event->getParameters()['object']->collectValues();
		}

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Deleted permissions in role #{ROLE_ID}",
			RolePermissionLogContext::getInstance()->appendTo($fields)
		);
	}

	public static function prepareAttr($attr): string
	{
		return trim($attr ?? '');
	}
}

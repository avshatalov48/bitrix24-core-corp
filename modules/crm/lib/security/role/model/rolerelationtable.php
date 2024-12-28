<?php

namespace Bitrix\Crm\Security\Role\Model;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Event;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Application;

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
	public static function getTableName(): string
	{
		return 'b_crm_role_relation';
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
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
			'ROLE' => new ReferenceField(
				'ROLE',
				RoleTable::class,
				Join::on('this.ROLE_ID', 'ref.ID'),
			),
		];
	}

	public static function updateForRole(int $roleId, array $relations): void
	{
		static::deleteAllForRole($roleId);
		foreach ($relations as $relation)
		{
			static::add([
				'ROLE_ID' => $roleId,
				'RELATION' => $relation,
			]);
		}

		static::cleanCache();
	}

	public static function deleteAllForRole(int $roleId): void
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$ct = new ConditionTree();
		$ct->where('ROLE_ID', $roleId);

		$sql = sprintf(
			'DELETE FROM %s WHERE %s;',
			$sqlHelper->quote(self::getTableName()),
			Query::buildFilterSql(static::getEntity(), $ct)
		);
		$connection->queryExecute($sql);

		\Bitrix\Crm\Service\Container::getInstance()->getLogger('Permissions')->info(
			"Deleted all relations for role #{roleId}",
			RolePermissionLogContext::getInstance()->appendTo([
				'roleId' => $roleId,
			])
		);
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
			"Added relation in role #{ROLE_ID}",
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
			"Updated relation in role #{ROLE_ID}",
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
			"Deleted relation in role #{ROLE_ID}",
			RolePermissionLogContext::getInstance()->appendTo($fields)
		);
	}
}

<?php

namespace Bitrix\Crm;

use Bitrix\Main\DB\Result;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Query;

class EntityPermsTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_entity_perms';
	}

	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Fields\StringField('ENTITY'))
				->configureSize(20)
				->configureRequired(),
			(new Fields\IntegerField('ENTITY_ID'))
				->configureRequired(),
			(new Fields\StringField('ATTR'))
				->configureSize(30)
				->configureRequired(),
		];
	}

	public static function clearByEntity(string $entityName, int $entityId): Result
	{
		$filter = [
			'=ENTITY' => $entityName,
			'=ENTITY_ID' => $entityId,
		];

		$entity = static::getEntity();
		$connection = $entity->getConnection();

		return $connection->query(sprintf(
			'DELETE FROM %s WHERE %s',
			$connection->getSqlHelper()->quote($entity->getDbTableName()),
			Query::buildFilterSql($entity, $filter)
		));
	}
}
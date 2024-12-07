<?php

namespace Bitrix\Crm\Security\AccessAttribute;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use CCrmOwnerType;

class DynamicBasedAttrTableLifecycle
{
	use Singleton;

	function createTable(string $typeName): Result
	{
		$result = new Result();

		$entity = Manager::getEntity($typeName);

		$entity->createDbTable();

		global $DB;
		if (!$DB->TableExists($entity->getDBTableName()))
		{
			$result->addError(new Error('Could not create item index table'));
			return $result;
		}

		try {
			$this->createIndex(
				$entity->getDBTableName(),
				'1',
				['ENTITY_ID', 'USER_ID', 'IS_OPENED', 'IS_ALWAYS_READABLE', 'PROGRESS_STEP'],
				true
			);
			$this->createIndex($entity->getDBTableName(), '2', ['USER_ID', 'ENTITY_ID'], false);
			$this->createIndex($entity->getDBTableName(), '3', ['IS_OPENED', 'ENTITY_ID'], false);
			$this->createIndex($entity->getDBTableName(), '4', ['ENTITY_ID', 'IS_OPENED'], false);
			$this->createIndex($entity->getDBTableName(), '5', ['IS_ALWAYS_READABLE', 'ENTITY_ID'], false);
			$this->createIndex($entity->getDBTableName(), '6', ['ENTITY_ID', 'IS_ALWAYS_READABLE'], false);
			$this->createIndex($entity->getDBTableName(), '7', ['CATEGORY_ID', 'ENTITY_ID'], false);
			$this->createIndex($entity->getDBTableName(), '8', ['ENTITY_ID', 'CATEGORY_ID', 'USER_ID'], false);
			$this->createIndex($entity->getDBTableName(), '9', ['CATEGORY_ID' ,'USER_ID', 'ENTITY_ID'], false);
		}
		catch (SystemException $e)
		{
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	private function createIndex(string $tableName, string $postfix, array $columns, bool $unique): void
	{
		global $DB;
		$indexName = 'IX_' . $tableName . '_' . $postfix;
		if ($DB->IndexExists($tableName, $columns, true))
		{
			return;
		}

		if (!$DB->CreateIndex($indexName, $tableName, $columns, $unique))
		{
			throw new SystemException("Could not create item index $indexName");
		}
	}

	public function dropTable(string $typeName): void
	{
		$entity = Manager::getEntity($typeName);
		$tableName = $entity->getDBTableName();
		if (Application::getConnection()->isTableExists($tableName))
		{
			Application::getConnection()->dropTable($tableName);
		}
	}

	public static function checkByEntityTypeIdIsTableExists(int $entityTypeId): bool
	{
		$entity = Manager::getEntity(CCrmOwnerType::ResolveName($entityTypeId));
		$tableName = $entity->getDBTableName();
		return Application::getConnection()->isTableExists($tableName);
	}
}

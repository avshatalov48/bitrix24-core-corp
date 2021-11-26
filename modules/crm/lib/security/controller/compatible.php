<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm;
use Bitrix\Main\Application;

class Compatible extends Crm\Security\Controller
{
	protected $cachedAttrs = [];

	public static function isEnabled(): bool
	{
		return true;
	}

	public function isPermissionEntityTypeSupported($entityType): bool
	{
		return true;
	}

	public function isEntityTypeSupported(int $entityTypeId): bool
	{
		return true;
	}

	public function getQueryBuilder(): QueryBuilder
	{
		return new QueryBuilder\Compatible();
	}

	public function getPermissionAttributes(string $permissionEntityType, array $entityIDs): array
	{
		$entityIDs = array_unique(array_filter(array_map('intval', $entityIDs)));
		if (empty($entityIDs))
		{
			return [];
		}

		$attributes = [];
		$entityPrefix = mb_strtoupper($permissionEntityType);
		$missedEntityIDs = [];
		foreach ($entityIDs as $entityId)
		{
			$entityKey = "{$entityPrefix}_{$entityId}";
			if (isset($this->cachedAttrs[$entityKey]))
			{
				$attributes[$entityId] = $this->cachedAttrs[$entityKey];
			}
			else
			{
				$missedEntityIDs[] = $entityId;
			}
		}

		if (empty($missedEntityIDs))
		{
			return $attributes;
		}

		// "SELECT ENTITY_ID, ATTR FROM b_crm_entity_perms WHERE ENTITY = '{$DB->ForSql($permissionEntityType)}' AND ENTITY_ID IN({$missedEntityIDs})"
		$attributesCollection = Crm\EntityPermsTable::getList([
			'select' => [
				'ENTITY_ID',
				'ATTR',
			],
			'filter' => [
				'=ENTITY' => $permissionEntityType,
				'@ENTITY_ID' => $missedEntityIDs,
			]
		]);

		while ($entityAttributesData = $attributesCollection->Fetch())
		{
			$entityId = $entityAttributesData['ENTITY_ID'];
			$entityAttribute = $entityAttributesData['ATTR'];
			$attributes[$entityId][] = $entityAttribute;

			$entityKey = "{$entityPrefix}_{$entityId}";
			if (!isset($this->cachedAttrs[$entityKey]))
			{
				$this->cachedAttrs[$entityKey] = [];
			}
			$this->cachedAttrs[$entityKey][] = $entityAttribute;
		}

		return $attributes;
	}

	protected function clearPermissionAttributesCache(string $permissionEntityType, int $entityId): void
	{
		$entityPrefix = mb_strtoupper($permissionEntityType);
		$entityKey = "{$entityPrefix}_{$entityId}";
		unset($this->cachedAttrs[$entityKey]);
	}

	public function register(string $entityType, int $entityId, ?RegisterOptions $options = null): void
	{
		$this->unregister($entityType, $entityId);

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityType = $sqlHelper->forSql(mb_strtoupper($entityType));
		$entityAttributes = $options ? $options->getEntityAttributes() : [];
		if (!empty($entityAttributes))
		{
			foreach ($entityAttributes as $attribute)
			{
				$attribute = $sqlHelper->forSql($attribute);
				$connection->query("INSERT INTO b_crm_entity_perms(ENTITY, ENTITY_ID, ATTR) VALUES ('{$entityType}', {$entityId}, '{$attribute}')");
			}
		}
	}


	public function unregister(string $entityType, int $entityId): void
	{
		$this->clearPermissionAttributesCache($entityType, $entityId);

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$entityType = $sqlHelper->forSql(mb_strtoupper($entityType));

		$connection->query("DELETE FROM b_crm_entity_perms WHERE ENTITY = '{$entityType}' AND ENTITY_ID = {$entityId}");
	}
}

<?php
namespace Bitrix\Crm\Entity;

use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\EO_Activity_Entity;
use Bitrix\Main\NotSupportedException;
use CAllCrmActivity;

class Activity extends EntityBase
{
	protected static ?Activity $instance = null;

	public static function getInstance(): Activity
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function getEntityTypeID(): int
	{
		return \CCrmOwnerType::Activity;
	}

	protected function getDbEntity(): EO_Activity_Entity
	{
		return ActivityTable::getEntity();
	}

	public function getDbTableAlias(): string
	{
		return CAllCrmActivity::TABLE_ALIAS;
	}

	protected function buildPermissionSql(array $params)
	{
		return \CCrmActivity::BuildPermSql(
			$params['alias'] ?? 'L',
			$params['permissionType'] ?? 'READ',
			isset($params['options']) && is_array($params['options']) ? $params['options'] : []
		);
	}

	public function checkReadPermission($entityID = 0, $userPermissions = null): bool
	{
		return \CCrmActivity::CheckReadPermission($entityID, $userPermissions);
	}

	public function checkDeletePermission($entityID = 0, $userPermissions = null): bool
	{
		return \CCrmActivity::CheckDeletePermission($entityID, $userPermissions);
	}

	protected function getTopIdsInCompatibilityMode(
		int $limit,
		array $order = [],
		array $filter = []
	): array
	{
		throw new NotSupportedException("Methode '$typeName' is not supported in current context");
	}

	public function getCount(array $params)
	{
		throw new NotSupportedException("Methode 'getCount' is not supported in current context");
	}

	public function delete($entityID, array $options = []): ?array
	{
		throw new NotSupportedException("Methode 'delete' is not supported in current context");
	}

	public static function getResponsibleID($entityID): ?int
	{
		throw new NotSupportedException("Methode 'getResponsibleID' is not supported in current context");
	}

	public function isExists($entityID): bool
	{
		throw new NotSupportedException("Methode 'isExists' is not supported in current context");
	}

	public static function selectExisted(array $entityIDs): array
	{
		throw new NotSupportedException("Methode 'selectExisted' is not supported in current context");
	}
}
<?php

namespace Bitrix\Crm\Security;

use Bitrix\Crm\Security\Controller\QueryBuilder;
use Bitrix\Crm\Security\Controller\RegisterOptions;

abstract class Controller
{
	abstract public function isPermissionEntityTypeSupported(string $entityType): bool;

	abstract public function isEntityTypeSupported(int $entityTypeId): bool;

	abstract public static function isEnabled(): bool;

	abstract public function getQueryBuilder(): QueryBuilder;

	/**
	 * @param string $permissionEntityType
	 * @param int[] $entityIDs
	 * @return array
	 */
	abstract public function getPermissionAttributes(string $permissionEntityType, array $entityIDs): array;

	abstract public function register(string $permissionEntityType, int $entityId, ?RegisterOptions $options = null): void;

	abstract public function unregister(string $permissionEntityType, int $entityId): void;
}

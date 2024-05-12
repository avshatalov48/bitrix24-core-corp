<?php

namespace Bitrix\Crm\Security\AccessAttribute;

use Bitrix\Crm\Service\UserPermissions;

/**
 * Array of user permission attributes grouped by entity type
 *
 * Entity permission attributes are restrictions that must be applied to queries
 * for a given EntityType for a user.
 *
 * There can be none, one or many restrictions for the entity
 *
 * Restrictions can be four types
 * 1. User restrictions. it looks like 'U6' or 'IU6' - U means user and 6 is user id.
 * 2. Progress step restrictions. it looks like 'STAGE_IDC7:NEW' - 'C' means Category and 7 is a category id.
 * 	'LEAD' means entity without category
 * 3. Open entities restrictions. it looks like 'O' (Open). Can access to all "open" entities
 * 4. Department restrictions. it looks like 'D2' - D means department and 2 is a department id
 *
 * The descriptions of restrictions above is example designed to give you an idea of what to expect there,
 * but is not a completely accurate description of all species.
 *
 * Some examples^
 * ```
 * [
 * 	'DEAL_C1' => [],  // means no access
 * 	'DEAL_C2' => [0], // means full access
 * 	'DEAL_C3'=> [ // has access with some restrictions
 * 		['STAGE_IDC3:NEW', 'U6'], // can access to deals with RESPONSIBLE field is user 6 on the NEW stage
 * 		['STAGE_IDC3:PREPAYMENT_INVOICE', 'D2'], //  RESPONSIBLE field contain users from department 2 on the PREPAYMENT_INVOICE stage
 * 		['STAGE_IDC3:EXECUTING', 'O'] // 'O' - (Open). Can access to all "open" entities on the EXECUTING stage
 * 	],
 *	'DEAL_C4' => ['U6'] // can access to deals with RESPONSIBLE field is user 6 on any stage
 * ]
 * ```
 *
 */
class Collection
{

	protected array $attributesByEntityType = [];

	protected int $userId = 0;

	/**
	 * @param UserPermissions $userPermissions
	 * @param string[] $requestedPermissionCheckEntityTypes
	 * @param string[] $requestedOperations - Bitrix\Crm\Service\UserPermission::OPERATIONS_* constants
	 * @return self
	 */
	public static function build(
		UserPermissions $userPermissions,
		array $requestedPermissionCheckEntityTypes,
		array $requestedOperations
	): self
	{
		$userId = $userPermissions->getUserId();
		$userAttributes = new Collection($userId);

		$attributesProvider = $userPermissions->getAttributesProvider();

		foreach ($requestedPermissionCheckEntityTypes as $permissionEntityType)
		{
			foreach ($requestedOperations as $operation)
			{
				$userAttributes->addByEntityType(
					$permissionEntityType,
					$attributesProvider->getEntityListAttributes(
						$permissionEntityType,
						$operation
					)
				);
			}
		}

		return $userAttributes;
	}

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function addByEntityType(string $permissionEntityType, array $attributes): void
	{
		if (!isset($this->attributesByEntityType[$permissionEntityType]))
		{
			$this->attributesByEntityType[$permissionEntityType] = [];
		}
		$this->attributesByEntityType[$permissionEntityType] = array_merge(
			$this->attributesByEntityType[$permissionEntityType],
			$attributes
		);
	}

	public function getByEntityType($permissionEntityType): array
	{
		return $this->attributesByEntityType[$permissionEntityType] ?? [];
	}

	public function getAllowedEntityTypes(): array
	{
		$allowedPermissionEntityTypes = [];

		foreach ($this->attributesByEntityType as $permissionEntityType => $entityUserAttributes)
		{
			if (!empty($entityUserAttributes))
			{
				$allowedPermissionEntityTypes[] = $permissionEntityType;
			}
		}

		return $allowedPermissionEntityTypes;
	}

	public function areAllEntityTypesAllowed(): bool
	{
		foreach ($this->attributesByEntityType as $entityUserAttributes)
		{
			if (empty($entityUserAttributes))
			{
				return false;
			}
		}

		return true;
	}
}

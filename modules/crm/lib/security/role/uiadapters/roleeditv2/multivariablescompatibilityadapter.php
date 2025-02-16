<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2;


use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;
use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Manage\Permissions\Permission;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Variants;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class MultivariablesCompatibilityAdapter
{
	private const VALUE_DELIMITER = '|';


	public static function getPermissionCodes(): array
	{
		return ['READ', 'ADD', 'WRITE', 'DELETE', 'EXPORT', 'IMPORT'];
	}
	/**
	 * @param EntityDTO[] $permissionEntities
	 * @param array $rolAssignedPermissions
	 * @return EntityDTO[]
	 */
	public function preparePermissionValues(array $permissionEntities, array $rolAssignedPermissions): array
	{
		$permissionValues = [];
		foreach ($rolAssignedPermissions as $assignedPermission)
		{
			if (!isset($permissionValues[$assignedPermission['ENTITY']][$assignedPermission['PERM_TYPE']]))
			{
				$permissionValues[$assignedPermission['ENTITY']][$assignedPermission['PERM_TYPE']] = [];
			}

			$permissionValues[$assignedPermission['ENTITY']][$assignedPermission['PERM_TYPE']][] = [
				'ATTR' => (string)$assignedPermission['ATTR'],
				'SETTINGS' => (array)$assignedPermission['SETTINGS'],
			];
		}
		foreach ($permissionEntities as $permissionEntity)
		{
			foreach ($permissionEntity->permissions() as $permission)
			{
				if ($this->isSuitablePermission($permission))
				{
					$variants = $this->getVariantsFromValues(
						$permissionValues[$permissionEntity->code()][$permission->code()] ?? []
					);
					$permission->setVariants($variants);
				}
			}
		}
		return $permissionEntities;
	}

	public function prepareRoleAssignedPermissions(
		\Bitrix\Crm\Security\Role\Manage\RoleManagementModelBuilder $permissionEntitiesBuilder,
		array $rolAssignedPermissions,
	): array
	{
		foreach ($rolAssignedPermissions as $index => $assignedPermission)
		{
			$permission = $permissionEntitiesBuilder->getPermissionByCode(
				$assignedPermission['ENTITY'],
				$assignedPermission['PERM_TYPE'],
			);
			if ($this->isSuitablePermission($permission) && !empty($assignedPermission['SETTINGS']))
			{
				$compatibleValue = (new UserRoleAndHierarchy())->tryConvertMultiToSingleValue(
					$assignedPermission['SETTINGS'],
				);
				if (!is_null($compatibleValue))
				{
					$rolAssignedPermissions[$index]['ATTR'] = $compatibleValue;
				}
				else
				{
					$rolAssignedPermissions[$index]['ATTR'] = $this->getAttrValueFromSettings(
						$assignedPermission['SETTINGS'],
					);
				}
				$rolAssignedPermissions[$index]['SETTINGS'] = null;
			}
		}

		return $rolAssignedPermissions;
	}

	/**
	 * @param PermissionModel[] $toChange
	 * @return void
	 */
	public function prepareChangedValues(array $toChange): array
	{
		$variants = (new UserRoleAndHierarchy())->getVariants();
		foreach ($toChange as $index => $item)
		{
			if (in_array($item->permissionCode(), self::getPermissionCodes()))
			{
				if ($variants->has($item->attribute()))
				{
					$toChange[$index] = new PermissionModel(
						$item->entity(),
						$item->permissionCode(),
						$item->field(),
						$item->filedValue(),
						null,
						[$item->attribute()]
					);
				}
				elseif(mb_strpos($item->attribute(), self::VALUE_DELIMITER))
				{
					$toChange[$index] = new PermissionModel(
						$item->entity(),
						$item->permissionCode(),
						$item->field(),
						$item->filedValue(),
						null,
						explode(self::VALUE_DELIMITER, $item->attribute())
					);
				}
			}
		}

		return $toChange;
	}

	private function isSuitablePermission(?Permission $permission): bool
	{
		if (!defined('\Bitrix\Main\Access\Permission\PermissionDictionary::TYPE_DEPENDENT_VARIABLES'))
		{
			$typeDependantVariables = 'dependent_variables';
		}
		else
		{
			$typeDependantVariables = PermissionDictionary::TYPE_DEPENDENT_VARIABLES;
		}

		return $permission
			&& in_array($permission->code(), self::getPermissionCodes())
			&& $permission->getControlMapper()->getType() === $typeDependantVariables
		;
	}

	private function getVariantsFromValues(array $permissionValues): Variants
	{
		$compatibleVariants = \Bitrix\Crm\Security\Role\Manage\PermissionAttrPresets::userHierarchyAndOpen();
		$newVariants = (new UserRoleAndHierarchy())->getVariants();

		$result = $compatibleVariants;
		foreach ($permissionValues as $permissionValue)
		{
			$settings = $permissionValue['SETTINGS'];

			if (!empty($settings)) // permissions in new format
			{
				$compatibleValue = (new UserRoleAndHierarchy())->tryConvertMultiToSingleValue($settings);

				if ($compatibleValue !== null && $result->has($compatibleValue))
				{
					continue;
				}

				$titleParts = [];
				foreach ($newVariants->toArray() as $code => $title)
				{
					if (in_array($code, $settings, true))
					{
						$titleParts[] = $title;
					}
				}

				$title = implode(' + ', $titleParts);
				$result->add($this->getAttrValueFromSettings($settings), $title);
			}
		}

		if ($result->has(UserPermissions::PERMISSION_ALL))
		{
			$result->moveToEndOfList(UserPermissions::PERMISSION_ALL);  // sel "All" to the end of variants list
		}

		return $result;
	}

	private function getAttrValueFromSettings(array $settings): string
	{
		sort($settings, SORT_STRING);

		return implode(self::VALUE_DELIMITER, $settings);
	}
}

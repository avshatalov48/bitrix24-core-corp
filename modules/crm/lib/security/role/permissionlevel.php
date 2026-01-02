<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Item;
use Bitrix\Crm\Security\AttributesProvider;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserDepartmentAndOpened;

class PermissionLevel
{
	private array $maxAttribute = [];
	private array $attributeToRoleRelations = [];
	private array $settingsToRoleRelations = [];
	private array $settingsValuesByField = [];

	private const EMPTY_FIELD_VALUE = '';

	public function __construct(
		private readonly int $userId,
		private readonly string $permissionEntity,
		private readonly string $permissionType,
		private readonly AttributesProvider $attributesProvider,
		private readonly bool $isAdmin,
	)
	{
	}

	public function addValueAttribute(string $attribute, int $roleId, string $field, string $fieldValue): self
	{
		$field = $this->normalizeField($field);
		$fieldKey = $this->getFieldKey($field, $fieldValue);

		$this->maxAttribute[$fieldKey] = max($attribute, $this->maxAttribute[$fieldKey] ?? UserPermissions::PERMISSION_NONE);
		if (!isset($this->attributeToRoleRelations[$fieldKey]))
		{
			$this->attributeToRoleRelations[$fieldKey] = [];
		}
		if (!isset($this->attributeToRoleRelations[$fieldKey][$attribute]))
		{
			$this->attributeToRoleRelations[$fieldKey][$attribute] = [];
		}
		$this->attributeToRoleRelations[$fieldKey][$attribute][] = $roleId;

		return $this;
	}

	public function addValueSettings(array $settings, int $roleId, string $field, string $fieldValue): self
	{
		$field = $this->normalizeField($field);
		$fieldKey = $this->getFieldKey($field, $fieldValue);

		foreach ($settings as $setting)
		{
			if (!isset($this->settingsToRoleRelations[$fieldKey]))
			{
				$this->settingsToRoleRelations[$fieldKey] = [];
			}
			if (!isset($this->settingsToRoleRelations[$fieldKey][$setting]))
			{
				$this->settingsToRoleRelations[$fieldKey][$setting] = [];
			}
			$this->settingsToRoleRelations[$fieldKey][$setting][] = $roleId;

			if (!isset($this->settingsValuesByField[$fieldValue]))
			{
				$this->settingsValuesByField[$fieldValue] = [];
			}
			$this->settingsValuesByField[$fieldValue][$setting] = $setting;
		}

		return $this;
	}

	public function hasPermission(): bool
	{
		if (empty($this->maxAttribute) || max($this->maxAttribute) === UserPermissions::PERMISSION_NONE)
		{
			$variants = (new \Bitrix\Crm\Security\Role\Manage\AttrPreset\UserDepartmentAndOpened())->getPermissiveSettingsVariantsList();
			foreach ($this->settingsValuesByField as $settings)
			{
				if (count(array_intersect($variants, array_values($settings))) > 0)
				{
					return true;
				}
			}
			return false;
		}

		return max($this->maxAttribute) > UserPermissions::PERMISSION_NONE;
	}

	/**
	 * Pay attention! This method is not compatible with permission stored in settings.
	 * Can be used for attributes only.
	 *
	 * @param string $level
	 * @return bool
	 */
	public function hasPermissionLevel(string $level): bool
	{
		if (empty($this->maxAttribute))
		{
			return false;
		}

		return max($this->maxAttribute) >= $level;
	}

	public function hasMaxPermissionLevel(): bool
	{
		$hasMaxPermission =
			isset($this->settingsValuesByField[self::EMPTY_FIELD_VALUE][UserDepartmentAndOpened::ALL])
			|| $this->maxAttribute[self::EMPTY_FIELD_VALUE] === UserPermissions::PERMISSION_ALL
		;
		if (!$hasMaxPermission)
		{
			return false;
		}

		foreach ($this->attributeToRoleRelations as $fieldKey => $attributes)
		{
			if ($fieldKey === self::EMPTY_FIELD_VALUE)
			{
				continue;
			}

			if (max(array_keys($attributes)) !== UserPermissions::PERMISSION_ALL)
			{
				return false;
			}
		}
		foreach ($this->settingsToRoleRelations as $fieldKey => $settings)
		{
			if ($fieldKey === self::EMPTY_FIELD_VALUE)
			{
				continue;
			}

			if (array_keys($settings) !== [UserDepartmentAndOpened::ALL])
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @internal
	 * @deprecated
	 * Used in backward compatibility methods only
	 * Will be removed soon!
	 */
	public function getPermissionAttributeByEntityAttributes(array $entityAttributes): string
	{
		if ($this->isAdmin)
		{
			return UserPermissions::PERMISSION_ALL;
		}

		if (empty($this->attributeToRoleRelations) && empty($this->settingsToRoleRelations))
		{
			return UserPermissions::PERMISSION_NONE;
		}

		if(
			$this->permissionType === UserPermissions::OPERATION_READ
			&& (
				in_array( UserPermissions::ATTRIBUTES_READ_ALL, $entityAttributes, true)
				|| in_array(UserPermissions::ATTRIBUTES_CONCERNED_USER_PREFIX.$this->userId, $entityAttributes, true)
			)
		)
		{
			return UserPermissions::PERMISSION_ALL;
		}

		if (empty($entityAttributes))
		{
			$attributes = $this->attributeToRoleRelations[self::EMPTY_FIELD_VALUE];

			return empty($attributes) ? UserPermissions::PERMISSION_NONE : max(array_keys($attributes));
		}

		foreach ($this->attributeToRoleRelations as $fieldKey => $attributes)
		{
			if ($fieldKey === self::EMPTY_FIELD_VALUE)
			{
				continue;
			}

			if (in_array($fieldKey, $entityAttributes))
			{
				return max(array_keys($attributes));
			}
		}
		$attributes = $this->attributeToRoleRelations[self::EMPTY_FIELD_VALUE];

		return empty($attributes) ? UserPermissions::PERMISSION_NONE : max(array_keys($attributes));
	}

	public function hasPermissionByEntityAttributes(array $entityAttributes): bool
	{
		return $this->getValueByEntityAttributes($entityAttributes)->hasSomePermissions();
	}

	/**
	 * @internal
	 */
	public function compareUserAttributesWithEntityAttributes(array $entityAttributes): bool
	{
		$permissionLevelValue = $this->getValueByEntityAttributes($entityAttributes);

		if (!$permissionLevelValue->hasSomePermissions())
		{
			return false;
		}

		if ($permissionLevelValue->hasMaxPermissions())
		{
			return true;
		}

		if (
			$permissionLevelValue->hasOpenedPermissions()
			&& in_array(UserPermissions::ATTRIBUTES_OPENED, $entityAttributes, true)
		)
		{
			return true;
		}

		if (
			$permissionLevelValue->hasSelfPermissions()
			&& in_array(UserPermissions::ATTRIBUTES_USER_PREFIX . $this->userId, $entityAttributes, true)
		)
		{
			return true;
		}

		$userAttributes = $this->attributesProvider->getUserAttributes();

		if (is_array($userAttributes['INTRANET']) && $permissionLevelValue->hasDepartmentPermissions())
		{
			foreach ($userAttributes['INTRANET'] as $departmentAccessCode)
			{
				if (in_array($departmentAccessCode, $entityAttributes, true))
				{
					return true;
				}
			}
		}

		if (is_array($userAttributes['SUBINTRANET']) && $permissionLevelValue->hasSubDepartmentsPermissions())
		{
			foreach ($userAttributes['SUBINTRANET'] as $departmentAccessCode)
			{
				if (in_array($departmentAccessCode, $entityAttributes, true))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @internal
	 */
	public function getEntityAttributesToCheckInListMode(): array
	{
		$result = [];
		if (!$this->hasPermission())
		{
			return $result;
		}

		$userAttributes = $this->attributesProvider->getUserAttributes();

		$defaultPermissionValue = $this->getValueFromSavedAttrsAndSettings(self::EMPTY_FIELD_VALUE);

		$availableFieldKeys = array_unique(array_merge(
			array_keys($this->attributeToRoleRelations),
			array_keys($this->settingsToRoleRelations),
		));

		if (
			$availableFieldKeys === [self::EMPTY_FIELD_VALUE]
			&& $defaultPermissionValue->hasSomePermissions()
		)
		{
			$result = array_merge(
				$result,
				$this->prepareAttributesByPermissionValue($userAttributes, $defaultPermissionValue)
			);
		}

		$relationsForFields = [];
		foreach ($availableFieldKeys as $fieldKey)
		{
			if ($fieldKey === self::EMPTY_FIELD_VALUE)
			{
				continue;
			}
			$relationsForFields[$fieldKey] = $this->getValueFromSavedAttrsAndSettings($fieldKey);
		}

		if (!empty($relationsForFields))
		{
			$stageFieldKeys = $this->getEntityStageFieldKeys($this->permissionEntity);
			foreach ($stageFieldKeys as $stageFieldKey)
			{
				$permissionValue = $relationsForFields[$stageFieldKey] ?? $defaultPermissionValue;

				$result = array_merge(
					$result,
					$this->prepareAttributesByPermissionValue($userAttributes, $permissionValue, $stageFieldKey)
				);
			}
		}

		return $result;
	}

	public function isPermissionLevelEqualsToByEntityAttributes(string $permissionAttribute, array $entityAttributes): bool
	{
		return $this->getValueByEntityAttributes($entityAttributes)->isEqualToPermissionAttribute($permissionAttribute);
	}

	protected function getValueByEntityAttributes(array $entityAttributes): PermissionLevelValue
	{
		if ($this->isAdmin)
		{
			return new PermissionLevelValue(UserPermissions::PERMISSION_ALL);
		}

		if (empty($this->attributeToRoleRelations) && empty($this->settingsToRoleRelations))
		{
			return new PermissionLevelValue(UserPermissions::PERMISSION_NONE);
		}

		if(
			$this->permissionType === UserPermissions::OPERATION_READ
			&& (
				in_array( UserPermissions::ATTRIBUTES_READ_ALL, $entityAttributes, true)
				|| in_array(UserPermissions::ATTRIBUTES_CONCERNED_USER_PREFIX.$this->userId, $entityAttributes, true)
			)
		)
		{
			return new PermissionLevelValue(UserPermissions::PERMISSION_ALL);
		}

		if (empty($entityAttributes))
		{
			return $this->getValueFromSavedAttrsAndSettings(self::EMPTY_FIELD_VALUE);
		}

		$availableFieldKeys = array_unique(array_merge(
			array_keys($this->attributeToRoleRelations),
			array_keys($this->settingsToRoleRelations),
		));

		foreach ($availableFieldKeys as $fieldKey)
		{
			if ($fieldKey === self::EMPTY_FIELD_VALUE)
			{
				continue;
			}

			if (in_array($fieldKey, $entityAttributes, true))
			{
				return $this->getValueFromSavedAttrsAndSettings($fieldKey);
			}
		}

		return $this->getValueFromSavedAttrsAndSettings(self::EMPTY_FIELD_VALUE);
	}

	private function normalizeField(string $field): string
	{
		if ($field === '-')
		{
			$field = self::EMPTY_FIELD_VALUE;
		}

		return $field;
	}

	private function getFieldKey(string $field, string $fieldValue): string
	{
		return $field . $fieldValue;
	}

	public function getSettingsForStage(string $stageId): array
	{
		$result = [];

		$settingsForStage = $this->settingsValuesByField[$stageId] ?? null;
		if (is_array($settingsForStage))
		{
			if (array_values($settingsForStage) === [UserPermissions::SETTINGS_INHERIT])
			{
				$result = $this->settingsValuesByField[self::EMPTY_FIELD_VALUE] ?? [];
			}
			else
			{
				$result = $settingsForStage;
			}
		}
		else
		{
			$result = $this->settingsValuesByField[self::EMPTY_FIELD_VALUE] ?? [];
		}

		return array_filter(array_values($result));
	}

	private function prepareAttributesByPermissionValue(
		array $userAttributes,
		PermissionLevelValue $permissionLevelValue,
		?string $statusRestriction = null
	): array
	{
		$result = [];
		$partOfResult = [];

		if (!$permissionLevelValue->hasSomePermissions())
		{
			return [];
		}
		elseif (!$permissionLevelValue->hasMaxPermissions())
		{
			if (
				$permissionLevelValue->hasSelfPermissions()
				|| $permissionLevelValue->hasDepartmentPermissions()
				|| $permissionLevelValue->hasSubDepartmentsPermissions()
				|| $permissionLevelValue->hasOpenedPermissions()
			)
			{
				foreach ($userAttributes['USER'] as $userId)
				{
					$result[] =  $statusRestriction ? [$statusRestriction, $userId] : [$userId];
				}
			}
			if (isset($userAttributes['INTRANET'])
				&& (
					$permissionLevelValue->hasDepartmentPermissions()
					|| $permissionLevelValue->hasSubDepartmentsPermissions()
				)
			)
			{
				foreach ($userAttributes['INTRANET'] as $departmentAccessCode)
				{
					//HACK: SKIP IU code it is not required for this method
					if ($departmentAccessCode != '' && mb_substr($departmentAccessCode, 0, 2) === 'IU')
					{
						continue;
					}

					if (!in_array($departmentAccessCode, $partOfResult))
					{
						$partOfResult[] = $departmentAccessCode;
					}
				}
			}
			if (isset($userAttributes['SUBINTRANET']) && $permissionLevelValue->hasSubDepartmentsPermissions())
			{
				foreach ($userAttributes['SUBINTRANET'] as $departmentAccessCode)
				{
					if ($departmentAccessCode != '' && mb_substr($departmentAccessCode, 0, 2) === 'IU')
					{
						continue;
					}

					if (!in_array($departmentAccessCode, $partOfResult))
					{
						$partOfResult[] = $departmentAccessCode;
					}
				}
			}
			if ($permissionLevelValue->hasOpenedPermissions())
			{
				$result[] = $statusRestriction ? [$statusRestriction, UserPermissions::ATTRIBUTES_OPENED] : [UserPermissions::ATTRIBUTES_OPENED];
			}
		}
		else //self::PERM_ALL
		{
			$result[] = $statusRestriction ? [$statusRestriction] : [];
		}

		if (!empty($partOfResult))
		{
			$result[] = $statusRestriction
				? array_merge([$statusRestriction], $partOfResult)
				: $partOfResult;
		}

		return $result;
	}

	protected function getEntityStageFieldKeys(string $permissionEntityType): array
	{
		static $cache = [];
		if (isset($cache[$permissionEntityType]))
		{
			return $cache[$permissionEntityType];
		}

		$stageFieldKEys = [];
		$categoryIdentifier = PermissionEntityTypeHelper::extractEntityAndCategoryFromPermissionEntityType($permissionEntityType);
		$entityTypeId = $categoryIdentifier?->getEntityTypeId();
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (
			$factory && $factory->isStagesSupported()
		)
		{
			$stageFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);

			$categoryId = $categoryIdentifier?->getCategoryId();
			$stages = $factory->getStages((int)$categoryId);
			foreach ($stages->getAll() as $stage)
			{
				$stageFieldKEys[] = $stageFieldName . $stage->getStatusId();
			}

		}
		$cache[$permissionEntityType] = $stageFieldKEys;

		return $stageFieldKEys;
	}

	private function getValueFromSavedAttrsAndSettings(string $fieldName): PermissionLevelValue
	{
		$attributes = $this->attributeToRoleRelations[$fieldName] ?? [];
		$settingsRoles = $this->settingsToRoleRelations[$fieldName] ?? [];

		$excludeAttributesValuesForRoleIds = [];
		foreach ($settingsRoles as $roleIds)
		{
			$excludeAttributesValuesForRoleIds = array_merge(
				$excludeAttributesValuesForRoleIds,
				$roleIds
			);
		}
		foreach ($attributes as $attributeValue => $roleIds)
		{
			$roleIds = array_diff($roleIds, $excludeAttributesValuesForRoleIds); // remove roles which have settings form attributes to avoid value ambiguity
			if (empty($roleIds))
			{
				unset($attributes[$attributeValue]);
			}
			else
			{
				$attributes[$attributeValue] = $roleIds;
			}
		}

		$maxAttributeValue = empty($attributes) ? UserPermissions::PERMISSION_NONE : max(array_keys($attributes));

		return new PermissionLevelValue(
			$maxAttributeValue,
			array_keys($settingsRoles)
		);
	}
}

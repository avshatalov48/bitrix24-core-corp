<?php

namespace Bitrix\Crm\Security\Role;

use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;

class RolePermissionComparer
{
	private array $valuesToDelete = [];
	private array $valuesToAdd = [];
	public function __construct(private array $oldValues, private array $newValues)
	{
		// convert old and new values to the same format:
		$preparedOldValues = $this->getPreparedOldValues();
		$preparedNewValues = $this->getPreparedNewValues();

		foreach ($preparedOldValues as $entity => $entityPermissions)
		{
			foreach ($entityPermissions as $permissionType => $permissionByField)
			{
				foreach ($permissionByField as $field => $permissionFieldValue)
				{
					foreach ($permissionFieldValue as $fieldValue => $permissionValue)
					{
						$newValueExists = isset($preparedNewValues[$entity][$permissionType][$field][$fieldValue]);
						$newValue = $newValueExists ? $preparedNewValues[$entity][$permissionType][$field][$fieldValue] : [];
						if (
							($newValue['ATTR'] ?? null) !== $permissionValue['ATTR']
							|| ($newValue['SETTINGS'] ?? null) !== $permissionValue['SETTINGS']
						)
						{
							if ($newValueExists)
							{
								$this->valuesToAdd[] = new PermissionModel(
									$entity,
									$permissionType,
									$field,
									$fieldValue === '' ? null : $fieldValue,
									$newValue['HAS_ATTR'] ? $newValue['ATTR'] : $permissionValue['ATTR'],  // if new value doesn't contain attrs, use previous value
									$newValue['HAS_SETTINGS'] ? $newValue['SETTINGS'] : $permissionValue['SETTINGS'], // if new value doesn't contain settings, use previous value
								);
							}
							else
							{
								$this->valuesToDelete[] = new PermissionModel(
									$entity,
									$permissionType,
									$field,
									$fieldValue  === '' ? null : $fieldValue,
									$permissionValue['ATTR'],
									$permissionValue['SETTINGS'],
								);
							}
						}
					}
				}
			}
		}

		foreach ($preparedNewValues as $entity => $entityPermissions)
		{
			foreach ($entityPermissions as $permissionType => $permissionByField)
			{
				foreach ($permissionByField as $field => $permissionFieldValue)
				{
					foreach ($permissionFieldValue as $fieldValue => $permissionValue)
					{
						if (!isset($preparedOldValues[$entity][$permissionType][$field][$fieldValue]))
						{
							$this->valuesToAdd[] = new PermissionModel(
								$entity,
								$permissionType,
								$field,
								$fieldValue  === '' ? null : $fieldValue,
								$permissionValue['ATTR'],
								$permissionValue['SETTINGS'],
							);
						}
					}
				}
			}
		}
	}

	/**
	 * @return PermissionModel[]
	 */
	public function getValuesToDelete(): array
	{
		return $this->valuesToDelete;
	}

	/**
	 * @return PermissionModel[]
	 */
	public function getValuesToAdd(): array
	{
		return $this->valuesToAdd;
	}

	private function getPreparedNewValues(): array
	{
		$preparedNewValues = [];
		foreach ($this->newValues as $entity => $permissions)
		{
			foreach ($permissions as $permissionType => $permission)
			{
				foreach ($permission as $field => $value)
				{
					if ($field === '-')
					{
						$attr = null;
						$settings = null;
						$hasSettings = false;
						$hasAttr = false;
						if (is_array($value))
						{
							$attr = $value['ATTR'] ?? null;
							$settings = !empty($value['SETTINGS']) ? $value['SETTINGS'] : null;
							$hasSettings = array_key_exists('SETTINGS', $value);
							$hasAttr = array_key_exists('ATTR', $value);
						} else
						{
							$attr = trim($value);
							$hasSettings = false;
							$hasAttr = true;
						}
						if ($attr !== '-')
						{
							$preparedNewValues[$entity][$permissionType][$field][''] = [
								'ATTR' => $attr,
								'SETTINGS' => $settings,
								'HAS_ATTR' => $hasAttr,
								'HAS_SETTINGS' => $hasSettings,
							];
						}
					}
					else
					{
						foreach ($value as $fieldValue => $permValue)
						{
							$attr = null;
							$settings = null;
							$hasAttr = false;
							$hasSettings = false;
							if (is_array($permValue))
							{
								$attr = $permValue['ATTR'] ?? null;
								$settings = !empty($permValue['SETTINGS']) ? $permValue['SETTINGS'] : null;
								$hasSettings = array_key_exists('SETTINGS', $permValue);
								$hasAttr = array_key_exists('ATTR', $permValue);
							} else
							{
								$attr = trim($permValue);
								$hasSettings = false;
								$hasAttr = true;
							}
							if ($attr !== '-')
							{
								$preparedNewValues[$entity][$permissionType][$field][$fieldValue] = [
									'ATTR' => $attr,
									'SETTINGS' => $settings,
									'HAS_ATTR' => $hasAttr,
									'HAS_SETTINGS' => $hasSettings,
								];
							}
						}
					}
				}
			}
		}

		return $preparedNewValues;
	}

	private function getPreparedOldValues(): array
	{
		$preparedOldValues = [];
		foreach ($this->oldValues as $oldValue)
		{
			$preparedOldValues[$oldValue['ENTITY']][$oldValue['PERM_TYPE']][$oldValue['FIELD']][$oldValue['FIELD_VALUE'] ?? ''] = [
				'ATTR' => $oldValue['ATTR'],
				'SETTINGS' => !empty($oldValue['SETTINGS']) ? $oldValue['SETTINGS'] : null,
				'ID' => $oldValue['ID'],
			];
		}
		return $preparedOldValues;
	}
}

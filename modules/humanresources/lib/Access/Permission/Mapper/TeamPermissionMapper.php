<?php

namespace Bitrix\HumanResources\Access\Permission\Mapper;

use Bitrix\HumanResources\Access\Enum\PermissionValueType;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Item\Collection\Access\PermissionCollection;

final class TeamPermissionMapper extends BasePermissionMapper
{
	private int $teamValue = PermissionVariablesDictionary::VARIABLE_NONE;
	private int $departmentValue = PermissionVariablesDictionary::VARIABLE_NONE;

	private function __construct()
	{
	}

	public static function createById(string $permissionId): TeamPermissionMapper
	{
		if (!PermissionDictionary::isTeamDependentVariablesPermission($permissionId))
		{
			throw new \InvalidArgumentException('Unavailable permission id');
		}

		return
			(new TeamPermissionMapper())
				->setPermissionId($permissionId)
		;
	}

	/**
	 * @param array<array{id: string, value: int}> $permissions
	 *
	 * @return TeamPermissionMapper
	 */
	public static function createFromArray(array $permissions): TeamPermissionMapper
	{
		$teamPermissionMapper = new TeamPermissionMapper();

		foreach ($permissions as $permission)
		{
			if (!is_array($permission))
			{
				throw new \InvalidArgumentException('Permission must be array');
			}

			if (!isset($permission['id']) || !PermissionDictionary::isTeamDependentVariablesPermission((string)$permission['id']))
			{
				throw new \InvalidArgumentException('Unavailable permission id');
			}
			$teamPermissionMapper->setPermissionId((string)$permission['id']);

			$value = (int)$permission['value'];
			$valueType = PermissionVariablesDictionary::getPermissionValueType($value);
			if ($valueType === PermissionValueType::AllValue)
			{
				return
					$teamPermissionMapper
						->setTeamValue($value)
						->setDepartmentValue($value)
				;
			}

			if ($valueType === PermissionValueType::DepartmentValue)
			{
				$teamPermissionMapper->setHigherDepartmentValue($value);
			}

			if ($valueType === PermissionValueType::TeamValue)
			{
				$teamPermissionMapper->setHigherTeamValue($value);
			}
		}

		return $teamPermissionMapper;
	}

	public static function createFromCollection(PermissionCollection $permissionCollection): TeamPermissionMapper
	{
		if ($permissionCollection->count() !== 2)
		{
			throw new \InvalidArgumentException('Invalid permission collection');
		}

		$teamPermissionMapper = new TeamPermissionMapper();
		foreach ($permissionCollection as $permission)
		{
			$permissionParts = explode('_', $permission->permissionId, 2);
			$permissionId = $permissionParts[0];
			$permissionType = $permissionParts[1] ?? null;

			if ($permissionType === PermissionValueType::TeamValue->value)
			{
				$teamPermissionMapper->setPermissionId($permissionId);
				$teamPermissionMapper->setHigherTeamValue($permission->value);

				continue;
			}

			if ($permissionType === PermissionValueType::DepartmentValue->value)
			{
				$teamPermissionMapper->setHigherDepartmentValue($permission->value);

				continue;
			}

			throw new \InvalidArgumentException('Invalid permission collection');
		}

		return $teamPermissionMapper;
	}

	public function setTeamValue(int $value): TeamPermissionMapper
	{
		$this->teamValue = $value;

		return $this;
	}

	public function setDepartmentValue(int $value): TeamPermissionMapper
	{
		$this->departmentValue = $value;

		return $this;
	}

	public function getTeamPermissionId(): string
	{
		return self::makeTeamPermissionId((string)$this->getPermissionId(), PermissionValueType::TeamValue);
	}

	public function getTeamPermissionValue(): int
	{
		return $this->teamValue;
	}

	public function getDepartmentPermissionId(): string
	{
		return self::makeTeamPermissionId((string)$this->getPermissionId(), PermissionValueType::DepartmentValue);
	}

	public function getDepartmentPermissionValue(): int
	{
		return $this->departmentValue;
	}

	public static function getTeamValueTypeByPermissionId(string $permissionId): PermissionValueType
	{
		$permissionId = explode('_', $permissionId, 2)[1];

		return PermissionValueType::tryFrom($permissionId);
	}

	public static function transformPermissionToAccessRights(string $permissionId, int $permissionValue): array
	{
		$permissionParts = explode('_', $permissionId, 2);
		$permissionId = $permissionParts[0];
		$permissionType = $permissionParts[1] ?? null;

		$accessRights = [];
		if (
			$permissionType !== PermissionValueType::TeamValue->value
			&& $permissionType !== PermissionValueType::DepartmentValue->value
		)
		{
			return $accessRights;
		}

		if (
			$permissionType === PermissionValueType::TeamValue->value
			&& $permissionValue !== PermissionVariablesDictionary::VARIABLE_NONE
		)
		{
			$accessRights[] = [
				'id' => $permissionId,
				'value' => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS,
			];

			if ($permissionValue > PermissionVariablesDictionary::VARIABLE_SELF_TEAMS)
			{
				$accessRights[] = [
					'id' => $permissionId,
					'value' => PermissionVariablesDictionary::VARIABLE_SELF_TEAMS_SUB_TEAMS,
				];
			}

			if ($permissionValue === PermissionVariablesDictionary::VARIABLE_ALL)
			{
				$accessRights[] = [
					'id' => $permissionId,
					'value' => PermissionVariablesDictionary::VARIABLE_ALL,
				];
			}
		}

		if (
			$permissionType === PermissionValueType::DepartmentValue->value
			&& $permissionValue !== PermissionVariablesDictionary::VARIABLE_NONE
		)
		{
			$accessRights[] = [
				'id' => $permissionId,
				'value' => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS,
			];

			if ($permissionValue > PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS)
			{
				$accessRights[] = [
					'id' => $permissionId,
					'value' => PermissionVariablesDictionary::VARIABLE_SELF_DEPARTMENTS_SUB_DEPARTMENTS,
				];
			}
		}

		return $accessRights;
	}

	public static function makeTeamPermissionId(string $permissionId, PermissionValueType $permissionValueType): string
	{
		if (
			!PermissionDictionary::isTeamDependentVariablesPermission($permissionId)
			|| (
				$permissionValueType !== PermissionValueType::TeamValue
				&& $permissionValueType !== PermissionValueType::DepartmentValue
			)
		)
		{
			throw new \InvalidArgumentException('Invalid permission collection');
		}

		return $permissionId . '_' . $permissionValueType->value;
	}

	private function setHigherTeamValue(int $value): void
	{
		$value = max($this->teamValue, $value);
		$this->teamValue = $value;
	}

	private function setHigherDepartmentValue(int $value): void
	{
		$value = max($this->departmentValue, $value);
		$this->departmentValue = $value;
	}
}
<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables;

use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserDepartmentAndOpened;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Main\Access\Permission\PermissionDictionary;

abstract class UserDepartmentAndOpenedBase extends BaseControlMapper
{
	private const ALIAS_SEPARATOR = '|';

	private array $aliases = [];
	protected UserDepartmentAndOpened $permissionPreset;

	public function getType(): string
	{
		return PermissionDictionary::TYPE_DEPENDENT_VARIABLES;
	}

	public function setPermissionPreset(UserDepartmentAndOpened $permissionPreset): self
	{
		$this->permissionPreset = $permissionPreset;

		return $this;
	}

	public function getMinValue(): string|array|null
	{
		return $this->permissionPreset->convertSingleToMultiValue($this->permission->getMinAttributeValue());
	}

	public function getMaxValue(): string|array|null
	{
		return $this->permissionPreset->convertSingleToMultiValue(
			$this->permission->getMaxAttributeValue(),
		);
	}

	public function addSelectedVariablesAlias(array $variableIds, string $alias): self
	{
		sort($variableIds, SORT_STRING);
		$key = implode(self::ALIAS_SEPARATOR, $variableIds);

		$this->aliases[$key] = $alias;

		return $this;
	}

	public function getExtraOptions(): array
	{
		return [
			'selectedVariablesAliases' => ['separator' => self::ALIAS_SEPARATOR] + $this->aliases,
		];
	}
}

<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables;

use Bitrix\Crm\Security\Role\Manage\AttrPreset\UserRoleAndHierarchy;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class UserRoleAndHierarchyAsAttributes extends BaseControlMapper
{
	private const ALIAS_SEPARATOR = '|';

	private array $aliases = [];
	private UserRoleAndHierarchy $hierarchy;

	public function getType(): string
	{
		if (!defined('\Bitrix\Main\Access\Permission\PermissionDictionary::TYPE_DEPENDENT_VARIABLES'))
		{
			return 'dependent_variables';
		}

		return PermissionDictionary::TYPE_DEPENDENT_VARIABLES;
	}

	public function setHierarchy(UserRoleAndHierarchy $hierarchy): self
	{
		$this->hierarchy = $hierarchy;

		return $this;
	}

	public function getValueForUi(?string $attr, ?array $settings)
	{
		return $this->hierarchy->convertSingleToMultiValue((string)$attr);
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		return $this->hierarchy->tryConvertMultiToSingleValue($value);
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return null;
	}

	public function getMinValue(): string|array|null
	{
		return $this->hierarchy->convertSingleToMultiValue($this->permission->getMinAttributeValue());
	}

	public function getMaxValue(): string|array|null
	{
		return $this->hierarchy->convertSingleToMultiValue(
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

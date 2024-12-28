<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlType\DependentVariables\ValuesStrategy;
use Bitrix\Main\Access\Permission\PermissionDictionary;

class DependentVariables extends BaseControlType
{
	private const ALIAS_SEPARATOR = '|';

	private ValuesStrategy $valuesStrategy;
	private array $aliases = [];

	public function __construct()
	{
		$this->valuesStrategy = new ValuesStrategy\SettingsAsValues();
	}

	public function getType(): string
	{
		if (!defined('\Bitrix\Main\Access\Permission\PermissionDictionary::TYPE_DEPENDENT_VARIABLES'))
		{
			return 'dependent_variables';
		}

		return PermissionDictionary::TYPE_DEPENDENT_VARIABLES;
	}

	public function getMinValue(): string | array | null
	{
		return $this->valuesStrategy->getMinValue($this->permission);
	}

	public function getMaxValue(): string | array | null
	{
		return $this->valuesStrategy->getMaxValue($this->permission);
	}

	public function getValueForUi(?string $attr, ?array $settings): array | string | null
	{
		return $this->valuesStrategy->getValueForUi($this->permission, $attr, $settings);
	}

	public function getAttrFromUiValue(array $value): ?string
	{
		return $this->valuesStrategy->getAttrFromUiValue($value);
	}

	public function getSettingsFromUiValue(array $value): ?array
	{
		return $this->valuesStrategy->getSettingsFromUiValue($value);
	}

	public function setUseAttributesAsValues(bool $useAttributesAsValues): self
	{
		if ($useAttributesAsValues)
		{
			$this->valuesStrategy = new ValuesStrategy\AttributesAsValues();
		}
		else
		{
			$this->valuesStrategy = new ValuesStrategy\SettingsAsValues();
		}

		return $this;
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

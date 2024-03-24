<?php

namespace Bitrix\Tasks\Integration\CRM\Fields;

class EmulationData
{
	private const CRM_VALUE_KEY = 0;

	private string $name;
	private string $value;
	private string $class = 'tasks-task-temporary-crm-input';
	private string $type = 'hidden';

	public function __construct(array $crmUf)
	{
		$this->name =
			isset($crmUf['FIELD_NAME']) && is_string($crmUf['FIELD_NAME'])
				? $crmUf['FIELD_NAME']
				: ''
		;

		$this->name =
			isset($crmUf['MULTIPLE']) && $crmUf['MULTIPLE'] === 'Y' && !empty($this->name)
				? $this->name . '[]'
				: $this->name
		;

		$values =
			isset($crmUf['VALUE']) && is_array($crmUf['VALUE']) && !empty($crmUf['VALUE'])
				? $crmUf['VALUE']
				: []
		;

		$this->value =
			isset($values[static::CRM_VALUE_KEY]) && is_string($values[static::CRM_VALUE_KEY])
				? $values[static::CRM_VALUE_KEY]
				: ''
		;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function getType(): string
	{
		return $this->type;
	}
}
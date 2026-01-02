<?php

namespace Bitrix\Crm\Service\WebForm;

use Bitrix\Crm\Service\WebForm\DefaultValue\CompanyPhoneRule;
use Bitrix\Crm\Service\WebForm\DefaultValue\ContactPhoneRule;
use Bitrix\Crm\Service\WebForm\DefaultValue\LeadPhoneRule;
use Bitrix\Crm\Service\WebForm\DefaultValue\Rule;

class DefaultValueProvider
{
	/** @var Rule[] */
	private array $ruleMap = [];

	public function __construct()
	{
		$this->registerRules([
			new ContactPhoneRule(),
			new LeadPhoneRule(),
			new CompanyPhoneRule(),
		]);
	}

	/**
	 * @param Rule[] $rules
	 */
	private function registerRules(array $rules): void
	{
		foreach ($rules as $rule)
		{
			if ($rule instanceof Rule)
			{
				$this->ruleMap[$rule->getTargetFieldType()] = $rule;
			}
		}
	}

	public function applyForFields(array $fields): array
	{
		if (empty($fields) || empty($this->ruleMap))
		{
			return [];
		}

		foreach ($fields as $key => $field)
		{
			$fieldType = $field['CODE'] ?? $field['id'] ?? null;
			if ($fieldType === null)
			{
				continue;
			}

			if (isset($this->ruleMap[$fieldType]))
			{
				$rule = $this->ruleMap[$fieldType];
				if ($rule->isApplicable($field))
				{
					$fields[$key][$rule->getValueKey()] = $rule->getValue($field);
				}
			}
		}

		return $fields;
	}
}
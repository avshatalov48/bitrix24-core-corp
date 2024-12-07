<?php declare(strict_types=1);

namespace Bitrix\AI\Services;

use Bitrix\AI\Enum\RuleName;
use Bitrix\AI\Model\EO_PromptDisplayRule_Collection;
use Bitrix\AI\Model\EO_RoleDisplayRule_Collection;
use Bitrix\AI\Model\EO_PromptDisplayRule;
use Bitrix\AI\Model\EO_RoleDisplayRule;

class AvailableRuleService
{
	/**
	 * @param EO_PromptDisplayRule_Collection|EO_RoleDisplayRule_Collection $rules
	 * @param string $userLang
	 * @return bool
	 */
	public function isAvailableRules(mixed $rules, string $userLang): bool
	{
		if (!$this->isRuleCollectionType($rules))
		{
			return true;
		}

		if (self::isEmptyRulesList($rules))
		{
			return true;
		}

		$isAvailable = false;
		$hasRuleWithForCheck = false;
		foreach ($rules as $rule)
		{
			if (self::getNameForRule($rule) !== RuleName::Lang->value)
			{
				continue;
			}

			$hasRuleWithForCheck = true;
			if (self::isCheckInvertForRule($rule))
			{
				if (self::getValueForRule($rule) === $userLang)
				{
					return false;
				}

				$isAvailable = true;
				continue;
			}

			if (self::getValueForRule($rule) === $userLang)
			{
				$isAvailable = true;
			}
		}

		if (!$hasRuleWithForCheck)
		{
			return true;
		}

		return $isAvailable;
	}

	/**
	 * @param EO_PromptDisplayRule_Collection|EO_RoleDisplayRule_Collection $rules
	 * @return bool
	 */
	private function isEmptyRulesList(mixed $rules): bool
	{
		if ($this->isRuleCollectionType($rules))
		{
			return $rules->isEmpty();
		}

		return empty($rules);
	}

	/**
	 * @param EO_PromptDisplayRule|EO_RoleDisplayRule $rule
	 * @return string
	 */
	private function getNameForRule(mixed $rule): string
	{
		if ($this->isRuleType($rule))
		{
			return $rule->getName();
		}

		return '';
	}

	/**
	 * @param EO_PromptDisplayRule|EO_RoleDisplayRule $rule
	 * @return string
	 */
	private function getValueForRule(mixed $rule): string
	{
		if ($this->isRuleType($rule))
		{
			return $rule->getValue();
		}

		return '';
	}

	/**
	 * @param EO_RoleDisplayRule|EO_PromptDisplayRule $rule
	 * @return bool
	 */
	private function isCheckInvertForRule(mixed $rule): bool
	{
		if ($this->isRuleType($rule))
		{
			return  $rule->getIsCheckInvert();
		}

		return false;
	}

	private function isRuleType(mixed $rule): bool
	{
		return $rule instanceof EO_RoleDisplayRule || $rule instanceof EO_PromptDisplayRule;
	}

	private function isRuleCollectionType(mixed $rules): bool
	{
		return $rules instanceof EO_PromptDisplayRule_Collection || $rules instanceof EO_RoleDisplayRule_Collection;
	}
}

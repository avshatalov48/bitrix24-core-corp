<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service;

use Bitrix\AI\Enum\RuleName;
use Bitrix\AI\SharePrompt\Repository\PromptDisplayRuleRepository;
use Bitrix\AI\Synchronization\Dto\RuleDto;

class PromptDisplayRuleService
{
	public function __construct(
		protected PromptDisplayRuleRepository $promptDisplayRuleRepository
	)
	{
	}

	/**
	 * @param int $promptId
	 * @param RuleDto[] $rules
	 * @param bool $needDeleteOld
	 * @return void
	 */
	public function updateRulesForPrompt(int $promptId, array $rules, bool $needDeleteOld = false): void
	{
		if ($needDeleteOld)
		{
			$this->promptDisplayRuleRepository->deleteByPromptId($promptId);
		}

		if (empty($rules))
		{
			return;
		}

		$this->promptDisplayRuleRepository
			->addRulesForPrompt(
				$promptId,
				array_filter($rules, fn($rule) => $rule->getRuleName() == RuleName::Lang)
			)
		;
	}

	/**
	 * @param int[] $promptsIds
	 * @return int[]
	 */
	public function getForbiddenPrompts(array $promptsIds, string $userLang): array
	{
		if (empty($promptsIds))
		{
			return [];
		}

		$rules = $this->promptDisplayRuleRepository->getRulesForPromptsAndChildren($promptsIds);
		if (empty($rules))
		{
			return [];
		}


		return $this->getForbiddenPromptsFromList(
			$this->getRulesByPrompt($rules),
			$userLang
		);
	}

	/**
	 * @param array $rules
	 * @return RuleDto[]
	 */
	private function getRulesByPrompt(array $rules): array
	{
		$rulesByPrompt = [];
		foreach ($rules as $rule)
		{
			if (
				empty($rule['PROMPT_ID'])
				|| empty($rule['NAME'])
				|| empty($rule['VALUE'])
				|| !isset($rule['IS_CHECK_INVERT'])
			)
			{
				continue;
			}

			$ruleName = RuleName::tryFrom($rule['NAME']);
			if (empty($ruleName))
			{
				continue;
			}

			$promptId = $rule['PROMPT_ID'];

			if (empty($rulesByPrompt[$promptId]))
			{
				$rulesByPrompt[$promptId] = [];
			}

			$rulesByPrompt[$promptId][] = new RuleDto(
				(bool)$rule['IS_CHECK_INVERT'],
				$ruleName,
				$rule['VALUE']
			);
		}

		return $rulesByPrompt;
	}

	protected function getForbiddenPromptsFromList(array $rulesByPrompt, string $userLang): array
	{
		if (empty($rulesByPrompt))
		{
			return [];
		}

		$promptsForbidden = [];
		foreach ($rulesByPrompt as $promptId => $rules)
		{
			if ($this->isAvailableRules($rules, $userLang))
			{
				continue;
			}

			$promptsForbidden[] = (int)$promptId;
		}

		return array_unique($promptsForbidden);
	}

	/**
	 * @param RuleDto[] $rules
	 * @param $userLang
	 * @return bool
	 */
	protected function isAvailableRules(array $rules, $userLang): bool
	{
		if (empty($rules))
		{
			return true;
		}

		$isAvailable = false;
		$hasRuleWithForCheck = false;
		foreach ($rules as $rule)
		{
			if ($rule->getRuleName() !== RuleName::Lang)
			{
				continue;
			}

			$hasRuleWithForCheck = true;
			if ($rule->isCheckInvert())
			{
				if ($rule->getValue() === $userLang)
				{
					return false;
				}

				$isAvailable = true;
				continue;
			}

			if ($rule->getValue() === $userLang)
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
}

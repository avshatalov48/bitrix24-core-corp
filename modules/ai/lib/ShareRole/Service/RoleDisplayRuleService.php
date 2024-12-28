<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Service;

use Bitrix\AI\Enum\RuleName;
use Bitrix\AI\ShareRole\Repository\RoleDisplayRuleRepository;
use Bitrix\AI\Synchronization\Dto\RuleDto;

class RoleDisplayRuleService
{
	public function __construct(
		protected RoleDisplayRuleRepository $roleDisplayRuleRepository
	)
	{
	}

	/**
	 * @param int $roleId
	 * @param RuleDto[] $rules
	 * @param bool $needDeleteOld
	 * @return void
	 */
	public function updateRulesForRole(int $roleId, array $rules, bool $needDeleteOld = false): void
	{
		if ($needDeleteOld)
		{
			$this->roleDisplayRuleRepository->deleteByRoleId($roleId);
		}

		if (empty($rules))
		{
			return;
		}

		$this->roleDisplayRuleRepository
			->addRulesForRole(
				$roleId,
				array_filter($rules, fn($rule) => $rule->getRuleName() === RuleName::Lang)
			)
		;
	}

	/**
	 * @param int[] $rolesIds
	 * @return int[]
	 */
	public function getForbiddenRoles(array $rolesIds, string $userLang): array
	{
		if (empty($rolesIds))
		{
			return [];
		}

		$rules = $this->roleDisplayRuleRepository->getRulesForRoles($rolesIds);
		if (empty($rules))
		{
			return [];
		}


		return $this->getForbiddenRolesFromList(
			$this->getRulesByRole($rules),
			$userLang
		);
	}

	/**
	 * @param array $rules
	 * @return RuleDto[]
	 */
	private function getRulesByRole(array $rules): array
	{
		$rulesByRole = [];
		foreach ($rules as $rule)
		{
			if (
				empty($rule['ROLE_ID'])
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

			$roleId = $rule['ROLE_ID'];

			if (empty($rulesByRole[$roleId]))
			{
				$rulesByRole[$roleId] = [];
			}

			$rulesByRole[$roleId][] = new RuleDto(
				(bool)$rule['IS_CHECK_INVERT'],
				$ruleName,
				$rule['VALUE']
			);
		}

		return $rulesByRole;
	}

	protected function getForbiddenRolesFromList(array $rulesByRole, string $userLang): array
	{
		if (empty($rulesByRole))
		{
			return [];
		}

		$rolesForbidden = [];
		foreach ($rulesByRole as $roleId => $rules)
		{
			if ($this->isAvailableRules($rules, $userLang))
			{
				continue;
			}

			$rolesForbidden[] = (int)$roleId;
		}

		return array_unique($rolesForbidden);
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
<?php declare(strict_types=1);

namespace Bitrix\AI\Synchronization\Repository;

use Bitrix\AI\Model\RoleDisplayRuleTable;
use Bitrix\AI\Synchronization\Dto\RuleDto;

class RoleDisplayRuleRepository extends BaseDisplayRuleRepository
{
	public function deleteByEntityId(int $id): void
	{
		RoleDisplayRuleTable::deleteByFilter(['=ROLE_ID' => $id]);
	}

	/**
	 * @param int $id
	 * @param RuleDto[] $rules
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function addRulesForEntityId(int $id, array $rules): void
	{
		if (empty($rules))
		{
			return;
		}

		$rows = [];
		foreach ($rules as $rule)
		{
			$rows[] = [
				'ROLE_ID' => $id,
				'NAME' => $rule->getRuleNameString(),
				'IS_CHECK_INVERT' => $rule->getIsCheckInvertInt(),
				'VALUE' => $rule->getValue()
			];
		}

		RoleDisplayRuleTable::addMulti($rows, true);
	}
}

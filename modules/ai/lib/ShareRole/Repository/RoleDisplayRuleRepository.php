<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Enum\RuleName;
use Bitrix\AI\Model\RoleDisplayRuleTable;
use Bitrix\AI\Model\RoleTable;
use Bitrix\AI\Synchronization\Dto\RuleDto;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class RoleDisplayRuleRepository extends BaseRepository
{
	public function deleteByRoleId(int $roleId): void
	{
		RoleDisplayRuleTable::deleteByFilter(['=ROLE_ID' => $roleId]);
	}

	/**
	 * @param int $roleId
	 * @param RuleDto[] $rules
	 * @return void
	 */
	public function addRulesForRole(int $roleId, array $rules): void
	{
		if (empty($rules))
		{
			return;
		}

		$rows = [];
		foreach ($rules as $rule)
		{
			$rows[] = [
				'ROLE_ID' => $roleId,
				'NAME' => $rule->getRuleNameString(),
				'IS_CHECK_INVERT' => $rule->getIsCheckInvertInt(),
				'VALUE' => $rule->getValue(),
			];
		}

		RoleDisplayRuleTable::addMulti($rows, true);
	}

	/**
	 * @param int[] $rolesIds
	 * @return mixed
	 */
	public function getRulesForRoles(array $rolesIds): array
	{
		return RoleDisplayRuleTable::query()
			->setSelect([
				'ROLE_ID',
				'NAME',
				'VALUE',
				'IS_CHECK_INVERT',
			])
			->where('NAME', '=', RuleName::Lang->value)
			->fetchAll()
		;
	}
}
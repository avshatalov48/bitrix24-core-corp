<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Enum\RuleName;
use Bitrix\AI\Model\PromptDisplayRuleTable;
use Bitrix\AI\Model\PromptTable;
use Bitrix\AI\Synchronization\Dto\RuleDto;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

class PromptDisplayRuleRepository extends BaseRepository
{
	public function deleteByPromptId(int $promptId): void
	{
		PromptDisplayRuleTable::deleteByFilter(['=PROMPT_ID' => $promptId]);
	}

	/**
	 * @param int $promptId
	 * @param RuleDto[] $rules
	 * @return void
	 */
	public function addRulesForPrompt(int $promptId, array $rules): void
	{
		if (empty($rules))
		{
			return;
		}

		$rows = [];
		foreach ($rules as $rule)
		{
			$rows[] = [
				'PROMPT_ID' => $promptId,
				'NAME' => $rule->getRuleNameString(),
				'IS_CHECK_INVERT' => $rule->getIsCheckInvertInt(),
				'VALUE' => $rule->getValue()
			];
		}

		PromptDisplayRuleTable::addMulti($rows, true);
	}

	/**
	 * @param int[] $promptsIds
	 * @return mixed
	 */
	public function getRulesForPromptsAndChildren(array $promptsIds): mixed
	{
		return PromptDisplayRuleTable::query()
			->setSelect([
				'PROMPT_ID',
				'NAME',
				'VALUE',
				'IS_CHECK_INVERT',
			])
			->where('NAME', '=', RuleName::Lang->value)
			->where((new ConditionTree())
				->logic(ConditionTree::LOGIC_OR)
				->whereIn('PROMPT_ID', $promptsIds)
				->whereIn(
					'PROMPT_ID',
					PromptTable::query()
						->setSelect(['ID'])
						->whereIn('PARENT_ID', $promptsIds)
				))
			->fetchAll()
		;
	}
}

<?php declare(strict_types=1);

namespace Bitrix\AI\Synchronization\Repository;

use Bitrix\AI\Synchronization\Dto\RuleDto;

abstract class BaseDisplayRuleRepository
{
	abstract public function deleteByEntityId(int $id): void;

	/**
	 * @param int $id
	 * @param RuleDto[] $rules
	 * @return void
	 */
	abstract public function addRulesForEntityId(int $id, array $rules): void;
}

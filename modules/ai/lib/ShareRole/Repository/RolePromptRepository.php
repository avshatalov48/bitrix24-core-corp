<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Repository;

use Bitrix\AI\BaseRepository;
use Bitrix\AI\Model\RolePromptTable;

class RolePromptRepository extends BaseRepository
{

	public function addPromptsToRole(int $roleId, array $promptIds): void
	{
		if (empty($promptIds))
		{
			return;
		}

		$rows = [];
		$uniquePromptIds = array_unique($promptIds);
		foreach ($uniquePromptIds as $promptId)
		{
			$rows[] = [
				'ROLE_ID' => $roleId,
				'PROMPT_ID' => $promptId,
			];
		}
		RolePromptTable::addMulti($rows, true);
	}
}

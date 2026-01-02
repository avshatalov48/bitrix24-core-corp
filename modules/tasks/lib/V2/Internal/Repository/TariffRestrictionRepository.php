<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;

class TariffRestrictionRepository implements TariffRestrictionRepositoryInterface
{
	public function getGanttLinkCount(int $userId): int
	{
		$row = ProjectDependenceTable::query()
			->setSelect([new ExpressionField('CNT', 'COUNT(*)')])
			->where('CREATOR_ID', $userId)
			->where('DIRECT', '1')
			->fetch()
		;

		if (!is_array($row))
		{
			return 0;
		}

		return (int)($row['CNT'] ?? 0);
	}
}

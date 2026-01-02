<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper\Task\Gantt;

use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\Gantt\LinkType;

class LinkTypeMapper
{
	public function mapFromEnum(?LinkType $linkType): int
	{
		return match($linkType)
		{
			LinkType::StartStart => ProjectDependenceTable::LINK_TYPE_START_START,
			LinkType::StartFinish => ProjectDependenceTable::LINK_TYPE_START_FINISH,
			LinkType::FinishFinish => ProjectDependenceTable::LINK_TYPE_FINISH_FINISH,
			default => ProjectDependenceTable::LINK_TYPE_FINISH_START
		};
	}

	public function mapToEnum(int $linkType): LinkType
	{
		return match($linkType)
		{
			ProjectDependenceTable::LINK_TYPE_START_START => LinkType::StartStart,
			ProjectDependenceTable::LINK_TYPE_START_FINISH => LinkType::StartFinish,
			ProjectDependenceTable::LINK_TYPE_FINISH_FINISH => LinkType::FinishFinish,
			default => LinkType::FinishStart,
		};
	}
}

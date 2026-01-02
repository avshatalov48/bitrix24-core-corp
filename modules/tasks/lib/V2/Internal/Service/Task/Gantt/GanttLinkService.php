<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Gantt;

use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\V2\Internal\Entity\Task\GanttLink;
use Bitrix\Tasks\V2\Internal\Exception\Task\TreeLinkException;
use Bitrix\Tasks\V2\Internal\Repository\GanttLinkRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\Task\Gantt\LinkTypeMapper;

class GanttLinkService
{
	public function __construct(
		private readonly GanttLinkRepositoryInterface $ganttLinkRepository,
		private readonly LinkTypeMapper $linkTypeMapper,
	)
	{

	}

	public function create(GanttLink $ganttLink): void
	{
		$result = ProjectDependenceTable::createLink($ganttLink->taskId, $ganttLink->dependentId, [
			'LINK_TYPE' => $this->linkTypeMapper->mapFromEnum($ganttLink->type),
			'CREATOR_ID' => $ganttLink->creatorId,
		]);

		if (!$result->isSuccess())
		{
			throw new TreeLinkException($result->getError()?->getMessage());
		}
	}

	public function update(GanttLink $ganttLink): void
	{
		$this->ganttLinkRepository->update($ganttLink);
	}

	public function delete(GanttLink $ganttLink): void
	{
		$result = ProjectDependenceTable::deleteLink($ganttLink->taskId, $ganttLink->dependentId);

		if (!$result->isSuccess())
		{
			throw new TreeLinkException($result->getError()?->getMessage());
		}
	}
}

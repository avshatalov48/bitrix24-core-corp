<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Main\Loader;
use CSearch;

class DeleteGlobalSearchIndex
{
	public function __invoke(array $fullTaskData): void
	{
		if (Loader::includeModule("search"))
		{
			CSearch::DeleteIndex("tasks", $fullTaskData['ID']);
		}
	}
}
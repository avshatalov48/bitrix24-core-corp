<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Task\FavoriteTable;

class DeleteFavorite
{
	public function __invoke(array $fullTaskData): void
	{
		FavoriteTable::deleteByTaskId($fullTaskData['ID'], ['LOW_LEVEL' => true]);
	}
}
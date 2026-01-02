<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\Internals\Task\Template\TemplateDependenceTable;

class DeleteTemplateDependencies
{
	public function __invoke(array $fullTaskData): void
	{
		TemplateDependenceTable::deleteList(['=DEPENDS_ON_ID' => $fullTaskData['ID']]);
	}
}
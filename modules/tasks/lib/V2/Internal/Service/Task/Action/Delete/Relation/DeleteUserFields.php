<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\V2\Internal\Service\Task\Trait\UserFieldTrait;
use Bitrix\Tasks\Util\UserField\Task;

class DeleteUserFields
{
	use UserFieldTrait;

	public function __invoke(array $fullTaskData): void
	{
		$this->getUfManager()->Delete(Task::getEntityCode(), $fullTaskData['ID']);
	}
}
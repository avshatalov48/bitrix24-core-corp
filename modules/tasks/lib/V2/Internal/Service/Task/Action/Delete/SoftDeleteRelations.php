<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteChildrenDependencies;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteDependencies;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteGlobalSearchIndex;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteMembers;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteProjectDependencies;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteReminders;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\HideLivefeedLogs;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\RecalculateTree;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\SocialNetwork\Log;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Internals\Task\RegularParametersTable;
use Bitrix\Tasks\Processor\Task\Scheduler;

class SoftDeleteRelations
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		(new DeleteMembers())($fullTaskData);

		(new DeleteDependencies())($fullTaskData);

		(new DeleteReminders())($fullTaskData);

		(new DeleteProjectDependencies())($fullTaskData);

		(new DeleteChildrenDependencies())($fullTaskData);

		(new RecalculateTree($this->config))($fullTaskData);

		(new DeleteGlobalSearchIndex())($fullTaskData);

		(new HideLivefeedLogs())($fullTaskData);
	}
}
<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;

class SyncHandler
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		if ($this->config->isSkipExchangeSync())
		{
			return;
		}

		\CTaskSync::DeleteItem($fullTaskData);
	}
}
<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Option\Action\Mute;

use Bitrix\Tasks\Internals\Counter\CounterService;
use Bitrix\Tasks\V2\Internal\Entity;

class CollectCounter
{
	public function __invoke(Entity\Task\UserOption $userOption): void
	{
		CounterService::getInstance()->collectData($userOption->taskId);
	}
}
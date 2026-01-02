<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Integration;

use Bitrix\Im\V2\Service\Locator;
use Bitrix\Im\V2\Service\Messenger;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;

class RunMessenger
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		if (!Loader::includeModule('im'))
		{
			return;
		}

		$isMovedToRecyclebin = $this->config->getRuntime()->isMovedToRecyclebin();

		Application::getInstance()->addBackgroundJob(
			function() use ($fullTaskData, $isMovedToRecyclebin) {
				Locator::getMessenger()->unregisterTask($fullTaskData, $isMovedToRecyclebin);
			}
		);
	}
}
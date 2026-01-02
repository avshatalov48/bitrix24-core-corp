<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Integration\RunCrm;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Integration\RunMessenger;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;

class RunIntegration
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		(new RunCrm($this->config))($fullTaskData);

		(new RunMessenger($this->config))($fullTaskData);
	}
}
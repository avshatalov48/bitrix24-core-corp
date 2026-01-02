<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Integration;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;

class RunCrm
{
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		(new TimeLineManager($fields['ID'], $this->config->getUserId()))
			->onTaskCreated()
			->save();
	}
}
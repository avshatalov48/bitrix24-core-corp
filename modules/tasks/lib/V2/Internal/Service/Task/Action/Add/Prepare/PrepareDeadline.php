<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\Deadline\Control\Task\Field\DeadlineFieldHandler;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareDeadline implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$handler = new DeadlineFieldHandler($this->config->getUserId());
		$handler->modify($fields);

		return $fields;
	}
}
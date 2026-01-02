<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ApplicationErrorTrait;

class RunBeforeAddEvent
{
	use ConfigTrait;
	use ApplicationErrorTrait;

	/**
	 * @throws TaskAddException
	 */
	public function __invoke(array $fields): array
	{
		$fields = array_merge($fields, $this->config->getByPassParameters());

		foreach (GetModuleEvents('tasks', 'OnBeforeTaskAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, [&$fields]) !== false)
			{
				continue;
			}

			$message = $this->getAdminApplicationError();

			throw new TaskAddException($message);
		}

		return $fields;
	}
}
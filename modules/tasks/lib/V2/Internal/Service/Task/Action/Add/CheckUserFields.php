<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\UserFieldTrait;

class CheckUserFields
{
	use UserFieldTrait;
	use ConfigTrait;

	/**
	 * @throws TaskAddException
	 */
	public function __invoke(array $fields): void
	{
		if (!$this->checkContainsUfKeys($fields))
		{
			return;
		}

		if (!$this->checkFields(0, $fields, $this->config->getUserId()))
		{
			$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
			throw new TaskAddException($message);
		}
	}
}
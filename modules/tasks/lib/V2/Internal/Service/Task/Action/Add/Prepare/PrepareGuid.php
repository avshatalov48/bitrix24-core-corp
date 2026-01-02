<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util;

class PrepareGuid implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$fields['GUID'] = Util::generateUUID();

		$task = TaskTable::query()
			->setSelect(['ID'])
			->where('GUID', $fields['GUID'])
			->setLimit(1)
			->exec()
			->fetch();

		if (!$task)
		{
			return $fields;
		}

		throw new TaskFieldValidateException(Loc::getMessage('ERROR_TASKS_GUID_NON_UNIQUE'));
	}
}
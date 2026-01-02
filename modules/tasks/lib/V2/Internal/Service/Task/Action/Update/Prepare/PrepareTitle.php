<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;

class PrepareTitle implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (!isset($fields['TITLE']))
		{
			return $fields;
		}

		$title = trim((string)$fields['TITLE']);
		if ($title === '')
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_TITLE'));
		}

		// we can break emoji here, but that's the price
		$title = mb_substr(Emoji::encode($title), 0, 250);
		$fields['TITLE'] = $title;

		return $fields;
	}
}
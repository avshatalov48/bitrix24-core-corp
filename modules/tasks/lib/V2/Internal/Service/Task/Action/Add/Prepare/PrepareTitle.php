<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareTitle implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$title = (string)($fields['TITLE'] ?? '');
		$title = trim($title);
		if ($title === '')
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_TITLE'));
		}

		$fields['TITLE'] = Emoji::encode(mb_substr($title, 0, 250));

		return $fields;
	}
}

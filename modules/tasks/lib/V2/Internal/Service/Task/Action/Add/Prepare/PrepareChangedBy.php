<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

class PrepareChangedBy implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$nowDateTimeString = UI::formatDateTime(User::getTime());

		if (!isset($fields['ACTIVITY_DATE']))
		{
			$fields['ACTIVITY_DATE'] = $nowDateTimeString;
		}

		if (isset($fields['CHANGED_BY']))
		{
			return $fields;
		}

		$fields['CHANGED_BY'] = $fields['CREATED_BY'];

		if (!isset($fields['CHANGED_DATE']))
		{
			$fields['CHANGED_DATE'] = $nowDateTimeString;
		}

		return $fields;
	}
}
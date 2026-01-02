<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\User;

class PrepareChangedBy implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (!isset($fields['CHANGED_BY']))
		{
			$fields['CHANGED_BY'] = $this->config->getUserId();
		}
		if (!isset($fields['CHANGED_DATE']))
		{
			$fields['CHANGED_DATE'] = UI::formatDateTime(User::getTime());
		}

		return $fields;
	}
}
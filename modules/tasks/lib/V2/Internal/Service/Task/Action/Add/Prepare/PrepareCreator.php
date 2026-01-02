<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareCreator implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$creatorId = (int)($fields['CREATED_BY'] ?? 0);
		if ($creatorId <= 0)
		{
			trigger_error('Passing empty creator is deprecated', E_USER_WARNING);
			$fields['CREATED_BY'] = $this->config->getUserId();
		}

		return $fields;
	}
}
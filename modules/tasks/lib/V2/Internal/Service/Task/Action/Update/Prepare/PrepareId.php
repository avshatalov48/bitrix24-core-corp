<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;

class PrepareId implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		unset($fields['ID']);

		return $fields;
	}
}
<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;

class PrepareOutlook implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		$currentVersion = (int)($fullTaskData['OUTLOOK_VERSION'] ?? 1);
		$fields['OUTLOOK_VERSION'] = $currentVersion + 1;

		return $fields;

	}
}
<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareOutlook implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$fields['OUTLOOK_VERSION'] = 1;

		return $fields;
	}
}
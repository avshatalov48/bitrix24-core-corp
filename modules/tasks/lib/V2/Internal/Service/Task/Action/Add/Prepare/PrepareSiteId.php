<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareSiteId implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if (!isset($fields['SITE_ID']))
		{
			$fields['SITE_ID'] = SITE_ID;
		}

		return $fields;
	}
}
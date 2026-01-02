<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareDescription implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if (isset($fields['DESCRIPTION']))
		{
			$fields['DESCRIPTION'] = Emoji::encode(trim((string)$fields['DESCRIPTION']));
		}

		return $fields;
	}
}
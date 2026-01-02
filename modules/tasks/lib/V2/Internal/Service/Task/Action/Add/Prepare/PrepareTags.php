<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareTags implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if (!isset($fields['TAGS']))
		{
			$fields['TAGS'] = [];

			return $fields;
		}

		if (is_array($fields['TAGS']))
		{
			return $fields;
		}

		if (is_string($fields['TAGS']))
		{
			$fields['TAGS'] = explode(',', $fields['TAGS']);

			return $fields;
		}

		if (is_numeric($fields['TAGS']))
		{
			$fields['TAGS'] = [$fields['TAGS']];

			return $fields;
		}

		unset($fields['TAGS']);

		return $fields;
	}
}
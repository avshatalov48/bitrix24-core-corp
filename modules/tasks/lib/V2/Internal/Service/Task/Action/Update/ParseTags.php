<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParseTextTrait;

class ParseTags
{
	use ConfigTrait;
	use ParseTextTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (!array_key_exists('TAGS', $fields))
		{
			$fields['TAGS'] = $fullTaskData['TAGS'];
		}
		if (!array_key_exists('TITLE', $fields))
		{
			$fields['TITLE'] = $fullTaskData['TITLE'];
		}
		if (!array_key_exists('DESCRIPTION', $fields))
		{
			$fields['DESCRIPTION'] = $fullTaskData['DESCRIPTION'];
		}

		$fields['TAGS'] = $this->parseTags($fields);

		return $fields;
	}
}
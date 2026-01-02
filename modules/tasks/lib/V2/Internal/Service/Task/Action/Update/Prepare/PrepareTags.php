<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParseTextTrait;

class PrepareTags implements PrepareFieldInterface
{
	use ParseTextTrait;
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (!isset($fields['TAGS']))
		{
			return $fields;
		}

		$fields = $this->castTags($fields);

		if (!isset($fields['TAGS']))
		{
			$fields['TAGS'] = $fullTaskData['TAGS'];
		}
		if (!isset($fields['TITLE']))
		{
			$fields['TITLE'] = $fullTaskData['TITLE'];
		}
		if (!isset($fields['DESCRIPTION']))
		{
			$fields['DESCRIPTION'] = $fullTaskData['DESCRIPTION'];
		}

		$fields['TAGS'] = $this->parseTags($fields);


		return $fields;
	}

	private function castTags(array $fields): array
	{
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
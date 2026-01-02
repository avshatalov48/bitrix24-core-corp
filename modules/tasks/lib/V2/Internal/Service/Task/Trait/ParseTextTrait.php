<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

trait ParseTextTrait
{
	private function parseTags(array $fields): array
	{
		$tags = [];
		$searchFields = ['TITLE', 'DESCRIPTION'];

		foreach ($searchFields as $code)
		{
			if (!array_key_exists($code, $fields))
			{
				continue;
			}
			if (preg_match_all('/\s#([^\s,\[\]<>]+)/is', ' ' . $fields[$code], $matches))
			{
				$tags[] = $matches[1];
			}
		}

		$tags = array_merge([], ...$tags);
		if (
			array_key_exists('TAGS', $fields)
			&& !empty($fields['TAGS'])
		)
		{
			$tags = array_merge($fields['TAGS'], $tags);
		}

		return array_unique($tags);
	}
}
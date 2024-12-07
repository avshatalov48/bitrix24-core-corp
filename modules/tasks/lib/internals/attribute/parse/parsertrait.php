<?php

namespace Bitrix\Tasks\Internals\Attribute\Parse;

use Bitrix\Main\Access\AccessCode;

trait ParserTrait
{
	private function parseValue(mixed $value, string $type): array
	{
		if (is_string($value))
		{
			return [$this->parseInternal($value, $type)];
		}

		if (is_countable($value))
		{
			$parsed = [];
			foreach ($value as $item)
			{
				if (!is_string($item))
				{
					continue;
				}

				$parsedValue = $this->parseInternal($item, $type);
				if ($parsedValue <= 0)
				{
					continue;
				}

				$parsed[] = $parsedValue;
			}

			return $parsed;
		}

		return [];
	}

	private function parseInternal(string $accessCode, string $type): int
	{
		if (!AccessCode::isValid($accessCode))
		{
			return 0;
		}

		$access = new AccessCode($accessCode);

		if ($access->getEntityType() !== $type)
		{
			return 0;
		}

		return $access->getEntityId();
	}
}
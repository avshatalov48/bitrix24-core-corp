<?php

namespace Bitrix\Crm\Decorator\JsonSerializable;

use JsonSerializable;

final class ClearNullValues implements JsonSerializable
{
	public function __construct(
		private readonly JsonSerializable $jsonSerializable,
	)
	{
	}

	public function jsonSerialize(): mixed
	{
		$values = $this->jsonSerializable->jsonSerialize();
		if (is_array($values))
		{
			$this->clearNullRecursive($values);
		}

		return $values;
	}

	private function clearNullRecursive(array &$values): void
	{
		$isNeedReindex = array_is_list($values);

		foreach ($values as $key => &$value)
		{
			if (is_array($value))
			{
				$this->clearNullRecursive($value);

				continue;
			}

			if ($value === null)
			{
				unset($values[$key]);
			}
		}
		unset($value);

		if ($isNeedReindex)
		{
			$values = array_values($values);
		}
	}

	/**
	 * @param JsonSerializable[] $items
	 * @return self[]
	 */
	public static function decorateList(array $items): array
	{
		return array_map(static fn ($item) => new self($item), $items);
	}
}

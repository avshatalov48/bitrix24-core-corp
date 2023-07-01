<?php

namespace Bitrix\Tasks\Internals\Task\Placeholder\Placeholder;

use Bitrix\Tasks\Internals\Task\Placeholder\Exception\PlaceholderValidationException;

class CrmTestArrayPlaceholder extends Placeholder
{
	public function toString(): string
	{
		return implode(', ', $this->value);
	}

	protected function validate(): bool
	{
		if (!is_array($this->value))
		{
			throw new PlaceholderValidationException(static::class, 'not an array');
		}

		foreach ($this->value as $item)
		{
			if (!is_string($item))
			{
				throw new PlaceholderValidationException(static::class, 'contains an array');
			}
		}

		return true;
	}
}
<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Models;

enum GigaChatModel: string
{
	case Lite = 'GigaChat-2';
	case Pro = 'GigaChat-2-Pro';
	case Max = 'GigaChat-2-Max';

	/**
	 * Returns the maximum number of tokens that can be processed in one request.
	 * @return int
	 */
	public function contextLimit(): int
	{
		return match ($this) {
			self::Lite, self::Pro, self::Max => 131072,
		};
	}
}

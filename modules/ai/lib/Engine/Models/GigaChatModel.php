<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Models;

enum GigaChatModel: string
{
	case Lite = 'GigaChat';
	case Plus = 'GigaChat-Plus';
	case Pro = 'GigaChat-Pro';

	/**
	 * Returns the maximum number of tokens that can be processed in one request.
	 * @return int
	 */
	public function contextLimit(): int
	{
		return match ($this) {
			self::Lite, self::Pro => 8192,
			self::Plus => 32768,
		};
	}
}

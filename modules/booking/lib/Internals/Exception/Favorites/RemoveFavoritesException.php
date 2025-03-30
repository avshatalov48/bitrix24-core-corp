<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Favorites;

use Bitrix\Booking\Internals\Exception\Exception;

class RemoveFavoritesException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed removing favorite resource' : $message;
		$code = self::CODE_FAVORITE_REMOVE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

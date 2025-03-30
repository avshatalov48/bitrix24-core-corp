<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Favorites;

use Bitrix\Booking\Internals\Exception\Exception;

class CreateFavoritesException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed creating new favorite resource' : $message;
		$code = self::CODE_FAVORITE_CREATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

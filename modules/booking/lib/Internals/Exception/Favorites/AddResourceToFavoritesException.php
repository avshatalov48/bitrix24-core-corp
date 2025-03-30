<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Favorites;

use Bitrix\Booking\Internals\Exception\Exception;

class AddResourceToFavoritesException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed add resource to favorites list' : $message;
		$code = self::CODE_ADD_RESOURCE_TO_FAVORITES_LIST;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

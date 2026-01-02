<?php

namespace Bitrix\Booking\Internals\Exception\WaitListItem;

use Bitrix\Booking\Internals\Exception\Exception;

class UpdateWaitListItemException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed updating wait list item' : $message;
		$code = self::CODE_WAIT_LIST_ITEM_UPDATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

<?php

namespace Bitrix\Booking\Internals\Exception\WaitListItem;

use Bitrix\Booking\Internals\Exception\Exception;

class RemoveWaitListItemException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed removing wait list item' : $message;
		$code = self::CODE_WAIT_LIST_ITEM_REMOVE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

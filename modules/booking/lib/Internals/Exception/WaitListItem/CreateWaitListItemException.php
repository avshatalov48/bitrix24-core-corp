<?php

namespace Bitrix\Booking\Internals\Exception\WaitListItem;

use Bitrix\Booking\Internals\Exception\Exception;

class CreateWaitListItemException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed creating new wait list item' : $message;
		$code = self::CODE_WAIT_LIST_ITEM_CREATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception;

class JournalAppendException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed appending to the journal' : $message;
		$code = self::CODE_JOURNAL_APPEND;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

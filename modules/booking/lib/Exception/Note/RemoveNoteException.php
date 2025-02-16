<?php

declare(strict_types=1);

namespace Bitrix\Booking\Exception\Note;

use Bitrix\Booking\Exception\Exception;

class RemoveNoteException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed removing note' : $message;
		$code = self::CODE_NOTE_REMOVE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

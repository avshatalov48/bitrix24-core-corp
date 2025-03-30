<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Exception\Note;

use Bitrix\Booking\Internals\Exception\Exception;

class CreateNoteException extends Exception
{
	public function __construct($message = '')
	{
		$message = $message === '' ? 'Failed creating new note' : $message;
		$code = self::CODE_NOTE_CREATE;

		parent::__construct(
			message: $message,
			code: $code,
		);
	}
}

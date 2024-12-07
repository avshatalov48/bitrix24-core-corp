<?php

namespace Bitrix\StaffTrack\Internals\Exception;

use Bitrix\Main\SystemException;

class IntranetUserException extends SystemException
{
	public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct(($message ?: 'Intranet user only'), 403, '', 0, $previous);
	}
}
<?php

namespace Bitrix\StaffTrack\Internals\Exception;

use Bitrix\Main\SystemException;

class UserNotFoundException extends SystemException
{
	public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct(($message ?: 'User not found'), 404, '', 0, $previous);
	}
}
<?php

namespace Bitrix\Tasks\Integration\Rest\Exception;

use Bitrix\Main\Loader;
use Exception;

class RestException
{
	public static function getException(string $message = ''): Exception
	{
		if (Loader::includeModule('rest'))
		{
			return new \Bitrix\Rest\RestException($message);
		}

		return new Exception($message);
	}
}

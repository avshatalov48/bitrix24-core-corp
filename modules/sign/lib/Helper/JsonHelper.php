<?php

namespace Bitrix\Sign\Helper;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;
use Closure;

class JsonHelper
{
	public static function encodeOrDefault(string $default, mixed... $data): string
	{
		try
		{
			return Json::encode(...$data);
		}
		catch (ArgumentException $e)
		{
			return $default;
		}
	}
}
<?php
namespace Bitrix\Timeman\Monitor\Utils;

class User
{
	public static function getCurrentUserId(): ?int
	{
		global $USER;

		return $USER->GetID();
	}
}


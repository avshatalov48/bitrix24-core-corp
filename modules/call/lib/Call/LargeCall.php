<?php

namespace Bitrix\Call\Call;

use Bitrix\Main\Config\Option;

class LargeCall extends BitrixCall
{
	public function getMaxUsers(): int
	{
		return (int)Option::get('call', 'call_server_large_room', parent::getMaxUsers());
	}
}
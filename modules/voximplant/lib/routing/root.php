<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Voximplant\Call;

class Root extends Node
{
	public function getFirstAction(Call $call)
	{
		return false;
	}

	public function getNextAction(Call $call, array $request = [])
	{
		return false;
	}
}
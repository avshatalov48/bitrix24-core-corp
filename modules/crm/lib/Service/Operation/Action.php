<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Item;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

abstract class Action
{
	public function __construct()
	{
		Loc::loadMessages(__FILE__);
	}

	abstract public function process(Item $item): Result;
}
<?php

namespace Bitrix\HumanResources\Contract;

use Bitrix\Main\Result;

interface Operation
{
	public function launch(): Result;
}
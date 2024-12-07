<?php

namespace Bitrix\Sign\Contract;

use Bitrix\Main;

interface Operation
{
	public function launch(): Main\Result;
}
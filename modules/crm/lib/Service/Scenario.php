<?php

namespace Bitrix\Crm\Service;

use Bitrix\Main\Result;

abstract class Scenario
{
	abstract public function play(): Result;
}

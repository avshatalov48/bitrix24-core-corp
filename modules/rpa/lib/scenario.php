<?php

namespace Bitrix\Rpa;

use Bitrix\Main\Result;

abstract class Scenario
{
	abstract public function play(): Result;
}
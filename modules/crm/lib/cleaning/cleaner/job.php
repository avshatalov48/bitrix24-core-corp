<?php

namespace Bitrix\Crm\Cleaning\Cleaner;

use Bitrix\Main\Result;

abstract class Job
{
	abstract public function run(Options $options): Result;
}

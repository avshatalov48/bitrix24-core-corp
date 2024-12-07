<?php

namespace Bitrix\BIConnector\Superset\Updater\Versions;

use Bitrix\Main\Result;

abstract class BaseVersion
{
	abstract public function run(): Result;
}

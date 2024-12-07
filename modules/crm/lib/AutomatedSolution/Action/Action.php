<?php

namespace Bitrix\Crm\AutomatedSolution\Action;

use Bitrix\Main\Result;

interface Action
{
	public function execute(): Result;
}

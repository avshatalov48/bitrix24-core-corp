<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Contract;

use Bitrix\Main\Result;

interface Command
{
	public function execute(): Result;
}
<?php

declare(strict_types=1);

namespace Bitrix\Baas\Repository\Result;

use Bitrix\Main;

class BalanceResult extends Main\Result
{
	public function __construct(
		public readonly int $currentValue,
	)
	{
		parent::__construct();
	}
}

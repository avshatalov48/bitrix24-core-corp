<?php

namespace Bitrix\Sign\Operation\Result;

use Bitrix\Main\Result;

class ConfigureProgressResult extends Result
{
	public function __construct(
		public readonly bool $completed = false,
		public readonly float $progress = 0,
	)
	{
		parent::__construct();
	}
}
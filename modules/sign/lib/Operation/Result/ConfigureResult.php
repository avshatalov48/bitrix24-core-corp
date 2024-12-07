<?php

namespace Bitrix\Sign\Operation\Result;

use Bitrix\Main\Result;

class ConfigureResult extends Result
{
	public function __construct(
		public readonly bool $completed = false,
	)
	{
		parent::__construct();
	}
}
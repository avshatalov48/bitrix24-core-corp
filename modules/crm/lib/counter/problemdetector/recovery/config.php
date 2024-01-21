<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;

class Config
{
	use Singleton;

	private const DEFAULT_FIX_LIMIT = 10;

	private int $limit;


	public function __construct(?int $limit = null)
	{
		if ($limit === null)
		{
			$this->limit = (int)Option::get('crm', 'CounterProblemDetectorRecoveryLimit', self::DEFAULT_FIX_LIMIT);
		}
		else
		{
			$this->limit = $limit;
		}
	}

	public function getLimit(): int
	{
		return $this->limit;
	}
}
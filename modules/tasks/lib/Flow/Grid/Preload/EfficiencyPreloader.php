<?php

namespace Bitrix\Tasks\Flow\Grid\Preload;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Efficiency\Efficiency;
use Bitrix\Tasks\Flow\Efficiency\LastMonth;
use Bitrix\Tasks\Internals\Log\Logger;

class EfficiencyPreloader
{
	private Efficiency $efficiency;

	public function __construct()
	{
		$this->init();
	}

	final public function preload(int ...$flowIds): void
	{
		try
		{
			$this->efficiency->load(...$flowIds);
		}
		catch (SystemException $e)
		{
			Logger::logThrowable($e);
		}
	}

	private function init(): void
	{
		$this->efficiency = new Efficiency(new LastMonth());
	}
}
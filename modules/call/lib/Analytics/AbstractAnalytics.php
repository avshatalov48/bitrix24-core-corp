<?php

namespace Bitrix\Call\Analytics;

use Bitrix\Im\Call\Call;
use Bitrix\Main\Application;

abstract class AbstractAnalytics
{
	protected Call $call;

	public function __construct(Call $call)
	{
		$this->call = $call;
	}

	protected function async(callable $job): void
	{
		Application::getInstance()->addBackgroundJob($job);
	}
}

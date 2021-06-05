<?php

namespace Bitrix\Crm\Service\Scenario;

use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Scenario;
use Bitrix\Main\Result;

class PurgeStagesCache extends Scenario
{
	protected $factory;

	public function __construct(Factory $factory)
	{
		$this->factory = $factory;
	}

	public function play(): Result
	{
		$this->factory->purgeStagesCache();

		return new Result();
	}
}
<?php

namespace Bitrix\Crm\Service\WebForm\Scenario;

use Bitrix\Crm\Service\WebForm\WebFormScenarioBuilder;

abstract class BaseBuilder implements WebFormScenarioBuilder
{
	/**
	 * @var array
	 */
	protected $prepared = [];

	public function prepare(array &$options): array
	{
		foreach ($this->prepared['data'] as $key => $prepared)
		{
			if (isset($this->prepared[$key]))
			{
				$this->prepared[$key] += $prepared;
				continue;
			}
			$this->prepared[$key] = $prepared;
		}

		return $this->prepared;
	}
}
<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference\Contract;

use Bitrix\Crm\Integration\AI\ConfigurationDifference\Difference;
use Bitrix\Crm\Integration\AI\ConfigurationDifference\DifferenceItemCollection;

interface DifferenceCalculator
{
	public function calculate(ConfigurationProvider $provider): Difference;
}

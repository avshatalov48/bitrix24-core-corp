<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference\Contract;

use Bitrix\Crm\Integration\AI\ConfigurationDifference\DifferenceItemCollection;

interface ConfigurationProvider
{
	public function name(): string;

	public function default(): DifferenceItemCollection;

	public function actual(): DifferenceItemCollection;

	public function fields(): array;
}

<?php

namespace Bitrix\Tasks\Integration\AI\Restriction;

use Bitrix\Main;
use Bitrix\Tasks\Integration\AI\Restriction;

class Text extends Restriction
{
	public function isChecklistAvailable(): bool
	{
		return $this->engineAvailable;
	}

	protected function getType(): string
	{
		return 'text';
	}
}
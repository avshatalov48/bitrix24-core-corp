<?php

namespace Bitrix\Tasks\Integration\AI\Restriction;

use Bitrix\Main;
use Bitrix\Tasks\Integration\AI\Restriction;

class Text extends Restriction
{
	public function isChecklistAvailable(): bool
	{
		if (!$this->engineAvailable)
		{
			return false;
		}

		return Main\Config\Option::get('main', 'bitrix:main.post.form:Copilot', 'N') === 'Y';
	}

	protected function getType(): string
	{
		return 'text';
	}
}
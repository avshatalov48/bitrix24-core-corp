<?php

namespace Bitrix\Tasks\Integration\AI\Restriction;

use Bitrix\Tasks\Integration\AI\Restriction;

class Text extends Restriction
{
	protected function getType(): string
	{
		return 'text';
	}
}
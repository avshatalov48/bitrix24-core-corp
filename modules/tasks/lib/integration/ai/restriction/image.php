<?php

namespace Bitrix\Tasks\Integration\AI\Restriction;

use Bitrix\Tasks\Integration\AI\Restriction;

class Image extends Restriction
{
	protected function getType(): string
	{
		return 'image';
	}
}
<?php

namespace Bitrix\StaffTrack\Controller\Trait;

use Bitrix\Main\Error;

trait ErrorResponseTrait
{
	protected function buildErrorResponse(?string $message = null): array
	{
		$this->errorCollection->setError(new Error($message ?? 'Unknown error'));

		return [];
	}
}
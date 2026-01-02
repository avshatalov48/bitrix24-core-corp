<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access;

use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Error;

trait AccessUserErrorTrait
{
	private ?Error $userError = null;

	public function getUserError(): ?Error
	{
		return $this->userError;
	}

	private function resolveUserError(AccessibleController $accessController): void
	{
		if (!$accessController instanceof UserErrorInterface)
		{
			return;
		}

		$error = $accessController->getUserErrors()[0] ?? null;
		if (!$error instanceof Error)
		{
			return;
		}

		$this->userError = $error;
	}
}
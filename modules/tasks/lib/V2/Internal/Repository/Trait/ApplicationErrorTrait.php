<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Trait;

trait ApplicationErrorTrait
{
	public function getErrorMessage(): ?string
	{
		global $APPLICATION;

		$error = $APPLICATION->GetException();
		if (!$error)
		{
			return null;
		}

		$message = $error->GetString();
		if (!is_string($message))
		{
			return null;
		}

		return $message;
	}
}
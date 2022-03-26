<?php

namespace Bitrix\Mobile\Helpers;

use Bitrix\Main\Error;

trait ReadsApplicationErrors
{
	/**
	 * @return Error|null
	 */
	private function getLastApplicationError(): ?Error
	{
		$lastError = $GLOBALS['APPLICATION']->LAST_ERROR;
		if ($lastError)
		{
			return new Error(
				(string)$lastError->getString(),
				(string)$lastError->GetID(),
			);
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	private function getLastApplicationErrorText(): ?string
	{
		$error = $this->getLastApplicationError();
		if (!$error)
		{
			return null;
		}

		return $error->getMessage();
	}
}

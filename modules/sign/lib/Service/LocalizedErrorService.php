<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

/**
 * Localize error service error messages by code
 */
final class LocalizedErrorService
{
	public function getLocalizedError(Error $error): Error
	{
		$localizedMessage = $error->getCode() ? $this->getMessage($error) : null;

		return new Main\Error(
			$localizedMessage ?? $error->getMessage(),
			$error->getCode(),
			$error->getCustomData(),
		);
	}

	/**
	 * @param Main\Error[] $errors
	 * @return Main\Error[]
	 */
	public function localizeErrors(array $errors): array
	{
		$localizedErrors = [];
		foreach ($errors as $error)
		{
			$localizedErrors[] = $this->getLocalizedError($error);
		}
		return $localizedErrors;
	}

	private function getMessage(Error $error): string
	{
		$code = (string)$error->getCode();
		return match ($code)
		{
			'PERIOD_TOO_LONG', 'PERIOD_TOO_SHORT' => Loc::getMessagePlural(
				$code,
				$this->getPluralValue($code, $error->getCustomData()),
				$this->getReplacements($error),
			),
			default => Loc::getMessage($code, $this->getReplacements($error)),
		};
	}

	private function getPluralValue(string $code, array $customData): ?int
	{
		return match ($code)
		{
			'PERIOD_TOO_LONG' => $customData['MAX_PERIOD_MONTHS'] ?? 0,
			'PERIOD_TOO_SHORT' => $customData['MIN_PERIOD_MINUTES'] ?? 0,
			default => null,
		};
	}

	private function getReplacements(Error $error): array
	{
		return match ($error->getCode())
		{
			'PERIOD_TOO_LONG' => ['#MONTH#' => $this->getPluralValue($error->getCode(), $error->getCustomData())],
			'PERIOD_TOO_SHORT' => ['#MIN#' => $this->getPluralValue($error->getCode(), $error->getCustomData())],
			default => [],
		};
	}
}
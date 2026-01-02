<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use CAdminException;

class Result extends \Bitrix\Main\Result
{
	public static function success(...$data): static
	{
		return (new static())->setData($data);
	}

	/**
	 * @param Error|ErrorCollection|string|null $error
	 * @param string|int $code
	 * @return static
	 */
	public static function fail(Error|ErrorCollection|string|null $error = null, string|int $code = 0): static
	{
		$result = new static();

		return match (true){
			is_string($error) => $result->addError(new Error($error, $code)),
			$error instanceof Error => $result->addError($error),
			$error instanceof ErrorCollection => $result->addErrors($error->toArray()),
			default => $result->addCommonError(),
		};
	}

	public static function failAccessDenied(): static
	{
		return static::fail(ErrorCode::getAccessDeniedError());
	}

	public static function failEntityTypeNotSupported(?int $entityTypeId = null): static
	{
		return static::fail(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));
	}

	public static function failModuleNotInstalled(string $moduleName): static
	{
		return static::fail(ErrorCode::getModuleNotInstalledError($moduleName));
	}

	public static function failFromApplication(): static
	{
		return (new static())->fillErrorsFromApplication();
	}

	public function fillErrorsFromApplication(): static
	{
		global $APPLICATION;

		$exception = $APPLICATION->GetException();
		if ($exception instanceof CAdminException)
		{
			foreach ($exception->GetMessages() as $error)
			{
				$this->addError(new Error($error['text'], $error['id']));
			}
		}
		else
		{
			$this->addCommonError();
		}

		return $this;
	}

	public function addCommonError(): static
	{
		return $this->addError(ErrorCode::getGeneralError());
	}
}

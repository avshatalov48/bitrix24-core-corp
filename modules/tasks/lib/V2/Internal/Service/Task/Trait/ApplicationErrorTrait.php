<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use CAdminException;
use CApplicationException;
use CMain;

trait ApplicationErrorTrait
{
	private function getApplicationError(string $default = ''): string
	{
		$e = $this->getApplication()->GetException();

		if (is_a($e, CApplicationException::class))
		{
			$message = $e->GetString();
			$message = explode('<br>', $message);

			return $message[0];
		}

		if (
			!is_object($e)
			|| !isset($e->messages)
			|| !is_array($e->messages)
		)
		{
			return $default;
		}

		$message = array_shift($e->messages);

		if (
			is_array($message)
			&& isset($message['text'])
		)
		{
			$message = $message['text'];
		}
		elseif (!is_string($message))
		{
			$message = $default;
		}

		return $message;
	}

	private function getAdminApplicationError(): string
	{
		$e = $this->getApplication()->GetException();
		if (!$e)
		{
			throw new TaskAddException(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
		}

		if (
			$e instanceof CAdminException
			&& is_array($e->messages)
		)
		{
			$message = array_shift($e->messages);

			return $message['txt'];
		}

		return $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR'));
	}

	private function setApplicationError(string $message): void
	{
		$this->getApplication()->ThrowException(new CAdminException([['text' => $message]]));
	}

	private function getApplication(): CMain
	{
		global $APPLICATION;

		return $APPLICATION;
	}
}

<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller;

use Bitrix\Main\Error;

trait PublicErrorsTrait
{
	/**
	 * @param Error[] $errors
	 * @return Error[]
	 */
	protected function markErrorsAsPublic(array $errors): array
	{
		$publicErrors = [];

		foreach ($errors as $error)
		{
			/** @var string|array $message */
			$message = $error->getMessage();
			$message = isset($message['message']) && is_string($message['message']) ? $message['message'] : $message;

			if (!is_string($message) || $message === '')
			{
				continue;
			}

			$message = str_replace(['<br>', '<br/>', '<br />'], "\n", $message);
			$message = strip_tags($message);

			$publicErrors[] = new Error(
				$message,
				$error->getCode(),
				array_merge((array)($error->getCustomData() ?? []), ['public' => true])
			);
		}

		return $publicErrors;
	}
}

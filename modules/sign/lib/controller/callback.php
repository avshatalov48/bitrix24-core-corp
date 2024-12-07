<?php

namespace Bitrix\Sign\Controller;

use Bitrix\Main;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Engine;
use Bitrix\Sign\Service;

class Callback extends Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new Engine\ActionFilter\ClientAuth()
		];
	}

	public function handleAction(array $payload): array
	{
		$handler = Service\Container::instance()->getCallbackHandler();

		Main\Application::getInstance()->addBackgroundJob(function() use ($payload, $handler) {
			$result = $handler->execute($payload);

			if (!$result->isSuccess())
			{
				$errors = $result->getErrors();
				$errorsRepresentedTextLines = [];

				foreach ($errors as $error)
				{
					$errorCode = $error->getCode();
					$errorMessage = $error->getMessage();
					$errorsRepresentedTextLines[] = "Code: $errorCode. Message: $errorMessage";
				}
				$errorsRepresentedText = implode("\n", $errorsRepresentedTextLines);

				$logger = Logger::getInstance();
				$logger->error("Callback handling end with errors. \n $errorsRepresentedText");
			}
		});

		return [];
	}
}
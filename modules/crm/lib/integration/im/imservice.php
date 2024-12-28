<?php

namespace Bitrix\Crm\Integration\Im;

use Bitrix\Crm\Integration\Im\Message\MessageInterface;
use Bitrix\Im\V2\Service\Messenger;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use CIMMessenger;

final class ImService
{
	private const IM_COMPONENT_ID = 'CrmMessage';

	public function isAvailable(): bool
	{
		return Loader::includeModule('im')
			&& Messenger::getInstance()->checkAccessibility()
		;
	}

	public function sendText(int $userTo, int $userFrom, string $text): Result
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error('IM service is not available'));
		}

		$result = CIMMessenger::Add([
			'DIALOG_ID' => $userTo,
			'FROM_USER_ID' => $userFrom,
			'MESSAGE' => $text,
			'PARAMS' => [],
		]);

		if ($result === false)
		{
			return (new Result())->addError(new Error('Unable to add message to chat'));
		}

		return new Result();
	}

	public function sendMessage(MessageInterface $message): Result
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Error('IM service is not available'));
		}

		$result = CIMMessenger::Add([
			'DIALOG_ID' => $message->getUserTo(),
			'FROM_USER_ID' => $message->getUserFrom(),
			'MESSAGE' => $message->getFallbackText(),
			'PARAMS' => $this->buildParams($message),
		]);

		if ($result === false)
		{
			return (new Result())->addError(new Error('Unable to add message to chat'));
		}

		return new Result();
	}

	private function buildParams(MessageInterface $message): array
	{
		$params = [
			'COMPONENT_ID' => self::IM_COMPONENT_ID,
			'COMPONENT_PARAMS' => [
				'TYPE_ID' => $message->getTypeId(),
				'CONTEXT' => $this->buildCrmContext($message),
			],
		];

		if ($helpId = $message->getHelpId())
		{
			$params['COMPONENT_PARAMS']['HELP_ARTICLE'] = $helpId;
		}

		return $params;
	}

	private function buildCrmContext(MessageInterface $message): array
	{
		$params = [];

		if ($message->getLink())
		{
			$params['LINK'] = $message->getLink();
		}

		return $params;
	}
}

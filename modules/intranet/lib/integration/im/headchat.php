<?php

namespace Bitrix\Intranet\Integration\Im;

use Bitrix\Im\V2\Service\Locator;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use CIMChat;

class HeadChat
{
	public function __construct(private HeadChatConfiguration\Base $configuration)
	{
	}

	public function create(): Result
	{
		$resultCreat = Locator::getMessenger()->createChat([
			'ENTITY_TYPE' => 'SUPERVISOR_CHAT',
			'AVATAR' => $this->configuration->getAvatar(),
			'TITLE' => $this->configuration->getChatTitle(),
			'SEND_GREETING_MESSAGES' => 'Y',
		]);

		if ($resultCreat->isSuccess())
		{
			$chatId = $resultCreat->getResult()['CHAT_ID'];
			$this->addHead($chatId);

			if (!$this->addBanner($chatId))
			{
				return (new Result)->addError(new Error('Error when adding a banner'));
			}

			return (new Result())->setData(['chatId' => $chatId]);
		}

		return (new Result)->addErrors($resultCreat->getErrors());
	}

	private function addHead(int $chatId): void
	{
		(new CIMChat())->AddUser($chatId, [$this->configuration->getHeadId()], false);
	}

	private function addBanner(int $chatId): int|false
	{
		return \CIMMessenger::Add([
			'TO_CHAT_ID' => $chatId,
			'SYSTEM' => 'Y',
			'MESSAGE_TYPE' => 'C',
			'MESSAGE' => $this->configuration->getBannerDescription(),
			'PARAMS' => [
				'COMPONENT_ID' => $this->configuration->getBannerId(),
				'COMPONENT_PARAMS' => ['TOOL_ID' => $this->configuration->getCode()],
			],
		]);
	}
}
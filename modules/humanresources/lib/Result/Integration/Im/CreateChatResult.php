<?php

namespace Bitrix\HumanResources\Result\Integration\Im;

use Bitrix\HumanResources\Result\PropertyResult;
use Bitrix\Im\V2\Chat;

/**
 * Successful or unsuccessful result of createChat method in ChatService
 */
class CreateChatResult extends PropertyResult
{
	protected ?int $chatId = null;
	protected ?Chat $chat = null;

	public function setChatId(?int $chatId): static
	{
		$this->chatId = $chatId;

		return $this;
	}

	public function setChat(Chat $chat): static
	{
		$this->chat = $chat;

		return $this;
	}

	public function getChat(): ?Chat
	{
		return $this->chat;
	}

	public function getChatId(): ?int
	{
		return $this->chatId;
	}

	public function setData(array $data): static
	{
		$this->setChatId($data['CHAT_ID'] ?? null);
		if ($data['CHAT'] ?? null instanceof Chat)
		{
			$this->setChat($data['CHAT']);
		}

		return $this;
	}
}

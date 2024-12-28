<?php

namespace Bitrix\ImOpenLines\V2\Recent;

use Bitrix\Im\V2\Rest\RestConvertible;
use Bitrix\Main\Loader;

Loader::requireModule('im');

class RecentItem implements RestConvertible
{
	protected int $chatId;
	protected int $messageId;
	protected int $sessionId;
	protected string $dialogId;

	public function getDialogId(): string
	{
		return $this->dialogId;
	}

	public function setDialogId(string $dialogId): RecentItem
	{
		$this->dialogId = $dialogId;
		return $this;
	}

	public function getChatId(): int
	{
		return $this->chatId;
	}

	public function setChatId(int $chatId): RecentItem
	{
		$this->chatId = $chatId;
		return $this;
	}

	public function getMessageId(): int
	{
		return $this->messageId;
	}

	public function setMessageId(int $messageId): RecentItem
	{
		$this->messageId = $messageId;
		return $this;
	}

	public function getSessionId(): int
	{
		return $this->sessionId;
	}

	public function setSessionId(?int $sessionId): RecentItem
	{
		$this->sessionId = $sessionId;

		return $this;
	}

	public static function getRestEntityName(): string
	{
		return 'recentItem';
	}

	public function toRestFormat(array $option = []): ?array
	{
		return [
			'chatId' => $this->chatId,
			'messageId' => $this->messageId,
			'sessionId' => $this->sessionId,
			'dialogId' => $this->dialogId,
		];
	}
}
<?php

namespace Bitrix\ImOpenLines\V2\Recent;

use Bitrix\Main\Loader;

Loader::requireModule('im');

class RecentItem extends \Bitrix\Im\V2\Recent\RecentItem
{
	protected int $chatId;
	protected int $messageId;
	protected int $sessionId;
	protected string $dialogId;

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

	public function toRestFormat(array $option = []): array
	{
		return [
			'chatId' => $this->chatId,
			'messageId' => $this->messageId,
			'sessionId' => $this->sessionId,
			'dialogId' => $this->dialogId,
		];
	}
}
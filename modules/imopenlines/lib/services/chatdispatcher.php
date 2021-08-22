<?php declare(strict_types=1);

namespace Bitrix\ImOpenLines\Services;

use Bitrix\Main;
use Bitrix\ImOpenLines;

/**
 * Chat service.
 *
 * @package Bitrix\ImOpenLines\Services
 */
class ChatDispatcher
{
	/** @var bool */
	private $isEnabled;

	public function __construct()
	{
		$this->isEnabled = Main\Loader::includeModule('imopenlines');
	}

	/**
	 * Returns chat object.
	 *
	 * @param int $chatId Chat id.
	 *
	 * @return ImOpenLines\Chat|null
	 */
	public function getChat(int $chatId): ?ImOpenLines\Chat
	{
		if ($this->isEnabled)
		{
			return new ImOpenLines\Chat($chatId);
		}

		return null;
	}
}

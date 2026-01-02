<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Mobile\Tourist;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Imbot\Bot\SupportService;
use Bitrix\Main\DI\ServiceLocator;

class SupportProvider
{
	private ?int $userId;

	public function __construct()
	{
		$this->userId = (int)CurrentUser::get()->getId();
	}

	/**
	 * Checks if the bot is enabled.
	 * @return bool
	 * @throws LoaderException
	 */
	public function isEnabled(): bool
	{
		if (!$this->userId)
		{
			return false;
		}

		$supportService = $this->getSupportService();

		return $supportService?->isEnabled() && $supportService?->isActiveSupportForUser($this->userId);
	}

	/**
	 * Gets the bot ID based on the current support type.
	 * @return int|null
	 * @throws LoaderException
	 */
	public function getBotId(): ?int
	{
		if (!$this->isEnabled())
		{
			return null;
		}

		return $this->getSupportService()?->getBotId();
	}

	/**
	 * Checks if support is shown for the current user.
	 * @return bool
	 */
	public static function isSupportShownForUser(): bool
	{
		$events = Tourist::getEvents();

		return array_key_exists('show_support', $events);
	}

	/**
	 * Gets support service instance
	 * @return SupportService|null
	 * @throws LoaderException
	 */
	private function getSupportService(): ?SupportService
	{
		if (Loader::includeModule('imbot') && ServiceLocator::getInstance()->has('imbot.bot.support'))
		{
			return ServiceLocator::getInstance()->get('imbot.bot.support');
		}

		return null;
	}
}

<?php

namespace Bitrix\Imbot\Bot;

use Bitrix\Main\Loader;


class SupportService
{
	private string $botClass;

	public function __construct()
	{
		if (Loader::includeModule('bitrix24'))
		{
			if (Support24::isActivePartnerSupport())
			{
				$this->botClass = Partner24::class;
			}
			else
			{
				$this->botClass = Support24::class;
			}
		}
		else
		{
			$this->botClass = SupportBox::class;
		}
	}

	/**
	 * Checks if the support bot is enabled on portal.
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		$botClass = $this->getBotClass();
		if ($botClass::isEnabled())
		{
			return $botClass::isActiveSupport();
		}

		return false;
	}

	/**
	 * Allows certain user write to OL.
	 * @param int $userId
	 * @return bool
	 */
	public function isActiveSupportForUser(int $userId): bool
	{
		return $this->getBotClass()::isActiveSupportForUser($userId);
	}

	/**
	 * Returns an id of the current support bot.
	 * @return int|null
	 */
	public function getBotId(): ?int
	{
		return $this->getBotClass()::getBotId() ?: null;
	}

	/**
	 * Returns class name of the current support bot.
	 * @return string|SupportBot
	 */
	public function getBotClass(): string|SupportBot
	{
		return $this->botClass;
	}
}
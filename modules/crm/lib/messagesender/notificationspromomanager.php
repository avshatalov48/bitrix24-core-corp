<?php

namespace Bitrix\Crm\MessageSender;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Session\SessionInterface;

/**
 * Class NotificationsPromoManager
 * @package Bitrix\Crm\MessageSender
 * @internal
 */
final class NotificationsPromoManager
{
	private const PROMO_SESSION_KEY = 'CRM_NOTIFICATIONS_PROMO';
	private const PROMO_OPTION_KEY = '~CRM_NOTIFICATIONS_PROMO';

	/**
	 * @return bool
	 */
	public static function isPromoSession(): bool
	{
		$landingId = (int)self::getSession()?->get(static::PROMO_SESSION_KEY);
		if ($landingId)
		{
			$optionValue = Option::get('crm', static::getPromoOptionName($landingId), 'N');
			if ($optionValue !== 'Y')
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $landingId
	 */
	public static function enablePromoSession(int $landingId): void
	{
		self::getSession()?->set(static::PROMO_SESSION_KEY, $landingId);
	}

	public static function usePromo(): void
	{
		$landingId = (int)self::getSession()?->get(static::PROMO_SESSION_KEY);
		if ($landingId)
		{
			Option::set('crm', static::getPromoOptionName($landingId), 'Y');
		}
	}

	private static function getSession(): ?SessionInterface
	{
		$session = Application::getInstance()->getSession();
		if (!$session->isAccessible())
		{
			return null;
		}

		return $session;
	}

	/**
	 * @param int $landingId
	 * @return string
	 */
	private static function getPromoOptionName(int $landingId): string
	{
		return sprintf(
			'%s_%s',
			static::PROMO_OPTION_KEY,
			$landingId
		);
	}
}

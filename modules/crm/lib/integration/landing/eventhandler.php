<?php

namespace Bitrix\Crm\Integration\Landing;

use Bitrix\Main;

/**
 * Class EventHandler
 * @package Bitrix\Crm\Integration\Landing
 */
class EventHandler
{
	/**
	 * Handler of event before deleting landing.
	 *
	 * @param Main\Event $event Event.
	 * @return Main\Entity\EventResult
	 */
	public static function onBeforeLandingDelete(Main\Event $event)
	{
		$result = new Main\Entity\EventResult;
		$landingId = $event->getParameter('primary')['ID'];
		if (!FormLanding::getInstance()->canDelete($landingId))
		{
			$result->setErrors([
				new \Bitrix\Main\Entity\EntityError(
					'This is CRM-form\'s site, delete action is not allowed',
					'DELETE_IS_NOT_ALLOWED'
				)
			]);
			return $result;
		}

		return $result;
	}

	/**
	 * Handler of event before recycling landing.
	 *
	 * @param Main\Event $event Event.
	 * @return Main\Entity\EventResult
	 */
	public static function onBeforeLandingRecycle(Main\Event $event)
	{
		$result = new Main\Entity\EventResult;
		if ($event->getParameter('delete') === 'Y')
		{
			$landingId = $event->getParameter('id');
			if (!FormLanding::getInstance()->canDelete($landingId))
			{
				$result->setErrors([
					new \Bitrix\Main\Entity\EntityError(
						'This is CRM form\'s site, delete action is not allowed',
						'DELETE_IS_NOT_ALLOWED'
					)
				]);
				return $result;
			}
		}

		return $result;
	}

	/**
	 * Handler of event before recycling site.
	 *
	 * @param Main\Event $event Event.
	 * @return Main\Entity\EventResult
	 */
	public static function onBeforeSiteRecycle(Main\Event $event)
	{
		$result = new Main\Entity\EventResult;
		if ($event->getParameter('delete') === 'Y')
		{
			$formLanding = new FormLanding();
			$siteId = $event->getParameter('id');
			if ($siteId === $formLanding->getSiteId())
			{
				$result->setErrors([
					new \Bitrix\Main\Entity\EntityError(
						'This is CRM form\'s site, delete action is not allowed',
						'DELETE_IS_NOT_ALLOWED'
					)
				]);
				return $result;
			}
		}

		return $result;
	}
}
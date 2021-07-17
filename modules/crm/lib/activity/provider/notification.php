<?php

namespace Bitrix\Crm\Activity\Provider;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Notification
 * @package Bitrix\Crm\Activity\Provider
 */
class Notification extends BaseMessage
{
	public const PROVIDER_TYPE_NOTIFICATION = 'NOTIFICATION';

	/**
	 * @inheritDoc
	 */
	public static function getId()
	{
		return 'CRM_NOTIFICATION';
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return 'NOTIFICATION';
	}

	/**
	 * @inheritDoc
	 */
	protected static function getDefaultTypeId(): string
	{
		return self::PROVIDER_TYPE_NOTIFICATION;
	}

	/**
	 * @inheritDoc
	 */
	protected static function getRenderViewComponentName(): string
	{
		return 'bitrix:crm.activity.notification';
	}
}

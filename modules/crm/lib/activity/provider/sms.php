<?php

namespace Bitrix\Crm\Activity\Provider;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

/**
 * Class Sms
 * @package Bitrix\Crm\Activity\Provider
 */
class Sms extends BaseMessage
{
	public const PROVIDER_TYPE_SMS = 'SMS';

	/**
	 * @inheritDoc
	 */
	public static function getId()
	{
		return 'CRM_SMS';
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return 'SMS';
	}

	/**
	 * @inheritDoc
	 */
	protected static function getDefaultTypeId(): string
	{
		return self::PROVIDER_TYPE_SMS;
	}

	/**
	 * @inheritDoc
	 */
	protected static function getRenderViewComponentName(): string
	{
		return 'bitrix:crm.activity.sms';
	}
}

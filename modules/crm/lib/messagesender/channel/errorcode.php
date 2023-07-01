<?php

namespace Bitrix\Crm\MessageSender\Channel;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

final class ErrorCode
{
	public const NOT_ENOUGH_MODULES = 'NOT_ENOUGH_MODULES';
	public const NOT_AVAILABLE = 'NOT_AVAILABLE';
	public const NOT_CONNECTED = 'NOT_CONNECTED';
	public const USAGE_ERROR = 'USAGE_ERROR';
	public const UNKNOWN_CHANNEL = 'UNKNOWN_CHANNEL';
	public const UNUSABLE_CHANNEL = 'UNUSABLE_CHANNEL';

	public const NO_RECEIVERS = 'NO_RECEIVERS';

	public static function getNotEnoughModulesError(): Error
	{
		return new Error(Loc::getMessage('CRM_MESSAGESENDER_ERROR_NOT_ENOUGH_MODULES'), self::NOT_ENOUGH_MODULES);
	}

	public static function getNotAvailableError(): Error
	{
		return new Error(Loc::getMessage('CRM_MESSAGESENDER_ERROR_NOT_AVAILABLE'), self::NOT_AVAILABLE);
	}

	public static function getNotConnectedError(): Error
	{
		return new Error(Loc::getMessage('CRM_MESSAGESENDER_ERROR_NOT_CONNECTED'), self::NOT_CONNECTED);
	}

	public static function getUnknownChannelError(): Error
	{
		return new Error(Loc::getMessage('CRM_MESSAGESENDER_ERROR_UNKNOWN_CHANNEL'), self::UNKNOWN_CHANNEL);
	}

	public static function getUnusableChannelError(): Error
	{
		return new Error(Loc::getMessage('CRM_MESSAGESENDER_ERROR_UNUSABLE_CHANNEL'), self::UNUSABLE_CHANNEL);
	}

	public static function getNoReceiversError(): Error
	{
		return new Error(Loc::getMessage('CRM_MESSAGESENDER_ERROR_NO_RECEIVERS'), self::NO_RECEIVERS);
	}

	private function __construct()
	{
	}
}

<?php

use Bitrix\Crm\Integration\Im\ProcessEntity\Notification;
use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

class CCrmNotifier
{
	protected static array $ERRORS = [];

	public static function Notify(
		$addresseeID,
		$internalMessage,
		$externalMessage,
		$schemeTypeID,
		$tag = '',
	): bool
	{
		self::ClearErrors();

		if (!(IsModuleInstalled('im') && CModule::IncludeModule('im')))
		{
			self::RegisterError('IM module is not installed.');

			return false;
		}

		if ($addresseeID <= 0)
		{
			self::RegisterError('Addressee is not assigned.');

			return false;
		}

		$arMessage = [
			'NOTIFY_TITLE' => Loc::getMessage('CRM_NOTIFY_TITLE'),
			'TO_USER_ID' => $addresseeID,
			'FROM_USER_ID' => 0,
			'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
			'NOTIFY_MODULE' => 'crm',
			'NOTIFY_MESSAGE' => $internalMessage,
			'NOTIFY_MESSAGE_OUT' => $externalMessage,
		];

		$schemeTypeName = CCrmNotifierSchemeType::ResolveName($schemeTypeID);
		if ($schemeTypeName !== '')
		{
			$arMessage['NOTIFY_EVENT'] = $schemeTypeName;
		}

		$tag = (string)$tag;
		if ($tag !== '')
		{
			$arMessage['NOTIFY_TAG'] = $tag;
		}

		$msgID = CIMNotify::Add($arMessage);
		if (!$msgID)
		{
			$exception = $GLOBALS['APPLICATION']->GetException();
			$errorMessage = $exception
				? $exception->GetString()
				: 'Unknown sending error. message not send.';

			self::RegisterError($errorMessage);

			return false;
		}

		return true;
	}

	public static function GetLastErrorMessage(): ?string
	{
		$errorsCnt = count(self::$ERRORS);

		return $errorsCnt > 0 ? self::$ERRORS[$errorsCnt - 1] : '';
	}

	public static function GetErrorMessages(): array
	{
		return self::$ERRORS;
	}

	public static function GetErrorCount(): int
	{
		return count(self::$ERRORS);
	}

	protected static function RegisterError($msg): void
	{
		$msg = (string)$msg;
		if ($msg !== '')
		{
			self::$ERRORS[] = $msg;
		}
	}

	private static function ClearErrors(): void
	{
		if (!empty(self::$ERRORS))
		{
			self::$ERRORS = [];
		}
	}
}

class CCrmNotifierSchemeType
{
	public const Undefined = 0;
	public const IncomingEmail = 1;
	public const WebForm = 4;
	public const Callback = 5;

	public const IncomingEmailName = 'incoming_email';
	public const WebFormName = 'webform';
	public const CallbackName = 'callback';

	public static function ResolveName($typeID): string
	{
		$typeID = (int)$typeID;
		switch ($typeID)
		{
			case self::IncomingEmail:
				return self::IncomingEmailName;
			case self::WebForm:
			case self::Callback:
				return self::WebFormName;
		}

		return '';
	}

	public static function PrepareNotificationSchemes(): array
	{
		return [
			'crm' => [
				'incoming_email' => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_ACTIVITY_EMAIL_INCOMING'),
					'MAIL' => 'Y',
					'XMPP' => 'Y',
					'PUSH' => 'N',
				],
				'post' => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_LIVEFEED_POST'),
					'PUSH' => 'N',
				],
				'mention' => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_LIVEFEED_MENTION'),
					'PUSH' => 'N',
				],
				self::WebFormName => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_WEBFORM'),
					'LIFETIME' => 86400 * 7,
					'PUSH' => 'N',
				],
				self::CallbackName => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_CALLBACK'),
					'LIFETIME' => 86400 * 7,
					'PUSH' => 'N',
				],
				Notification\Responsible::NOTIFY_EVENT => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_ENTITY_ASSIGNED_BY'),
					'PUSH' => 'N',
				],
				Notification\Observer::NOTIFY_EVENT => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_ENTITY_OBSERVER'),
					'PUSH' => 'N',
				],
				'changeStage' => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_ENTITY_STAGE'),
					'PUSH' => 'N',
				],
				'merge' => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_MERGE'),
					'PUSH' => 'N',
				],
				'other' => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_OTHER'),
					'PUSH' => 'N',
				],
				'pingTodoActivity' => [
					'NAME' => GetMessage('CRM_NOTIFY_SCHEME_PING_TODO_ACTIVITY'),
					'MAIL' => 'N',
					'PUSH' => 'Y',
				],
			],
		];
	}
}

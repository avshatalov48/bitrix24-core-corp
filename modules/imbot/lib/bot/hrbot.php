<?php

namespace Bitrix\Imbot\Bot;

use Bitrix\Main;
use Bitrix\Im;
use Bitrix\Main\Localization\Loc;

class HrBot extends Base
{
	public const BOT_CODE = 'hrbot';

	protected const BOT_PROPERTIES = [
		'CODE' => self::BOT_CODE,
		'TYPE' => Im\Bot::TYPE_BOT,
		'MODULE_ID' => self::MODULE_ID,
		'CLASS' => self::class,
		'OPENLINE' => 'N', // Allow in Openline chats
		'HIDDEN' => 'Y',
		'INSTALL_TYPE' => Im\Bot::INSTALL_TYPE_SILENT, // suppress success install message
		'METHOD_MESSAGE_ADD' => 'onMessageAdd',/** @see HrBot::onMessageAdd */
		'METHOD_MESSAGE_UPDATE' => 'onMessageUpdate',/** @see HrBot::onMessageUpdate */
		'METHOD_MESSAGE_DELETE' => 'onMessageDelete',/** @see HrBot::onMessageDelete */
		'METHOD_BOT_DELETE' => 'onBotDelete',/** @see HrBot::onBotDelete */
		'METHOD_WELCOME_MESSAGE' => 'onChatStart',/** @see HrBot::onChatStart */
	];

	public static function getBotIdOrRegister(): int
	{
		return self::getBotId() ?: self::register();
	}

	public static function register(array $params = []): int
	{
		if (!Main\Loader::includeModule('im'))
		{
			return -1;
		}

		if (self::getBotId())
		{
			return self::getBotId();
		}

		$languageId = Loc::getCurrentLang();
		if (!empty($params['LANG']))
		{
			$languageId = $params['LANG'];
			Loc::loadLanguageFile(__FILE__, $languageId);
		}

		$botProps = array_merge(self::BOT_PROPERTIES, [
			'LANG' => $languageId,// preferred language
			'PROPERTIES' => [
				'NAME' => Loc::getMessage('IMBOT_HR_BOT_NAME', null, $languageId),
				'WORK_POSITION' => Loc::getMessage('IMBOT_HR_BOT_WORK_POSITION', null, $languageId),
				'COLOR' => 'MARENGO',
			],
		]);

		$botAvatar = self::uploadAvatar($languageId);
		if (!empty($botAvatar))
		{
			$botProps['PROPERTIES']['PERSONAL_PHOTO'] = $botAvatar;
		}

		$botId = Im\Bot::register($botProps);

		if ($botId)
		{
			self::setBotId($botId);
		}

		return is_int($botId) ? $botId : 0;
	}

	public static function unRegister(): bool
	{
		if (!Main\Loader::includeModule('im'))
		{
			return false;
		}

		return Im\Bot::unRegister(['BOT_ID' => self::getBotId()]);
	}
}

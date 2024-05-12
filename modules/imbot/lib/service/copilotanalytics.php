<?php

namespace Bitrix\ImBot\Service;

use Bitrix\AI\Engine;
use Bitrix\Im\V2\Chat;
use Bitrix\Imbot\Bot\CopilotChatBot;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Result;

class CopilotAnalytics
{
	protected const ANALYTICS_STATUS = [
		'SUCCESS' => 'success',
		'ERROR_PROVIDER' => 'error_provider',
		'ERROR_B24' => 'error_b24',
		'ERROR_LIMIT_DAILY' => 'error_limit_daily',
		'ERROR_LIMIT_MONTHLY' => 'error_limit_monthly',
		'ERROR_AGREEMENT' => 'error_agreement',
		'ERROR_TURNEDOFF' => 'error_turnedoff',
	];
	protected const ANALYTICS_EVENTS = [
		'GENERATE' => 'generate',
		'RECEIVED_RESULT' => 'received_result',
	];
	protected const ANALYTICS_TOOL = 'ai';
	protected const ANALYTICS_CATEGORY = 'chat_operations';
	protected const ANALYTICS_ELEMENT = 'copilot_tab';
	protected const ANALYTICS_TYPE = 'chatType_private';

	public static function sendAnalyticsEvent(
		Chat $chat,
		?Engine $engine = null,
		?string $promptCode = null,
		?string $role = null,
		?string $status = null
	): void
	{
		$event = new AnalyticsEvent(
			isset($status) ? self::ANALYTICS_EVENTS['RECEIVED_RESULT'] : self::ANALYTICS_EVENTS['GENERATE'],
			self::ANALYTICS_TOOL,
			self::ANALYTICS_CATEGORY
		);

		if ($chat->getUserCount() > 2 || $chat->getType() !== 'A')
		{
			return;
		}

		$event
			->setP1(self::convertUnderscoreForAnalytics('none'))
			->setP2('provider_' . (isset($engine) ? $engine->getIEngine()->getName() : 'none'))
			->setP3(self::ANALYTICS_TYPE)
//			->setP4('role_' . (isset($role) ? self::convertUnderscoreForAnalytics($role) : 'copilotAssistant'))
			->setP5('chatId_' . $chat->getChatId())
			->setSection(self::ANALYTICS_ELEMENT)
		;

		if (isset($promptCode))
		{
			$event->setP1('1st-type_' . self::convertUnderscoreForAnalytics($promptCode));
		}

		if (isset($status))
		{
			$event->setStatus($status);
		}

		$event->send();
	}

	public static function getCopilotStatusByResult(Result $result): string
	{
		if ($result->isSuccess())
		{
			return self::ANALYTICS_STATUS['SUCCESS'];
		}

		$error = $result->getErrors()[0];
		if (!isset($error))
		{
			return self::ANALYTICS_STATUS['ERROR_B24'];
		}

		switch ($error->getCode())
		{
			case (CopilotChatBot::AI_ENGINE_ERROR_PROVIDER):
				return self::ANALYTICS_STATUS['ERROR_PROVIDER'];

			case (CopilotChatBot::LIMIT_IS_EXCEEDED_DAILY):
				return self::ANALYTICS_STATUS['ERROR_LIMIT_DAILY'];

			case (CopilotChatBot::LIMIT_IS_EXCEEDED_MONTHLY):
				return self::ANALYTICS_STATUS['ERROR_LIMIT_MONTHLY'];

			case (CopilotChatBot::ERROR_AGREEMENT):
				return self::ANALYTICS_STATUS['ERROR_AGREEMENT'];
		}

		return self::ANALYTICS_STATUS['ERROR_B24'];
	}

	protected static function convertUnderscoreForAnalytics(string $test): string
	{
		return (new Converter(Converter::TO_CAMEL | Converter::LC_FIRST))->process($test);
	}
}
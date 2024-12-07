<?php

namespace Bitrix\ImBot\Controller\Network;

use Bitrix\ImBot\Bot\Network;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;

class Chat extends Controller
{
	private const DEFAULT_LIMIT = 25;

	protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
	{
		$result = parent::processBeforeAction($action);

		if (!Loader::includeModule('im'))
		{
			$this->addError(new Error('Messenger is not installed', 'IM_NOT_INSTALLED'));
			return null;
		}

		return $result;
	}

	/**
	 * @restMethod imbot.Network.Chat.list
	 */
	public function listAction(?int $botId = null, int $limit = self::DEFAULT_LIMIT, int $offset = 0): ?array
	{

		$supportBotClass = null;
		if (!$botId)
		{
			if (!$supportBotClass = self::detectSupportBot())
			{
				$this->addError(new Error('Unknown bot', 'UNKNOWN_BOT'));
				return null;
			}
		}

		$params['BOT_ID'] = $botId;

		if ($limit > 0)
		{
			$params['LIMIT'] = $limit;
		}

		if ($offset > 0)
		{
			$params['OFFSET'] = $offset;
		}

		return $supportBotClass ? $supportBotClass::getSupportQuestionList($params) : Network::getQuestionList($params);
	}

	/**
	 * @restMethod imbot.Network.Chat.count
	 */
	public function countAction(?int $botId = null): ?array
	{
		$supportBotClass = null;
		if (!$botId)
		{
			if (!$supportBotClass = self::detectSupportBot())
			{
				$this->addError(new Error('Unknown bot', 'UNKNOWN_BOT'));
				return null;
			}
		}

		if ($supportBotClass)
		{
			$result = [
				'count' => $supportBotClass::getQuestionsCount(),
				'openSessionsLimit' => $supportBotClass::getQuestionLimit(),
				'chatIdsWithCounters' => $supportBotClass::getQuestionsWithUnreadMessages(),
			];
		}
		else
		{
			$result = [
				'count' => Network::getQuestionsCount($botId),
				'openSessionsLimit' => Network::getQuestionLimit($botId),
				'chatIdsWithCounters' => Network::getQuestionsWithUnreadMessages($botId),
			];
		}

		return $result;
	}

	/**
	 * @restMethod imbot.Network.Chat.add
	 */
	public function addAction(?int $botId = null)
	{
		$supportBotClass = null;
		if (!$botId)
		{
			if (!$supportBotClass = self::detectSupportBot())
			{
				$this->addError(new Error('Unknown bot', 'UNKNOWN_BOT'));
				return null;
			}
		}

		if (
			$supportBotClass && !$supportBotClass::allowAdditionalQuestion()
			|| $botId && !Network::allowAdditionalQuestion($botId)
		)
		{
			$this->addError(new Error('The limit for amount questions has been reached', 'QUESTION_LIMIT_EXCEEDED'));
			return null;
		}

		$chatId = $supportBotClass ? $supportBotClass::addSupportQuestion() : Network::addNetworkQuestionByBotId($botId);

		if (\Bitrix\ImBot\Bot\Network::hasError())
		{
			$error = \Bitrix\ImBot\Bot\Network::getError();
			$this->addError(new Error($error->msg, $error->code));
			return null;
		}

		$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);

		return $chat->toRestFormat();
	}

	/**
	 * Detects installed support bot.
	 * @return \Bitrix\ImBot\Bot\SupportBot & \Bitrix\Imbot\Bot\SupportQuestion|string|null
	 */
	private static function detectSupportBot(): ?string
	{
		static $classSupport = null;

		if ($classSupport === null)
		{
			/** @var \Bitrix\Imbot\Bot\SupportBot $classSupport */
			if (
				Loader::includeModule('bitrix24')
				&& \Bitrix\ImBot\Bot\Support24::isEnabled()
			)
			{
				$classSupport = \Bitrix\ImBot\Bot\Support24::class;
			}
			elseif (\Bitrix\ImBot\Bot\SupportBox::isEnabled())
			{
				$classSupport = \Bitrix\ImBot\Bot\SupportBox::class;
			}
		}

		return $classSupport;
	}
}
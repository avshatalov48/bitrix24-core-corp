<?php

namespace Bitrix\ImBot\Bot\Mixin;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im;
use Bitrix\Im\Bot\Keyboard;
use Bitrix\ImBot;
use Bitrix\ImBot\Error;
use Bitrix\ImBot\Model\NetworkSessionTable;

const CHAT_QUESTION_SUPPORT_COUNTER = 'imbot_support_question';
const COMMAND_ADD_QUESTION = 'question';
const OPTION_BOT_QUESTION_LIMIT = 'support24_session_limit';
const COMMAND_RESUME_SESSION = 'resume';

trait SupportQuestion
{
	/**
	 * Returns app's property list for questioning.
	 * @return array{command: string, icon: string, js: string, context: string, lang: string}[]
	 */
	public static function getQuestionAppList(): array
	{
		return [
			COMMAND_ADD_QUESTION => [
				'command' => COMMAND_ADD_QUESTION,
				'icon' => '/bitrix/modules/imbot/install/icon/support24/question.svg',
				'js' => 'BX.MessengerSupport24.togglePopup()',
				'context' => 'bot',
				'lang' => 'onAppLang',/** @see Support24::onAppLang */
			]
		];
	}

	/**
	 * Increments global for portal question counter.
	 * @return int
	 */
	public static function incrementGlobalQuestionCounter(): int
	{

		\CGlobalCounter::increment(CHAT_QUESTION_SUPPORT_COUNTER, \CGlobalCounter::ALL_SITES, false);
		$counter = (int)\CGlobalCounter::getValue(CHAT_QUESTION_SUPPORT_COUNTER, \CGlobalCounter::ALL_SITES);

		return $counter;
	}

	/**
	 * Tells true if additional question functional is enabled.
	 * @return bool
	 */
	public static function isEnabledQuestionFunctional(): bool
	{
		$questionLimit = static::getQuestionLimit();
		// -1 - full disabled
		// 1 - Only one session allowed
		if ($questionLimit < 0 || $questionLimit === 1)
		{
			return false;
		}
		// 0 - There is no limit
		// n - limit of active dialogs
		return true;
	}

	/**
	 * Permits adding new additional question.
	 * @return bool
	 */
	public static function allowAdditionalQuestion(): bool
	{
		if (static::isEnabledQuestionFunctional())
		{
			$questionLimit = static::getQuestionLimit();
			// 0 - There is no limit
			if ($questionLimit === 0)
			{
				return true;
			}
			// n - limit of active dialogs
			if ($questionLimit > 0)
			{
				$dialogSession = new ImBot\DialogSession(static::getBotId());

				$dialogs = [
					static::getCurrentUser()->getId()// dialog one-to-one
				];
				foreach (static::getRecentDialogs($dialogSession::EXPIRES_DAYS * 24) as $dialog)
				{
					if (
						$dialog['MESSAGE_TYPE'] == \IM_MESSAGE_CHAT
						&& $dialog['USER_ID'] == static::getCurrentUser()->getId()
					)
					{
						$dialogs[] = 'chat'.$dialog['CHAT_ID'];
					}
				}

				$countActiveSessions = $dialogSession->countActiveSessions([
					'=BOT_ID' => static::getBotId(),
					'=DIALOG_ID' => $dialogs,
				]);

				return $countActiveSessions < $questionLimit;
			}
		}

		return false;
	}

	/**
	 * Returns the limit for additional questions.
	 * @return int
	 * -1 - Functional is disabled,
	 * 0 - There is no limit,
	 * 1 - Only one session allowed,
	 * n - Max number for sessions allowed.
	 */
	public static function getQuestionLimit(): int
	{
		return (int)Option::get(static::MODULE_ID, OPTION_BOT_QUESTION_LIMIT, -1);
	}

	/**
	 * Returns button for session resume.
	 * @return Keyboard
	 */
	public static function getQuestionResumeButton(): Keyboard
	{
		$text = static::getMessage('CHAT_QUESTION_RESUME');
		if (!$text)
		{
			$text = Loc::getMessage('CHAT_QUESTION_RESUME');
		}

		$xxx = class_implements(static::class);

		$itrMenuResume = in_array('Bitrix\\Imbot\\Bot\\MenuBot', $xxx);

		$keyboard = new Keyboard(static::getBotId());
		$keyboard->addButton([
			'DISPLAY' => 'LINE',
			'TEXT' =>  $text,
			'BG_COLOR' => '#29619b',
			'TEXT_COLOR' => '#fff',
			'BLOCK' => 'Y',
			'COMMAND' => $itrMenuResume
				? 'menu' /** @see ItrMenu::COMMAND_MENU */
				: static::COMMAND_NETWORK_SESSION,
			'COMMAND_PARAMS' => 'resume',
		]);

		return $keyboard;
	}

	/**
	 * Returns question disallow message to display for user.
	 * @return string
	 */
	public static function getQuestionDisallowMessage(): string
	{
		$questionLimit = static::getQuestionLimit();
		if ($questionLimit > 0)
		{
			$message = static::getMessage('CHAT_QUESTION_LIMITED');
			if ($message)
			{
				$message = str_replace('#NUMBER#', $questionLimit, $message);
			}
			else
			{
				$message = Loc::getMessage('CHAT_QUESTION_LIMITED', ['#NUMBER#' => $questionLimit]) ?? '';
			}
		}
		else
		{
			$message = static::getMessage('CHAT_QUESTION_DISALLOWED');
			if (!$message)
			{
				$message = Loc::getMessage('CHAT_QUESTION_DISALLOWED') ?? '';
			}
		}

		return $message;
	}

	/**
	 * Returns configuration flags for client.
	 *
	 * @return array
	 */
	public static function getSupportQuestionConfig(): array
	{
		$canImproveTariff = false;
		if (Main\Loader::includeModule('bitrix24'))
		{
			$canImproveTariff = \CBitrix24::isMaximalLicense() !== true;
		}

		$isAdmin = static::isUserAdmin(static::getCurrentUser()->getId());

		return [
			'isAdmin' => $isAdmin,
			//'canAskQuestion' => $isAdmin && static::allowAdditionalQuestion(), //todo: Revert it if you need a slider
			'canAskQuestion' => $isAdmin,
			'canImproveTariff' => $canImproveTariff,
		];
	}

	/**
	 * Starts new question dialog.
	 *
	 * @return int
	 */
	public static function addSupportQuestion(): int
	{
		/*
		todo: Revert it if you need a slider
		if (!static::allowAdditionalQuestion())
		{
			static::addError(new Error(
				__METHOD__,
				'QUESTION_LIMIT_EXCEEDED',
				"The limit for amount questions has been reached"
			));

			return -1;
		}
		*/

		$counter = static::incrementGlobalQuestionCounter();
		$title = static::getMessage('CHAT_QUESTION_TITLE');
		if ($title)
		{
			$title = str_replace('#NUMBER#', $counter, $title);
		}
		else
		{
			$title = Loc::getMessage('CHAT_QUESTION_TITLE', ['#NUMBER#' => $counter]);
		}

		$chatParams = [
			'TYPE' => \IM_MESSAGE_CHAT,
			'ENTITY_TYPE' => static::CHAT_ENTITY_TYPE,
			'ENTITY_ID' => "question|{$counter}",
			'USERS' => [
				static::getBotId(),
				static::getCurrentUser()->getId(),
			],
			'OWNER_ID' => static::getBotId(),
			'TITLE' => $title,
			'DESCRIPTION' => static::getMessage('CHAT_QUESTION_DESC') ?: Loc::getMessage('CHAT_QUESTION_DESC'),
			'MESSAGE' => static::getMessage('CHAT_QUESTION_GREETING') ?: Loc::getMessage('CHAT_QUESTION_GREETING'),
			'SKIP_ADD_MESSAGE' => 'Y',
		];

		$chatId = (new \CIMChat(static::getBotId()))->add($chatParams);
		if (!$chatId)
		{
			$error = static::getApplication()->getException();
			if ($error instanceof \CApplicationException)
			{
				static::addError(new Error(
					__METHOD__,
					'WRONG_REQUEST',
					$error->getString()
				));

				return -1;
			}

			static::addError(new Error(
				__METHOD__,
				'WRONG_REQUEST',
				"Chat can't be created"
			));

			return -1;
		}

		return $chatId;
	}

	/**
	 * Returns the question dialog list and perfoms searching by question dialog title.
	 * @param array $params Query parameters.
	 * <pre>
	 * [
	 * 	(string) searchQuery - String to search by title.
	 * 	(int) limit - Number rows to select.
	 * 	(int) offset - Set starting offset.
	 * ]
	 * </pre>
	 * @return array{id: int, title: string}
	 */
	public static function getSupportQuestionList(array $params): array
	{
		if (!static::isUserAdmin(static::getCurrentUser()->getId()))
		{
			static::addError(new Error(
				__METHOD__,
				'ACCESS_DENIED',
				'You do not have access to create specified dialog'
			));
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$filter = [
			'=TYPE' => \IM_MESSAGE_CHAT,
			'=ENTITY_TYPE' => static::CHAT_ENTITY_TYPE,
			'=AUTHOR_ID' => static::getBotId(),
		];

		if (!empty($params['SEARCHQUERY']))
		{
			$filter['%TITLE'] = $params['SEARCHQUERY'];
		}

		$chatRes = Im\Model\ChatTable::getList([
			'runtime' => [
				new Main\ORM\Fields\Relations\Reference(
					'RELATION',
					Im\Model\RelationTable::class,
					Main\ORM\Query\Join::on('ref.CHAT_ID', '=', 'this.ID')->where('ref.USER_ID', '=', static::getCurrentUser()->getId()),
					['join_type' => 'INNER']
				)
			],
			'select' => ['ID', 'TITLE'],
			'filter' => $filter,
			'order' => ['ID' => 'DESC'],
			'limit' => $params['LIMIT'] ? (int)$params['LIMIT'] : 25,
			'offset' => $params['OFFSET'] ? (int)$params['OFFSET'] : 0,
		]);

		$questions = [];
		while ($chat = $chatRes->fetch())
		{
			$questions[] = [
				'id' => (int)$chat['ID'],
				'title' => $chat['TITLE'],
			];
		}

		return $questions;
	}

	/**
	 * @param string $lang
	 * @return array{TITLE: string, PARAMS: string}
	 */
	protected static function getSupportQuestionAppLang($lang = null): array
	{
		$title = self::getMessage('QUESTION');
		if (!$title)
		{
			$title = Loc::getMessage('SUPPORT_QUESTION_TITLE', null, $lang);
		}
		$description = self::getMessage('QUESTION_HELP');
		if (!$description)
		{
			$description = Loc::getMessage('SUPPORT_QUESTION_DESCRIPTION', null, $lang);
		}

		return [
			'TITLE' => $title,
			'DESCRIPTION' => $description,
			'COPYRIGHT' => ''
		];
	}

	/**
	 * Restores support chats owner and message history author.
	 * @return void
	 */
	protected static function restoreQuestionHistory(): void
	{
		$connection = Main\Application::getConnection();
		$chatRes = Im\Model\ChatTable::getList([
			'select' => ['ID', 'AUTHOR_ID', 'ENTITY_TYPE'],
			'filter' => [
				'=TYPE' => \IM_MESSAGE_CHAT,
				'=ENTITY_TYPE' => [static::CHAT_ENTITY_TYPE, ImBot\Service\Notifier::CHAT_ENTITY_TYPE],
				'!=AUTHOR_ID' => static::getBotId(),
			],
		]);
		while ($chatData = $chatRes->fetch())
		{
			if ($chatData['AUTHOR_ID'] != static::getBotId())
			{
				if ($chatData['ENTITY_TYPE'] == ImBot\Service\Notifier::CHAT_ENTITY_TYPE)
				{
					ImBot\Service\Notifier::changeChannelOwner((int)$chatData['ID'], static::getBotId(), (int)$chatData['AUTHOR_ID']);
				}
				else
				{
					$chat = new \CIMChat(0);
					$chat->addUser($chatData['ID'], static::getBotId(), true, true, true);
					$chat->setOwner($chatData['ID'], static::getBotId(), false);

					$connection->queryExecute(
						'UPDATE ' . Im\Model\MessageTable::getTableName()
						. ' SET AUTHOR_ID = ' . static::getBotId()
						. ' WHERE AUTHOR_ID = ' . (int)$chatData['AUTHOR_ID'] . ' AND CHAT_ID = ' . (int)$chatData['ID']
					);
				}
			}
		}
	}
}

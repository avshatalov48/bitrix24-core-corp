<?php

namespace Bitrix\Imbot\Bot\Mixin;

use Bitrix\Imbot\Bot\Network;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Imbot\Bot\MenuBot;
use Bitrix\ImBot\ItrMenu;


const MESSAGE_PARAM_MENU_ACTION = 'IMB_MENU_ACTION'; // menu action parameter

/**
 * The implement of the Imbot\Bot\MenuBot interface.
 *
 * @package \Bitrix\Imbot\Bot\Mixin
 */
trait NetworkMenuBot
{
	/** @var \Bitrix\ImBot\ItrMenu */
	protected static $menu;

	/**
	 * Returns command's property list.
	 * @return array{class: string, handler: string, visible: bool, context: string}[]
	 */
	public static function getMenuCommandList(): array
	{
		return [
			ItrMenu::COMMAND_MENU => [
				'command' => ItrMenu::COMMAND_MENU,
				'handler' => 'onCommandAdd',/** @see Support24::onCommandAdd */
				'visible' => false,
				'context' => [
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_PRIVATE,
						'TO_USER_ID' => static::getBotId(),
					],
					[
						'COMMAND_CONTEXT' => 'KEYBOARD',
						'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
						'CHAT_ENTITY_TYPE' => static::CHAT_ENTITY_TYPE,
					],
				],
			],
		];
	}

	/**
	 * @return ItrMenu
	 */
	public static function instanceMenu(): ItrMenu
	{
		if (static::$menu === null)
		{
			static::$menu = new ItrMenu(static::getBotId());
		}

		return static::$menu;
	}

	/**
	 * Returns user's menu track.
	 * @see MenuBot::getMenuState
	 *
	 * @param string $dialogId User or id.
	 *
	 * @return array|null
	 */
	public static function getMenuState(string $dialogId): ?array
	{
		return static::instanceMenu()->setDialogId($dialogId)->getState();
	}

	/**
	 * Saves user's menu track.
	 * @see MenuBot::saveMenuState
	 *
	 * @param string $dialogId User or chat id.
	 * @param array|null $menuState User menu track.
	 *
	 * @return void
	 */
	public static function saveMenuState(string $dialogId, ?array $menuState = null): void
	{
		static::instanceMenu()->setDialogId($dialogId);
		if ($menuState)
		{
			static::instanceMenu()->setState($menuState);
		}
		static::instanceMenu()->saveState();
	}

	/**
	 * Clears user's menu track.
	 *
	 * @param string $dialogId User or chat id.
	 *
	 * @return void
	 */
	public static function resetMenuState(string $dialogId): void
	{
		static::instanceMenu()->setDialogId($dialogId)->resetState()->saveState();
	}

	/**
	 * Checks if menu track has been completed.
	 *
	 * @param string $dialogId User or chat id.
	 *
	 * @return bool
	 */
	public static function isMenuTrackFinished(string $dialogId): bool
	{
		return static::instanceMenu()->setDialogId($dialogId)->isTrackFinished();
	}

	/**
	 * Checks if menu track has been started.
	 *
	 * @param string $dialogId User or chat id.
	 *
	 * @return bool
	 */
	public static function isMenuTrackStarted(string $dialogId): bool
	{
		return static::instanceMenu()->setDialogId($dialogId)->isTrackStarted();
	}

	/**
	 * Stops show menu to user.
	 *
	 * @param string $dialogId User or chat id.
	 *
	 * @return void
	 */
	public static function stopMenuTrack(string $dialogId): void
	{
		$menu = static::instanceMenu()->setDialogId($dialogId);
		if (!$menu->isTrackFinished())
		{
			$menu->stopTrack();

			$messageId = $menu->getMessageId();
			if ($messageId)
			{
				static::disableMessageButtons((int)$messageId);
			}
		}
	}

	/**
	 * Returns menu item.
	 *
	 * @return array|null
	 */
	protected static function getMenuItem(string $itemId): ?array
	{
		return static::instanceMenu()->getItem($itemId);
	}

	/**
	 * Display ITR menu.
	 * @see MenuBot::showMenu
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (string) DIALOG_ID Dialog id.
	 *   (int) MESSAGE_ID Previous message id.
	 *   (string) COMMAND
	 *   (string) COMMAND_PARAMS
	 *   (bool) FULL_REDRAW Drop previous menu block.
	 * ]
	 * </pre>.
	 *
	 * @return bool
	 */
	public static function showMenu(array $params): bool
	{
		$dialogId = (string)$params['DIALOG_ID'];

		$params['USER_LEVEL'] = static::getAccessLevel();
		$params['SUPPORT_LEVEL'] = \mb_strtoupper(static::getSupportLevel());

		static::instanceMenu()->setDialogId($dialogId)->getState();

		$previousMessageId = static::instanceMenu()->getMessageId();
		if (!$previousMessageId && isset($params['MESSAGE_ID']))
		{
			$previousMessageId = (int)$params['MESSAGE_ID'];
			static::instanceMenu()->setMessageId($previousMessageId);
		}

		$message = static::instanceMenu()->generateNextMessage($params);

		if (!empty($params['UNDELIVERED_MESSAGE']))
		{
			static::instanceMenu()->addStateData(['messages' => [$params['UNDELIVERED_MESSAGE']] ]);
		}

		if ($message)
		{
			$message['TO_USER_ID'] = static::getCurrentUser()->getId();
			$message['DIALOG_ID'] = $dialogId;
			$message['SYSTEM'] = 'N';
			$message['URL_PREVIEW'] = 'N';

			$finished = false;
			if (!isset($message['KEYBOARD']))
			{
				// menu finished
				if ($previousMessageId)
				{
					static::disableMessageButtons((int)$previousMessageId);
				}
				// reset menu if there are no buttons further
				static::instanceMenu()->resetState();
				$finished = true;
				$previousMessageId = null;
			}

			$fullRedraw = (
				isset($params['FULL_REDRAW'])
				&& $params['FULL_REDRAW'] === true
			);

			if ($previousMessageId && !$fullRedraw)
			{
				// replace current menu-message
				$message['EDIT_FLAG'] = 'N';
				static::updateMessage($previousMessageId, $message);
			}
			else
			{
				// move menu-message downwards as last one
				if ($previousMessageId && $fullRedraw)
				{
					static::dropMessage($previousMessageId);
				}

				$result = static::sendMessage($message);

				if (!$finished && $result[0])
				{
					static::instanceMenu()->setMessageId($result[0]);
				}
			}
		}

		static::instanceMenu()->saveState();

		return true;
	}

	/**
	 * Sends result of the user interaction with ITR menu to operator.
	 * @see MenuBot::sendMenuResult
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (string) DIALOG_ID Dialog id.
	 *   (int) MESSAGE_ID Message id.
	 *   (array) PARAMS Some extra action data.
	 * ]
	 * @return bool
	 */
	public static function sendMenuResult(array $params): bool
	{
		$menuState = static::getMenuState((string)$params['DIALOG_ID']);
		if (!$menuState)
		{
			return false;
		}

		$userId = (int)$params['FROM_USER_ID'];

		$level = 0;
		$blocks = static::instanceMenu()->printTrack();
		foreach ($blocks as $item)
		{
			$level ++;
			if ($level > 1)
			{
				$blocks[] = ["DELIMITER" => ['SIZE' => 200, 'COLOR' => "#c6c6c6"]];
			}
			$blocks[] = ["GRID" => [[
				"NAME" => $level.". ". static::replacePlaceholders($item['name'], $userId),
				"VALUE" => static::replacePlaceholders($item['value'], $userId),
				'COLOR' => "#239991",
				"DISPLAY" => "BLOCK",
				"WIDTH" => "500"
			]]];
		}

		$sendResult = static::clientMessageAdd([
			'BOT_ID' => static::getBotId(),
			'USER_ID' => $params['FROM_USER_ID'],
			'DIALOG_ID' => $params['DIALOG_ID'],
			'ATTACH' => Main\Web\Json::encode([
				'ID' => 1,
				'COLOR' => "#239991",
				'BLOCKS' => $blocks,
			]),
			'MESSAGE' => [
				'ID' => ($params['MESSAGE_ID'] ?: 0),
				'TEXT' => Loc::getMessage('NETWORK_MENU_RESULT'),
			],
			'PARAMS' => ($params['PARAMS'] ?? null),
		]);

		return $sendResult !== false;
	}

	/**
	 * @param array $messageFields
	 */
	protected static function isQuitMenuCommand(array $messageFields): bool
	{
		return (
			$messageFields['COMMAND_PARAMS'] === ItrMenu::MENU_EXIT_ID
			|| preg_match("/^".ItrMenu::MENU_EXIT_ID.";[a-z0-9;:_\/]+/i", $messageFields['COMMAND_PARAMS'])
		);
	}

	/**
	 * @param int $messageId
	 * @param array $messageFields
	 */
	public static function handleMenuCommand($messageId, $messageFields): void
	{
		if (!static::hasBotMenu())
		{
			return;
		}

		if (self::isQuitMenuCommand($messageFields))
		{
			static::instanceMenu()->getState($messageFields['DIALOG_ID']);

			$params = [
				Network::MESSAGE_PARAM_ALLOW_QUOTE => 'N'
			];
			if (
				preg_match("/^".ItrMenu::MENU_EXIT_ID.";([a-z0-9;:_\/]+)/i", $messageFields['COMMAND_PARAMS'], $commandParams)
				&& isset($commandParams, $commandParams[1])
			)
			{
				static::instanceMenu()->addStateData(['menu_action' => $commandParams[1]]);
				$params[MESSAGE_PARAM_MENU_ACTION] = $commandParams[1];
			}

			$forwardMessage = static::instanceMenu()->getForwardText();

			static::instanceMenu()->stopTrack();

			static::disableMessageButtons((int)$messageId, false);

			\CIMMessageParam::set($messageId, [
				Network::MESSAGE_PARAM_SENDING => 'Y',
				Network::MESSAGE_PARAM_SENDING_TIME => \time(),
			]);
			\CIMMessageParam::sendPull($messageId, [
				Network::MESSAGE_PARAM_SENDING,
				Network::MESSAGE_PARAM_SENDING_TIME,
				Network::MESSAGE_PARAM_KEYBOARD
			]);

			static::sendMenuResult([
				'FROM_USER_ID' => $messageFields['FROM_USER_ID'],
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'MESSAGE_ID' => $messageId,
				'PARAMS' => $params,
			]);

			if ($forwardMessage)
			{
				static::sendMessage([
					'TO_USER_ID' => $messageFields['FROM_USER_ID'],
					'DIALOG_ID' => $messageFields['DIALOG_ID'],
					'MESSAGE' => $forwardMessage,
					'SYSTEM' => 'Y',
					'URL_PREVIEW' => 'N',
					'PARAMS' => [Network::MESSAGE_PARAM_ALLOW_QUOTE => 'N'],
				]);
			}
		}
		else
		{
			static::showMenu([
				'DIALOG_ID' => $messageFields['DIALOG_ID'],
				'COMMAND' => $messageFields['COMMAND'],
				'COMMAND_PARAMS' => $messageFields['COMMAND_PARAMS'],
				'MESSAGE_ID' => (int)$messageId,
			]);
		}
	}
}
<?php declare(strict_types=1);

namespace Bitrix\Imbot\Bot;

/**
 * Common interface for chat bots.
 *
 * @package Bitrix\Imbot\Bot
 */
interface ChatBot
{
	/**
	 * Register bot at portal.
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public static function register(array $params = []);

	/**
	 * Unregister bot at portal.
	 *
	 * @return bool
	 */
	public static function unRegister();

	/**
	 * Returns registered bot Id.
	 *
	 * @return int
	 */
	public static function getBotId(): int;

	/**
	 * Event handler when bot join to chat.
	 * @see \Bitrix\Im\Bot::onJoinChat
	 * Method registers at bot field `b_im_bot.METHOD_WELCOME_MESSAGE`
	 *
	 * @param string $dialogId
	 * @param array $joinFields
	 *
	 * @return bool
	 */
	public static function onChatStart($dialogId, $joinFields);

	/**
	 * Event handler on bot remove.
	 * @see \Bitrix\Im\Bot::unRegister
	 * Method registers at bot field `b_im_bot.METHOD_BOT_DELETE`
	 *
	 * @param int|null $bodId
	 *
	 * @return bool
	 */
	public static function onBotDelete($bodId = null);

	/**
	 * Event handler on message add.
	 * @see \Bitrix\Im\Bot::onMessageAdd
	 * Method registers at bot field `b_im_bot.METHOD_MESSAGE_ADD`
	 *
	 * @param int $messageId Outgoing message Id.
	 * @param array $messageFields Message fields array.
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields);

	/**
	 * Event handler on answer add.
	 * @see \Bitrix\ImBot\Controller::sendToBot
	 *
	 * @param string $command
	 * @param array $params
	 *
	 * @return array
	 */
	public static function onAnswerAdd($command, $params);

	/**
	 * Event handler on command add.
	 * @see \Bitrix\Im\Command::onCommandAdd
	 * Method registers at bot field `b_im_command.METHOD_COMMAND_ADD`
	 *
	 * @param int $messageId Incomming message Id.
	 * @param array $messageFields Message fields array.
	 *
	 * @return bool
	 */
	public static function onCommandAdd($messageId, $messageFields);

	/**
	 * @return \Bitrix\ImBot\Error
	 */
	public static function getError();

	/**
	 * todo: Add method `onMessageUpdate` signature into interface.
	 * @see \Bitrix\Im\Bot::onMessageUpdate
	 * Method registers at bot field `b_im_bot.METHOD_MESSAGE_UPDATE`
	 */

	/**
	 * todo: Add method `onMessageDelete` signature into interface.
	 * @see \Bitrix\Im\Bot::onMessageDelete
	 * Method registers at bot field `b_im_bot.METHOD_MESSAGE_DELETE`
	 */

	/**
	 * todo: Add method `onStartWriting` signature into interface.
	 * @see \Bitrix\ImBot\Event::onStartWriting
	 */
}
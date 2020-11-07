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
	 * Event handler when bot join to chat.
	 *
	 * @param string $dialogId
	 * @param array $joinFields
	 *
	 * @return bool
	 */
	public static function onChatStart($dialogId, $joinFields);

	/**
	 * Event handler on bot remove.
	 *
	 * @param int|null $bodId
	 *
	 * @return bool
	 */
	public static function onBotDelete($bodId = null);


	/**
	 * Event handler on message add.
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onMessageAdd($messageId, $messageFields);

	/**
	 * Event handler on answer add.
	 *
	 * @param string $command
	 * @param array $params
	 *
	 * @return array
	 */
	public static function onAnswerAdd($command, $params);

	/**
	 * Event handler on command add.
	 *
	 * @param int $messageId
	 * @param array $messageFields
	 *
	 * @return bool
	 */
	public static function onCommandAdd($messageId, $messageFields);


	/**
	 * Returns registered bot Id.
	 *
	 * @return int
	 */
	public static function getBotId();

	/**
	 * Is bot enabled.
	 *
	 * @return bool
	 */
	public static function isEnabled();
}
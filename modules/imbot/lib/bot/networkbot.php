<?php declare(strict_types=1);

namespace Bitrix\Imbot\Bot;

/**
 * Common interface for chat bots.
 *
 * @package Bitrix\Imbot\Bot
 */
interface NetworkBot extends ChatBot
{
	/**
	 * Unregister bot at portal.
	 *
	 * @param string $code Open Line Id.
	 * @param bool $notifyController Send unregister notification request to controller.
	 *
	 * @return bool
	 */
	public static function unRegister($code = '', $notifyController = true);

	/**
	 * Event handler on answer add.
	 * Alias for @see \Bitrix\Imbot\Bot\ChatBot::onAnswerAdd
	 *
	 * @param string $command Text command alias.
	 * @param array $params Command arguments.
	 *
	 * @return \Bitrix\ImBot\Error|array
	 */
	public static function onReceiveCommand($command, $params);
}
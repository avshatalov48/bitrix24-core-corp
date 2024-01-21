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
	 * Returns OL code.
	 * @return string
	 */
	public static function getBotCode(): string;

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
	 * Register bot's command.
	 * @return bool
	 */
	public static function registerCommands(): bool;

	/**
	 * Register bot's command.
	 * @return bool
	 */
	public static function registerApps(): bool;

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

	/**
	 * Returns command's property list.
	 *
	 * @return array{handler: string, visible: bool, context: string}[]
	 */
	public static function getCommandList(): array;

	/**
	 * Returns app's property list.
	 * @return array{command: string, icon: string, js: string, context: string, lang: string}[]
	 */
	public static function getAppList(): array;

	/**
	 * Allows updating bot fields (name, desc, avatar, welcome mess) using data from incoming message.
	 *
	 * @return bool
	 */
	public static function isNeedUpdateBotFieldsAfterNewMessage(): bool;

	/**
	 * Allows updating bot's avatar using data from incoming message.
	 *
	 * @return bool
	 */
	public static function isNeedUpdateBotAvatarAfterNewMessage(): bool;

	/**
	 * Handler for "im:OnSessionVote" event.
	 * @see \Bitrix\ImBot\Event::onSessionVote
	 *
	 * @param array $params Event arguments.
	 *
	 * @return bool
	 */
	public static function onSessionVote(array $params): bool;

	/**
	 * Returns the limit for additional questions.
	 * @param int|null $botId
	 * @return int
	 * -1 - Functional is disabled,
	 * 0 - There is no limit,
	 * 1 - Only one session allowed,
	 * n - Max number for sessions allowed.
	 */
	public static function getQuestionLimit(?int $botId = null): int;

	/**
	 * Permits adding new question.
	 * @param int|null $botId
	 * @return bool
	 */
	public static function allowAdditionalQuestion(?int $botId = null): bool;
}
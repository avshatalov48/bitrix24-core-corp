<?php declare(strict_types=1);

namespace Bitrix\Imbot\Bot;

/**
 * Common interface for support bots.
 *
 * @package Bitrix\Imbot\Bot
 */
interface SupportBot extends NetworkBot
{
	/**
	 * Detects client's support level.
	 * @return string
	 */
	public static function getSupportLevel(): string;

	/**
	 * Detects client's access level.
	 * @return string
	 */
	public static function getAccessLevel(): string;

	/**
	 * Return name of the bot.
	 * @return string
	 */
	public static function getBotName(): string;

	/**
	 * Return bot shot description.
	 * @return string
	 */
	public static function getBotDesc(): string;

	/**
	 * Returns url of the bot avatar picture.
	 * @return string
	 */
	public static function getBotAvatar(): string;

	/**
	 * Returns phrase by the code.
	 *
	 * @param string $code
	 *
	 * @return string|null
	 */
	public static function getMessage(string $code): ?string;

	/**
	 * Loads bot settings from controller.
	 *
	 * @param array $params Command arguments.
	 *
	 * @return array|null
	 */
	public static function getBotSettings(array $params = []): ?array;
}
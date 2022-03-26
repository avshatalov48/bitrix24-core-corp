<?php declare(strict_types=1);

namespace Bitrix\Imbot\Bot;

use Bitrix\ImBot\ItrMenu;

/**
 * Common interface for chat menu.
 *
 * @package Bitrix\Imbot\Bot
 */
interface MenuBot
{
	/**
	 * Checks if bot has ITR menu.
	 *
	 * @return bool
	 */
	public static function hasBotMenu(): bool;

	/**
	 * Returns stored data for ITR menu.
	 *
	 * @return ItrMenu|null
	 */
	public static function getBotMenu(): ?ItrMenu;

	/**
	 * Returns user's menu track.
	 *
	 * @param string $dialogId User or chat id.
	 *
	 * @return array|null
	 */
	public static function getMenuState(string $dialogId): ?array;

	/**
	 * Saves user's menu track.
	 *
	 * @param string $dialogId User or chat id.
	 * @param array $menuState User menu track.
	 *
	 * @return void
	 */
	public static function saveMenuState(string $dialogId, array $menuState): void;

	/**
	 * Display ITR menu.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (int) BOT_ID Bot id.
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
	public static function showMenu(array $params): bool;

	/**
	 * Sends result of the user interaction with ITR menu to operator.
	 *
	 * @param array $params Command arguments.
	 * <pre>
	 * [
	 *   (int) BOT_ID Bot id.
	 *   (string) DIALOG_ID Dialog id.
	 *   (int) MESSAGE_ID Message id.
	 * ]
	 * </pre>
	 *
	 * @return bool
	 */
	public static function sendMenuResult(array $params): bool;
}
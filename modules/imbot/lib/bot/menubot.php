<?php declare(strict_types=1);

namespace Bitrix\Imbot\Bot;

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
	public static function hasBotMenu();

	/**
	 * Returns stored data for ITR menu.
	 *
	 * @return array
	 */
	public static function getBotMenu();

	/**
	 * Returns user's menu track.
	 *
	 * @param int $dialogId User id.
	 *
	 * @return array|null
	 */
	public static function getMenuState(int $dialogId);

	/**
	 * Saves user's menu track.
	 *
	 * @param int $dialogId User id.
	 * @param array $menuState User menu track.
	 *
	 * @return void
	 */
	public static function saveMenuState(int $dialogId, array $menuState);

	/**
	 * Display ITR menu.
	 *
	 * @param array $params Command arguments. <pre>{
	 *   (int) BOT_ID Bot id.
	 *   (int) DIALOG_ID Dialog id.
	 *   (int) MESSAGE_ID Previous message id.
	 *   (string) COMMAND
	 *   (string) COMMAND_PARAMS
	 *   (bool) FULL_REDRAW Drop previous menu block.
	 * ]
	 * </pre>.
	 *
	 * @return array|null
	 */
	public static function showMenu(array $params);

	/**
	 * Sends result of the user interaction with ITR menu to operator.
	 *
	 * @param array $params Command arguments. <pre>{
	 *   (int) BOT_ID Bot id.
	 *   (int) DIALOG_ID Dialog id.
	 *   (int) MESSAGE_ID Message id.
	 * ]
	 * @param array|null $menuState Saved user track.
	 *
	 * @return bool
	 */
	public static function sendMenuResult(array $params, ?array $menuState = null);
}
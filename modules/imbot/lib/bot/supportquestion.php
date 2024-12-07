<?php declare(strict_types=1);

namespace Bitrix\Imbot\Bot;

/**
 * Multidialog interface for support bots.
 *
 * @package Bitrix\Imbot\Bot\Mixin
 */
interface SupportQuestion
{
	/**
	 * Increments global for portal question counter.
	 * @return int
	 */
	public static function incrementGlobalQuestionCounter(): int;

	/**
	 * Tells true if additional question functional is enabled.
	 * @return bool
	 */
	public static function isEnabledQuestionFunctional(): bool;

	/**
	 * Returns configuration flags for client.
	 * @return array
	 */
	public static function getSupportQuestionConfig(): array;

	/**
	 * Starts new question dialog.
	 * @return int
	 */
	public static function addSupportQuestion(): int;

	/**
	 * Returns the question dialog list and perfoms searching by question dialog title.
	 * @param array $params Query parameters.
	 * @return array
	 */
	public static function getSupportQuestionList(array $params): array;

	public static function getQuestionsCount(?int $botId = null): int;

	public static function getQuestionsWithUnreadMessages(?int $botId = null): array;
}
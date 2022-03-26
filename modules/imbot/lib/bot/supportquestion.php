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
	 * Permits adding new question.
	 * @return bool
	 */
	public static function allowAdditionalQuestion(): bool;

	/**
	 * Returns the limit for additional questions.
	 * @return int
	 * -1 - Functional is disabled,
	 * 0 - There is no limit,
	 * 1 - Only one session allowed,
	 * n - Max number for sessions allowed.
	 */
	public static function getQuestionLimit(): int;

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
}
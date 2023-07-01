<?php

namespace Bitrix\Crm\MessageSender;

use Bitrix\Main\Result;

/**
 * Interface ICanSendMessage
 * @package Bitrix\Crm\MessageSender
 * @internal
 */
interface ICanSendMessage
{
	/**
	 * @return string
	 */
	public static function getSenderCode(): string;

	/**
	 * @return bool
	 */
	public static function isAvailable(): bool;

	/**
	 * @return bool
	 */
	public static function isConnected(): bool;

	/**
	 * @return string|array|null
	 */
	public static function getConnectUrl();

	/**
	 * @return array
	 */
	public static function getUsageErrors(): array;

	/**
	 * @param Array<string, \Bitrix\Crm\MessageSender\Channel\Correspondents\To[]> $toListByType
	 * @param int $userId
	 * @return Channel[]
	 */
	public static function getChannelsList(array $toListByType, int $userId): array;

	/**
	 * Checks whether it is possible to send a message via the channel.
	 *
	 * @param Channel $channel
	 * @return Result If send is not possible, contains errors with reasons (e.g. not configured, not enough credits, ...)
	 */
	public static function canSendMessageViaChannel(Channel $channel): Result;

	/**
	 * @return bool
	 */
	public static function canSendMessage();

	/**
	 * @param array $messageFields
	 * @return Result|false
	 */
	public static function sendMessage(array $messageFields);

	/**
	 * @param array $options
	 * @param array $commonOptions
	 * @return array
	 */
	public static function makeMessageFields(array $options, array $commonOptions): array;
}

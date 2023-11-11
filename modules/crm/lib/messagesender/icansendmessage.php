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
	 * If all the necessary modules installed
	 *
	 * @return bool
	 */
	public static function canUse(): bool;

	/**
	 * If the sender available on current tariff, with current modules installed, and in current region
	 *
	 * @return bool
	 */
	public static function isAvailable(): bool;

	/**
	 * If the sender fully configured by the user
	 *
	 * @return bool
	 */
	public static function isConnected(): bool;

	/**
	 * @return string|array|null
	 */
	public static function getConnectUrl();

	/**
	 * @return string[]
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
	 * @param array{
	 *     USER_ID: int,
	 *     PHONE_NUMBER: ?string,
	 *     EMAIL: ?string,
	 *     ADDITIONAL_FIELDS: array{
	 *         BINDINGS: array{OWNER_TYPE_ID: int, OWNER_ID: int},
	 *         ROOT_SOURCE: ?array{ENTITY_TYPE_ID: int, ENTITY_ID: int},
	 *         ADDRESS_SOURCE: ?array{ENTITY_TYPE_ID: int, ENTITY_ID: int},
	 *     },
	 * } $commonOptions
	 * @return array
	 */
	public static function makeMessageFields(array $options, array $commonOptions): array;
}

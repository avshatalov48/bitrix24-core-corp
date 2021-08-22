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

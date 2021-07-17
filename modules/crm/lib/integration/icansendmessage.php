<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Main\Result;

/**
 * Interface ICanSendMessage
 * @package Bitrix\Crm\Integration
 */
interface ICanSendMessage
{
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

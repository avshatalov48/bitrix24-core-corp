<?php

namespace Bitrix\Crm\MessageSender;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Class MessageSender
 * @package Bitrix\Crm\MessageSender
 * @internal
 */
final class MessageSender
{
	/**
	 * @param array $sendersOptions
	 * @param array $options
	 * @return Result
	 */
	public static function send(array $sendersOptions, array $options = []): Result
	{
		$sender = SenderPicker::getCurrentSender();
		if (!$sender)
		{
			return (new Result())->addError(new Error('Sender has not been set up'));
		}

		if (!isset($sendersOptions[$sender::getSenderCode()]))
		{
			return (new Result())->addError(new Error('Unexpected sender code'));
		}
		else
		{
			$senderOptions = $sendersOptions[$sender::getSenderCode()];
		}

		if (!$sender::canSendMessage())
		{
			return (new Result())->addError(new Error('Sender is not available'));
		}

		$result = $sender::sendMessage(
			$sender::makeMessageFields(
				$senderOptions,
				$options['COMMON_OPTIONS'] ?? []
			)
		);

		if ($result instanceof Result)
		{
			return $result;
		}

		return (new Result())->addError(new Error('Message has not been sent'));
	}
}

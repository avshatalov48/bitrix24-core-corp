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
		if (!$sendersOptions)
		{
			return (new Result())->addError(new Error('Sender options have not been specified'));
		}

		$currentSender = SenderPicker::getCurrentSender();
		if ($currentSender && isset($sendersOptions[$currentSender::getSenderCode()]))
		{
			$sender = $currentSender;
			$senderOptions = $sendersOptions[$sender::getSenderCode()];
		}
		else
		{
			$sender = SenderPicker::getSenderByCode(key($sendersOptions));
			$senderOptions = current($sendersOptions);
		}

		if (!$sender)
		{
			return (new Result())->addError(new Error('Sender has not been found'));
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

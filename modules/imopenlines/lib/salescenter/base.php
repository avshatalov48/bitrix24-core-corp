<?php
namespace Bitrix\ImOpenLines\SalesCenter;

use \Bitrix\Main\Error,
	\Bitrix\Main\Result;
use \Bitrix\ImOpenLines\Im;

/**
 * Class SalesCenter
 * @package Bitrix\ImOpenLines\SalesCenter
 */
abstract class Base
{
	protected $chatId = 0;
	protected $fieldsMessage = [];

	public static function normalizeChatId($chatId = 0)
	{
		if (mb_strpos($chatId, 'chat') === 0)
		{
			$chatId = (int)mb_substr($chatId, 4);
		}

		return $chatId;
	}

	/**
	 * Base constructor.
	 * @param int $chatId
	 */
	public function __construct($chatId = 0)
	{
		$this->chatId = $chatId;
	}

	/**
	 * @param array $params
	 * @return $this
	 */
	public function setMessage($params = [])
	{
		$this->fieldsMessage = $params;

		return $this;
	}

	/**
	 * @return Result
	 */
	public function isValidSendMessage(): Result
	{
		$result = new Result();

		if($this->chatId < 1 || !is_int($this->chatId))
		{
			$result->addError(new Error(
				'Not specified, or invalid chat ID is specified'
			));
		}

		if(empty($this->fieldsMessage) || !is_array($this->fieldsMessage))
		{
			$result->addError(new Error(
				'The data of the IM message being sent was not transmitted'
			));
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function sendMessage(): Result
	{
		$result = $this->isValidSendMessage();

		if($result->isSuccess())
		{
			$messageId = Im::addMessage($this->fieldsMessage);

			if($messageId)
			{
				$result->setData(['messageId' => $messageId]);
			}
			else
			{
				global $APPLICATION;
				$result->addError(new Error($APPLICATION->LAST_ERROR));
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function send(): Result
	{
		return $this->sendMessage();
	}
}
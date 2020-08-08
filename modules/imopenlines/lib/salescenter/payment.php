<?php
namespace Bitrix\ImOpenLines\SalesCenter;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Result;
use \Bitrix\ImConnector\InteractiveMessage\Output;

/**
 * Class SalesCenter
 * @package Bitrix\ImOpenLines\SalesCenter
 */
class Payment extends Base
{
	protected $data = [];

	/**
	 * @param array $data
	 * @return Payment
	 */
	public function setData($data = []): Payment
	{
		$this->data = $data;

		return $this;
	}

	/**
	 * @return bool
	 */
	protected function isValidPayment(): bool
	{
		$result = false;

		if(
			!empty($this->data) &&
			is_array($this->data)
		)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function send(): Result
	{
		if(
			Loader::includeModule('imconnector') &&
			$this->isValidPayment())
		{
			Output::getInstance($this->chatId)->setPaymentData($this->data);
		}

		return $this->sendMessage();
	}
}
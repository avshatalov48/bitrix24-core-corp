<?php
namespace Bitrix\ImConnector\Provider\LiveChat;

use Bitrix\ImConnector\Result,
	Bitrix\ImConnector\Provider\Base;

class Input extends Base\Input
{
	/**
	 * @return Result
	 */
	protected function receivingMessage(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function receivingStatusDelivery(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function receivingStatusReading(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function receivingError(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function receivingStatusBlock(): Result
	{
		return $this->receivingBase();
	}

	/**
	 * @return Result
	 */
	protected function deactivateConnector(): Result
	{
		return $this->receivingBase();
	}
}

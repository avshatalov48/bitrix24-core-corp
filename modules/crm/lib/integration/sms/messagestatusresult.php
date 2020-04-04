<?php

namespace Bitrix\Crm\Integration\Sms;

use Bitrix\Main\Result;

class MessageStatusResult extends Result
{
	protected $messageId;
	protected $statusCode;
	protected $statusText;

	/**
	 * @param string $id
	 * @return $this
	 */
	public function setMessageId($id)
	{
		$this->messageId = (string)$id;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getMessageId()
	{
		return $this->messageId;
	}

	/**
	 * @param int|string $statusCode
	 * @return $this
	 */
	public function setStatusCode($statusCode)
	{
		$this->statusCode = $statusCode;
		return $this;
	}

	/**
	 * @return int|string
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

	/**
	 * @param string $statusText
	 * @return $this
	 */
	public function setStatusText($statusText)
	{
		$this->statusText = (string)$statusText;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getStatusText()
	{
		return $this->statusText;
	}
}
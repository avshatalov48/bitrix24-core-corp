<?php

namespace Bitrix\Crm\Integration\Sms;

use Bitrix\Main\Result;

class SendMessageResult extends Result
{
	protected $messageId;

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
}
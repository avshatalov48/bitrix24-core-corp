<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Message;

use Bitrix\AI\Chatbot\Enum\MessageType;
use Bitrix\AI\Chatbot\Message\Parameter\DefaultParameter;

class SystemMessage extends Message
{
	protected MessageType $type = MessageType::System;

	public function __construct(string $content)
	{
		parent::__construct($content);
		$this->addParam(new DefaultParameter('isSystem', true));
	}

}
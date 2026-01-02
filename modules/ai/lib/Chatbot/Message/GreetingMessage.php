<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Message;

use Bitrix\AI\Chatbot\Enum\MessageType;

class GreetingMessage extends Message
{
	protected MessageType $type = MessageType::Greeting;
}
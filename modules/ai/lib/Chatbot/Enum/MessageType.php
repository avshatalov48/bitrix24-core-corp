<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Enum;

enum MessageType: string
{
	case Default = 'Default';
	case System = 'System';
	case Greeting = 'Greeting';
	case ButtonClicked = 'ButtonClicked';
	case GreetingSiteWithAI = 'GreetingSiteWithAi';
}

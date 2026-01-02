<?php declare(strict_types=1);

namespace Bitrix\AI\Chatbot\Enum;

enum ChatInputStatus: string
{
	case Lock = 'Lock';
	case Writing = 'Writing';
	case Unlock = 'Unlock';
}
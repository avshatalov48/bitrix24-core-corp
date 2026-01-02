<?php

namespace Bitrix\HumanResources\Type;

/**
 * An analogue for constants at Bitrix\Im\V2\Chat
 * for breaking a need to include im module
 */
enum ImChatType: string
{
	case ImTypeChat = 'C';
	case ImTypeOpen = 'O';
	case ImTypeChannel = 'N';
	case ImTypeOpenChannel = 'J';
}
<?php

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Internals\Trait\ValuesTrait;

enum NodeChatType: string
{
	case Chat = 'CHAT';
	case Channel = 'CHANNEL';
	case Collab = 'COLLAB';
}
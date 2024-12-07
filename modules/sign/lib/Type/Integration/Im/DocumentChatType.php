<?php

namespace Bitrix\Sign\Type\Integration\Im;

enum DocumentChatType: int
{
	case WAIT = 1;
	case READY = 2;
	case STOPPED = 3;
}
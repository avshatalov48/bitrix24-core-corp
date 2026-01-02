<?php

namespace Bitrix\Crm\Copilot\AiQueueBuffer\Enum;

enum Status: int
{
	case Waiting = 1;
	case Progress = 2;
}

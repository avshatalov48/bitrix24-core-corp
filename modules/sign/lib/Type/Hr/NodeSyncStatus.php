<?php

namespace Bitrix\Sign\Type\Hr;

enum NodeSyncStatus: int
{
	case Waiting = 0;
	case Sync = 1;
	case Done = 2;
}

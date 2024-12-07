<?php

namespace Bitrix\Tasks\Flow\Kanban;

use Bitrix\Tasks\Kanban\StagesTable;

enum SystemType: string
{
	case NEW = StagesTable::SYS_TYPE_NEW;
	case PROGRESS = StagesTable::SYS_TYPE_PROGRESS;
	case REVIEW = StagesTable::SYS_TYPE_REVIEW;
	case COMPLETED = StagesTable::SYS_TYPE_FINISH;
}

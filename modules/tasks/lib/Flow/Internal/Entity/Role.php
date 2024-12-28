<?php

namespace Bitrix\Tasks\Flow\Internal\Entity;

enum Role: string
{
	case CREATOR = 'C';
	case OWNER = 'O';
	case TASK_CREATOR = 'TC';
	case MANUAL_DISTRIBUTOR = 'MD';
	case QUEUE_ASSIGNEE = 'QA';
	case HIMSELF_ASSIGNED = 'HM';
}
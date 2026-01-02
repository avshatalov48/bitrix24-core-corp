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
	case IMMUTABLE_ASSIGNED = 'IM';

	public static function getResponsibleRoles(): array
	{
		return [
			self::MANUAL_DISTRIBUTOR->value,
			self::QUEUE_ASSIGNEE->value,
			self::HIMSELF_ASSIGNED->value,
			self::IMMUTABLE_ASSIGNED->value,
		];
	}
}
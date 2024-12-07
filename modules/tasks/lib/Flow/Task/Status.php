<?php

namespace Bitrix\Tasks\Flow\Task;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Tasks\Flow\Internal\FlowTaskTable;
use Bitrix\Tasks\Internals\Task;
use Bitrix\Tasks\Internals\TaskTable;

final class Status extends Task\Status
{
	public const FLOW_PENDING = 'PENDING';
	public const FLOW_AT_WORK = 'AT_WORK';
	public const FLOW_COMPLETED = 'COMPLETED';

	public const STATUS_MAP = [
		self::FLOW_PENDING => [
			self::NEW,
			self::PENDING,
		],
		self::FLOW_AT_WORK => [
			self::IN_PROGRESS,
		],
		self::FLOW_COMPLETED => [
			self::SUPPOSEDLY_COMPLETED,
			self::COMPLETED,
		],
	];

	public const STATUSES_CHANGING_ACTIVITY = [
		self::IN_PROGRESS,
		self::SUPPOSEDLY_COMPLETED,
		self::DEFERRED,
		self::COMPLETED,
	];
}

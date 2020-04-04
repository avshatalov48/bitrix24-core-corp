<?php

namespace Bitrix\Tasks\Internals\Counter;

class Type
{
	const TYPE_NEW = 0x0100000;    // not viewed yet by user
	const TYPE_IN_PROGRESS = 0x0300000;    // All except completed/deferred
	const TYPE_COMPLETED = 0x0400000;    // CTasks::STATE_COMPLETED
	const TYPE_DEFERRED = 0x0500000;    // CTasks::STATE_DEFERRED
	const TYPE_EXPIRED = 0x0600000;    // CTasks::METASTATE_EXPIRED
	const TYPE_EXPIRED_CANDIDATES = 0x0900000;    // <= 24h to deadline
	const TYPE_ATTENTION = 0x0700000;    // depends on role
	const TYPE_WAIT_CTRL = 0x0800000;    // CTasks::STATE_SUPPOSEDLY_COMPLETED
	const TYPE_WO_DEADLINE = 0x0A00000;    // tasks without DEADLINE, created NOT by the current user
	const TYPE_ALL = 0x0B00000;
}
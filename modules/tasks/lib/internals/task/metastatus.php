<?php

namespace Bitrix\Tasks\Internals\Task;

abstract class MetaStatus extends Base
{
	public const EXPIRED_SOON = -3;
	public const UNSEEN = -2;
	public const EXPIRED = -1;
}
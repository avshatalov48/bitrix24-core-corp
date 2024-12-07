<?php

namespace Bitrix\AI\Engine;

use Bitrix\AI\QueueJob;

interface IQueue
{
	/**
	 * Returns Queue Job instance for current instance.
	 *
	 * @return QueueJob
	 */
	public function getQueueJob(): QueueJob;
}

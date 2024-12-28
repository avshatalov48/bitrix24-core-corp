<?php
declare(strict_types=1);

namespace Bitrix\Disk\Realtime\Channels;

use Bitrix\Pull;

abstract class Channel
{
	abstract public function getName(): string;

	final public function getPullModel(): Pull\Model\Channel
	{
		return Pull\Model\Channel::createWithTag($this->getName());
	}
}
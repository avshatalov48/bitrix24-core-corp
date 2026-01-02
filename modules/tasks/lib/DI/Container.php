<?php

declare(strict_types=1);

namespace Bitrix\Tasks\DI;

use Bitrix\Tasks\Provider\TaskList;

final class Container extends AbstractContainer
{
	public function getTaskProvider(): TaskList
	{
		return $this->get(TaskList::class);
	}
}

<?php

namespace Bitrix\Tasks\Copy;

use Bitrix\Tasks\Copy\Implement\RegularTask;

class RegularTaskManager extends TaskManager
{
	public function getImplementerClass(): string
	{
		return RegularTask::class;
	}
}
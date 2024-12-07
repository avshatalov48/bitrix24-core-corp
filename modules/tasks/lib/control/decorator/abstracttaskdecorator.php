<?php

namespace Bitrix\Tasks\Control\Decorator;

use Bitrix\Tasks\Control\Task;

abstract class AbstractTaskDecorator extends Task
{
	public function __construct(protected Task $source)
	{

	}
}
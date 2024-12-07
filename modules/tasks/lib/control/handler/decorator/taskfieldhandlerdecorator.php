<?php

namespace Bitrix\Tasks\Control\Handler\Decorator;

use Bitrix\Tasks\Control\Handler\TaskFieldHandler;

abstract class TaskFieldHandlerDecorator extends TaskFieldHandler
{
	public function __construct(protected TaskFieldHandler $source)
	{

	}
}
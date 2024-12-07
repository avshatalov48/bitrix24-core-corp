<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

use Bitrix\Bizproc\Automation\Engine\Robot;
use Bitrix\Tasks\AbstractCommand;

abstract class AbstractRobotCommand extends AbstractCommand
{
	protected string $name;

	abstract public function isUserSensitive(): bool;

	public function getName(): string
	{
		$this->name ??= Robot::generateName();
		return $this->name;
	}
}
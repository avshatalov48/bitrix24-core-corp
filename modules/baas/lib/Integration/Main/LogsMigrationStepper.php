<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Main;

use Bitrix\Main\Update\Stepper;

class LogsMigrationStepper extends Stepper
{
	public function execute(array &$option)
	{
		return Stepper::FINISH_EXECUTION;
	}
}

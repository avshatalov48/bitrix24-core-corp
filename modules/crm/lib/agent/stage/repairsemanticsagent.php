<?php

namespace Bitrix\Crm\Agent\Stage;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Agent\Stage\Service;
use Bitrix\Crm\AgentUtil\ProcessEntitiesStorage;

final class RepairSemanticsAgent extends AgentBase
{
	public static function doRun(): bool
	{
		$storage = new ProcessEntitiesStorage(self::class);

		return (new Service\SemanticsRepair($storage))
			->execute()
		;
	}
}

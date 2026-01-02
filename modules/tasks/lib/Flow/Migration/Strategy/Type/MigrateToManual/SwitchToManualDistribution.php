<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Strategy\Type\MigrateToManual;

use Bitrix\Main\DI\ServiceLocator;

class SwitchToManualDistribution extends AbstractMigrateToManual
{
	protected function notify(int $flowId): void
	{
		$notificationService = ServiceLocator::getInstance()->get('tasks.flow.notification.service');
		$notificationService->onSwitchToManualDistribution($flowId);
	}
}

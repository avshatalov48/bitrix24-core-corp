<?php

namespace Bitrix\Crm\Agent\Sign\B2e;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningDoneTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningStartedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningStoppedTrigger;
use Bitrix\Crm\Service\Container;

final class AddDefaultTriggersAgent extends AgentBase
{
	public const IS_DISABLED = true;
	private const DEFAULT_TRIGGERS = [
		SigningDoneTrigger::class => 'SENT',
		SigningStartedTrigger::class => 'SEMISIGNED',
		SigningStoppedTrigger::class => 'SIGNED',
	];

	public static function doRun(): bool
	{
		if (self::IS_DISABLED)
		{
			return false;
		}

		$typeService = Container::getInstance()->getSignB2eTypeService();
		$statusService = Container::getInstance()->getSignB2eStatusService();
		$triggerService = Container::getInstance()->getSignB2eTriggerService();

		if (!$typeService->isCreated())
		{
			return false;
		}

		$defaultCategoryId = $typeService->getDefaultCategoryId();
		if (!$defaultCategoryId)
		{
			return false;
		}

		if ($triggerService->isTriggersCreated())
		{
			return false;
		}

		$triggers = $statusService->makeTriggerNames($defaultCategoryId, self::DEFAULT_TRIGGERS);
		$triggerService->addTriggers($triggers);

		return true;
	}
}

<?php

namespace Bitrix\Crm\Service\Timeline\Item\Mixin;

use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Settings\Crm;

trait CopilotButtonTrait
{
	final public function isItemHashValid(int $activityId, Context $context): bool
	{
		return (new Orchestrator())->findPossibleFillFieldsTarget($activityId)?->getHash() === $context->getIdentifier()->getHash();
	}

	final public function fillAILicenceAttributes(): self
	{
		$this->props = [
			'data-activity-id' => $this->activityId,
		];

		if (!AIManager::isAILicenceAccepted($this->context->getUserId()))
		{
			if (Crm::isBox())
			{
				$this->jsEventAction->addActionParamBoolean('isCopilotAgreementNeedShow', true);
			}
			elseif (!$this->context->getUserPermissions()->isAdmin())
			{
				$this->props = [
					'data-bitrix24-license-feature' => AIManager::AI_LICENCE_FEATURE_NAME,
				];
			}
		}

		return $this;
	}
}

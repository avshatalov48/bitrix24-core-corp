<?php

namespace Bitrix\Crm\Automation\Engine;

use Bitrix\Crm\Automation\Trigger\Entity\TriggerObject;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class TemplatesTunnel extends \Bitrix\Bizproc\Automation\Engine\TemplatesTunnel
{
	public function copyTriggers(array $triggerNames): Result
	{
		$documentService = \CBPRuntime::getRuntime()->getDocumentService();
		$target = $documentService->createAutomationTarget($this->srcTemplate->getDocumentType());

		/** @var \Bitrix\Crm\Automation\Trigger\Entity\TriggerObject[] $triggersToCopy */
		$triggersToCopy = array_filter(
			$target->getTriggerObjects([$this->srcTemplate->getDocumentStatus()]),
			fn ($trigger) => in_array($trigger->getId(), $triggerNames, true),
		);

		$copiedTriggers = [];
		$deniedTriggers = [];
		foreach ($triggersToCopy as $trigger)
		{
			if (!array_key_exists($trigger->getCode(), $this->availableTriggers))
			{
				$deniedTriggers[] = $trigger;
				continue;
			}

			$newTrigger = new TriggerObject();

			$entityTypeId = \CCrmOwnerType::ResolveID($this->dstTemplate->getDocumentType()[2]);

			$newTrigger->setName($trigger->getName());
			$newTrigger->setCode($trigger->getCode());
			$newTrigger->setEntityTypeId($entityTypeId);
			$newTrigger->setEntityStatus($this->dstTemplate->getDocumentStatus());
			$newTrigger->setApplyRules($trigger->getApplyRules());

			$newTrigger->save();
			$copiedTriggers[] = $newTrigger;
		}

		$result = new Result();
		$result->setData([
			'copied' => $copiedTriggers,
			'denied' => $deniedTriggers,
			'original' => $triggersToCopy,
		]);

		return $result;
	}
}
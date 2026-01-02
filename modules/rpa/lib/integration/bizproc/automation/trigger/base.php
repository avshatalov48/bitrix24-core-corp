<?php
namespace Bitrix\Rpa\Integration\Bizproc\Automation\Trigger;

use Bitrix\Bizproc\Automation\Trigger\BaseTrigger;
use Bitrix\Main;
use Bitrix\Rpa\Integration\Bizproc\Automation\Factory;

if (!Main\Loader::includeModule('bizproc'))
{
	return;
}

class Base extends BaseTrigger
{
	protected $inputData;

	/**
	 * @param int $documentType Target entity id
	 * @return bool
	 */
	public static function isSupported($documentType): bool
	{
		return true;
	}

	public static function execute($documentType, $itemId, array $inputData = null): \Bitrix\Main\Result
	{
		$result = new Main\Result();

		$automationTarget = Factory::createTarget($documentType, $itemId);

		$trigger = new static();
		$trigger->setTarget($automationTarget);
		if ($inputData !== null)
		{
			$trigger->setInputData($inputData);
		}

		$applied = $trigger->send();

		$result->setData([
			'triggersSent' => true,
			'triggerApplied' => $applied
		]);

		return $result;
	}

	public function setInputData($data): BaseTrigger
	{
		$this->inputData = $data;

		return $this;
	}

	public function getInputData($key = null)
	{
		if ($key !== null)
		{
			return is_array($this->inputData) && isset($this->inputData[$key]) ? $this->inputData[$key] : null;
		}

		return $this->inputData;
	}

	public function send(): bool
	{
		if (method_exists(\Bitrix\Bizproc\Automation\Trigger\BaseTrigger::class, 'send'))
		{
			return parent::send();
		}

		$applied = false;
		$triggers = $this->getPotentialTriggers();
		if ($triggers)
		{
			foreach ($triggers as $trigger)
			{
				if ($this->checkApplyRules($trigger))
				{
					$this->applyTrigger($trigger);
					$applied = true;
					break;
				}
			}
		}

		return $applied;
	}

	protected function applyTrigger(array $trigger): bool
	{
		if (method_exists(\Bitrix\Bizproc\Automation\Trigger\BaseTrigger::class, 'applyTrigger'))
		{
			return parent::applyTrigger($trigger);
		}

		$statusId = $trigger['DOCUMENT_STATUS'];

		$target = $this->getTarget();

		$target->setAppliedTrigger($trigger);
		$target->setDocumentStatus($statusId);
		$target->getRuntime()->onDocumentStatusChanged();

		return true;
	}
}

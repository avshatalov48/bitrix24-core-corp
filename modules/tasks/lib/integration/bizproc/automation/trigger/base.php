<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Trigger;

use Bitrix\Main;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;
use Bitrix\Tasks\Integration\Bizproc\Document;

if (!Main\Loader::includeModule('bizproc'))
{
	return;
}

class Base extends \Bitrix\Bizproc\Automation\Trigger\BaseTrigger
{
	protected $inputData;

	/**
	 * @param int $documentType Target entity id
	 * @return bool
	 */
	public static function isSupported($documentType)
	{
		return (!Document\Task::isPersonalTask($documentType));
	}

	public static function execute($documentType, $taskId, array $inputData = null)
	{
		$result = new Main\Result();

		if (!Factory::canUseAutomation())
		{
			$result->addError(new Main\Error('Automation is unavailable.'));
			return $result;
		}

		$automationTarget = Factory::createTarget($documentType, $taskId);

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

	public function setInputData($data)
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

	public function send()
	{
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

	protected function applyTrigger(array $trigger)
	{
		$statusId = $trigger['DOCUMENT_STATUS'];

		$target = $this->getTarget();

		$target->setAppliedTrigger($trigger);
		$target->setDocumentStatus($statusId);
		$target->getRuntime()->onDocumentStatusChanged();

		return true;
	}
}
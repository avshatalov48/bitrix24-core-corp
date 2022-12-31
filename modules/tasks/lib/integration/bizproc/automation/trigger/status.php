<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Bizproc\Document;

Loc::loadMessages(__FILE__);

class Status extends Base
{
	public static function isSupported($documentType)
	{
		return !Document\Task::isPersonalTask($documentType);
	}

	public static function getCode()
	{
		return 'STATUS';
	}

	public static function getName()
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_NAME_1');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		$statusA = (int)$trigger['APPLY_RULES']['STATUS'];
		$statusB = (int)$this->getInputData('STATUS');

		return (!$statusA || $statusA === $statusB);
	}

	public static function toArray()
	{
		$result = parent::toArray();
		$result['STATUS_LIST'] = static::getStatusList();
		$result['STATUS_LABEL'] = Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_LABEL');
		return $result;
	}

	/**
	 * @return array
	 */
	private static function getStatusList()
	{
		$result = [
			[
				'ID' => \CTasks::STATE_PENDING,
				'NAME' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_PENDING_1')
			],
			[
				'ID' => \CTasks::STATE_IN_PROGRESS,
				'NAME' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_IN_PROGRESS')
			],
			[
				'ID' => \CTasks::STATE_SUPPOSEDLY_COMPLETED,
				'NAME' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_SUPPOSEDLY_COMPLETED')
			],
			[
				'ID' => \CTasks::STATE_COMPLETED,
				'NAME' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_COMPLETED')
			],
			[
				'ID' => \CTasks::STATE_DEFERRED,
				'NAME' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_DEFERRED')
			]
		];

		return $result;
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_DESCRIPTION') ?? '';
	}
}
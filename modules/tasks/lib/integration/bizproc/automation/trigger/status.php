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

		$statusA = (int)($trigger['APPLY_RULES']['STATUS'] ?? 0);
		$statusB = (int)$this->getInputData('STATUS');

		return (!$statusA || $statusA === $statusB);
	}

	protected static function getPropertiesMap(): array
	{
		return [
			[
				'Id' => 'STATUS',
				'Name' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_LABEL'),
				'Type' => 'select',
				'EmptyValueText' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_DEFAULT_LABEL'),
				'Options' => static::getStatusList(),
			],
		];
	}

	/**
	 * @return array
	 */
	private static function getStatusList()
	{
		return [
			[
				'value' => \Bitrix\Tasks\Internals\Task\Status::PENDING,
				'name' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_PENDING_1')
			],
			[
				'value' => \Bitrix\Tasks\Internals\Task\Status::IN_PROGRESS,
				'name' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_IN_PROGRESS')
			],
			[
				'value' => \Bitrix\Tasks\Internals\Task\Status::SUPPOSEDLY_COMPLETED,
				'name' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_SUPPOSEDLY_COMPLETED')
			],
			[
				'value' => \Bitrix\Tasks\Internals\Task\Status::COMPLETED,
				'name' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_COMPLETED')
			],
			[
				'value' => \Bitrix\Tasks\Internals\Task\Status::DEFERRED,
				'name' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_DEFERRED')
			]
		];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_STATUS_DESCRIPTION') ?? '';
	}
}
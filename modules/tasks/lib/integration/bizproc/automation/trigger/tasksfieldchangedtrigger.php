<?php

namespace Bitrix\Tasks\Integration\Bizproc\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Bizproc\Document\Task;

class TasksFieldChangedTrigger extends Base
{
	private const ALLOWED_FIELDS = [
		'TITLE',
		'DESCRIPTION',
		'RESPONSIBLE_ID',
		'MARK',
		'ACCOMPLICES',
		'AUDITORS',
		'COMMENT_RESULT',
		'MEMBER_ROLE'
	];

	public static function getCode()
	{
		return 'TASKS_FIELD_CHANGED';
	}

	public static function getName()
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_FIELD_CHANGED_NAME');
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('TASKS_AUTOMATION_TRIGGER_FIELD_CHANGED_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['elementControl'];
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (!is_array($trigger['APPLY_RULES']['fields']))
		{
			return false;
		}
		$followFields = $trigger['APPLY_RULES']['fields'];
		$changedFields = (array)$this->getInputData('CHANGED_FIELDS');
		if (empty($followFields) || empty($changedFields))
		{
			return false;
		}

		$intersect = array_intersect($followFields, $changedFields, self::ALLOWED_FIELDS);

		return !empty($intersect);
	}

	public static function toArray()
	{
		$result = parent::toArray();
		$documentType = func_get_arg(0);

		$result['SETTINGS']['Properties'] = [
			[
				'Id' => 'fields',
				'Name' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_FIELD_CHANGED_PROPERTY_FIELDS'),
				'Type' => '@field-selector',
				'Settings' => [
					'Fields' => static::getFields($documentType),
					'ChooseFieldLabel' => Loc::getMessage('TASKS_AUTOMATION_TRIGGER_FIELD_CHANGED_PROPERTY_FIELDS_CHOOSE')
				],
			],
		];

		return $result;
	}

	protected static function getFields($documentType): array
	{
		$fields = array_intersect_key(Task::getDocumentFields($documentType), array_flip(self::ALLOWED_FIELDS));

		array_walk(
			$fields,
			function (&$field, $id)
			{
				$field['Id'] = $id;
			}
		);

		return array_values($fields);
	}
}

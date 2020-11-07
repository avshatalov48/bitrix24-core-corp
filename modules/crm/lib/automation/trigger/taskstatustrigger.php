<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Main\Loader;
Use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks;

Loc::loadMessages(__FILE__);

class TaskStatusTrigger extends BaseTrigger
{
	/**
	 * @param int $entityTypeId Target entity id
	 * @return bool
	 */
	public static function isSupported($entityTypeId)
	{
		return in_array($entityTypeId, [\CCrmOwnerType::Lead, \CCrmOwnerType::Deal], true);
	}

	public static function isEnabled()
	{
		$tasksOk = ModuleManager::isModuleInstalled('tasks');
		$conditionsOk = method_exists(
			\Bitrix\Bizproc\Automation\Engine\ConditionGroup::class,
			'evaluateByDocument'
		);

		return $tasksOk && $conditionsOk;
	}

	public static function getCode()
	{
		return 'TASK_STATUS';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_TASK_STATUS_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& !empty($trigger['APPLY_RULES']['taskCondition'])
		)
		{
			$task = $this->getInputData('TASK');

			$conditionGroup = new ConditionGroup($trigger['APPLY_RULES']['taskCondition']);
			$documentType = ['tasks', Tasks\Integration\Bizproc\Document\Task::class, 'TASK'];
			$documentId = Tasks\Integration\Bizproc\Document\Task::resolveDocumentId($task['ID']);

			return $conditionGroup->evaluateByDocument(
				$documentType,
				$documentId,
				$task
			);
		}
		return true;
	}

	public static function toArray()
	{
		$result = parent::toArray();
		if (static::isEnabled() && Loader::includeModule('tasks'))
		{
			$taskFields = \Bitrix\Bizproc\Automation\Helper::getDocumentFields(
				['tasks', Tasks\Integration\Bizproc\Document\Task::class, 'TASK']
			);

			$statusList = [];
			foreach($taskFields['STATUS']['Options'] as $id => $status)
			{
				$statusList[] = ['id' => $id, 'name' => $status];
			}
			unset($taskFields['STATUS']);

			$result['STATUS_LIST'] = $statusList;
			$result['FIELDS'] = array_values($taskFields);
		}
		return $result;
	}
}
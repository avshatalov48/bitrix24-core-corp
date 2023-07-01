<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Crm\Service\Container;
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
		if (in_array(
			$entityTypeId,
			[\CCrmOwnerType::Lead, \CCrmOwnerType::Deal, \CCrmOwnerType::SmartInvoice, \CCrmOwnerType::Quote],
			true)
		)
		{
			return true;
		}

		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			return (
				static::areDynamicTypesSupported()
				&& !is_null($factory)
				&& $factory->isAutomationEnabled()
				&& $factory->isStagesEnabled()
			);
		}

		return false;
	}

	public static function isEnabled()
	{
		return ModuleManager::isModuleInstalled('tasks');
	}

	public static function getCode()
	{
		return 'TASK_STATUS';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_TASK_STATUS_NAME_1');
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

			if (
				!empty($trigger['APPLY_RULES']['taskStatus'])
				&& (int)$trigger['APPLY_RULES']['taskStatus'] !== (int)$task['REAL_STATUS']
			)
			{
				return false;
			}

			$conditionGroup = new ConditionGroup($trigger['APPLY_RULES']['taskCondition']);
			$documentType = ['tasks', Tasks\Integration\Bizproc\Document\Task::class, 'TASK'];
			$documentId = Tasks\Integration\Bizproc\Document\Task::resolveDocumentId($task['ID']);

			return $conditionGroup->evaluateByDocument(
				$documentType,
				$documentId
			);
		}
		return true;
	}

	protected static function getPropertiesMap(): array
	{
		$taskFields = \Bitrix\Bizproc\Automation\Helper::getDocumentFields(
			['tasks', Tasks\Integration\Bizproc\Document\Task::class, 'TASK']
		);

		$statusList = [];
		foreach($taskFields['STATUS']['Options'] as $id => $status)
		{
			$statusList[] = ['value' => $id, 'name' => $status];
		}
		unset($taskFields['STATUS']);

		return [
			[
				'Id' => 'taskStatus',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_TASK_STATUS_PROPERTY_STATUS'),
				'Type' => 'select',
				'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_TASK_STATUS_DEFAULT_STATUS'),
				'Options' => $statusList,
			],
			[
				'Id' => 'taskCondition',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_TASK_STATUS_CONDITION'),
				'Type' => '@condition-group-selector',
				'Settings' => [
					'Fields' => array_values($taskFields),
				],
			],
		];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_TASK_STATUS_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['employeeControl', 'taskManagement'];
	}
}
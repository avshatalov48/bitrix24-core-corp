<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if(!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Rpa;
use Bitrix\Bizproc;
use Bitrix\Rpa\Driver;

class RpaAutomationTaskFieldsComponent extends Bitrix\Rpa\Components\ItemDetail
{
	public function executeComponent()
	{
		$this->init();
		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult['formParams'] = $this->prepareFormParams();
		$this->arResult['jsParams'] = [
			'typeId' => $this->type->getId(),
			'id' => $this->item->getId(),
		];

		$this->includeComponentTemplate();
	}

	protected function checkAccess(): Main\Result
	{
		return new Main\Result();
	}

	protected function prepareFormConfig(): array
	{
		$toShow = $this->arParams['fieldsToShow'];
		if(!is_array($toShow))
		{
			$toShow = [];
		}
		$toSet = $this->arParams['fieldsToSet'];
		if(!is_array($toSet))
		{
			$toSet = [];
		}

		$elementsToShow = $elementsToSet =[];
		$userFields = $this->type->getUserFieldCollection();
		foreach($userFields as $userField)
		{
			if (in_array($userField->getName(), $toSet))
			{
				$elementsToSet[] = [
					'name' => $userField->getName(),
				];
			}
			elseif (in_array($userField->getName(), $toShow))
			{
				$elementsToShow[] = [
					'name' => $userField->getName(),
				];
			}
		}

		if (count($toSet) !== count($elementsToSet))
		{
			$this->arResult['showFieldsToSetWarning'] = Main\Localization\Loc::getMessage('RPA_AUTOMATION_TASK_FIELDS_WARNING');
		}

		$formConfig = [];

		if ($elementsToShow)
		{
			$formConfig[] = [
				'name' => 'to_show',
				'title' => Main\Localization\Loc::getMessage('RPA_AUTOMATION_TASK_FIELDS_SHOW'),
				'type' => 'section',
				'elements' => $elementsToShow,
				'editable' => false,
				'data' => [
					'isChangeable' => false,
					'isRemovable' => false,
					'enableTitle' => true,
				],
				'enableTitle' => true,
			];
		}

		if ($elementsToSet)
		{
			$formConfig[] = [
				'name' => 'to_set',
				'title' => Main\Localization\Loc::getMessage('RPA_AUTOMATION_TASK_FIELDS_SET'),
				'type' => 'section',
				'elements' => $elementsToSet,
				'data' => [
					'isChangeable' => true,
					'isRemovable' => false,
					'enableTitle' => true,
				],
				'enableTitle' => true,
			];
		}

		return $formConfig;
	}

	protected function prepareFormFields(): array
	{
		$fields = parent::prepareFormFields();
		$toSet = $this->arParams['fieldsToSet'] ?? [];

		foreach ($toSet as $fieldToSet)
		{
			if (isset($fields[$fieldToSet]))
			{
				$fields[$fieldToSet]['required'] = true;
				$fields[$fieldToSet]['data']['fieldInfo']['MANDATORY'] = 'Y';
			}
		}

		return $fields;
	}

	public function saveAction(array $data, string $eventId = ''): ?array
	{
		$this->init();
		if($this->getErrors())
		{
			return null;
		}

		if(empty($data))
		{
			$this->errorCollection[] = new Error('No data');
			return null;
		}

		$result = [];
		$fields = $this->prepareDataToSet($data);

		$task = Driver::getInstance()->getTaskManager()->getTaskById($this->arParams['taskId']);
		if ($task)
		{
			$userId = Driver::getInstance()->getUserId();
			if (\CBPDocument::PostTaskForm($task, $userId, ['complete' => 'Y', 'fields' => $fields], $errors))
			{
				$result = ['completed' => 'ok', 'SUCCESS' => 'Y',];
			}
			else
			{
				$error = reset($errors);
				if ($error['code'] === \CBPRuntime::EXCEPTION_CODE_INSTANCE_TERMINATED)
				{
					$result = ['completed' => 'ok', 'stageUpdated' => true, 'SUCCESS' => 'Y',];
				}
				else
				{
					$this->errorCollection[] = new Error($error['message']);
				}
			}
		}

		return $result;
	}

	protected function prepareFormParams(): array
	{
		$params = parent::prepareFormParams();

		$params['INITIAL_MODE'] = 'edit';
		$params['ENABLE_COMMON_CONFIGURATION_UPDATE'] = false;
		$params['ENABLE_MODE_TOGGLE'] = false;
		$params['ENABLE_TOOL_PANEL'] = false;
		$params['ENABLE_USER_FIELD_CREATION'] = false;
		$params['ENABLE_FIELDS_CONTEXT_MENU'] = false;
		$params['ENABLE_CONFIG_SCOPE_TOGGLE'] = false;

		return $params;
	}

	protected function getEditorConfigId(): string
	{
		return parent::getEditorConfigId() . '-task-fields';
	}

	protected function listKeysSignedParameters(): array
	{
		return array_merge(parent::listKeysSignedParameters(), [
			'taskId',
		]);
	}
}
<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Crm;
use \Bitrix\Main\Localization\Loc;

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CrmCopyDynamicActivity');

class CBPCrmChangeDynamicCategoryActivity extends CBPCrmCopyDynamicActivity
{
	const CYCLE_LIMIT = 150;

	protected function prepareProperties(Crm\Service\Factory $factory, Crm\Item $item)
	{
		$this->preparedProperties = [
			'CategoryId' => (int)$this->CategoryId,
			'StageId' => $this->StageId,
		];
	}

	protected function checkPreparedProperties(Crm\Service\Factory $factory): bool
	{
		$result = parent::checkPreparedProperties($factory);
		if (!$result)
		{
			return false;
		}
		if (!$factory->isCategoriesEnabled())
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CDCA_ENABLING_CATEGORIES_ERROR'),
				0,
				CBPTrackingType::Error
			);
			return false;
		}

		$itemId = mb_split('_(?=[^_]*$)', $this->GetDocumentId()[2])[1];
		$item = $factory->getItem($itemId);
		if ($item->getCategoryId() === $this->preparedProperties['CategoryId'])
		{
			$this->WriteToTrackingService(
				Loc::getMessage('CRM_CDCA_MOVE_ERROR_CATEGORY_NOT_CHANGED'),
				0,
				CBPTrackingType::Error
			);

			return false;
		}

		return true;
	}

	protected function applyPreparedProperties(Crm\Item $item)
	{
		$item->setCategoryId($this->preparedProperties['CategoryId']);
		$item->setStageId($this->preparedProperties['StageId']);
	}

	protected function internalExecute(Crm\Service\Factory $factory, Crm\Item $item)
	{
		$operation = $factory->getUpdateOperation($item);
		$operation->getContext()->setScope(Crm\Service\Context::SCOPE_AUTOMATION);
		$operation
			->disableCheckAccess()
			->disableBizProc()
			->disableCheckFields()
			->disableCheckWorkflows()
			->disableAutomation()
		;
		$updateResult = $operation->launch();

		$errorMessages = $updateResult->getErrorMessages();

		if ($updateResult->isSuccess())
		{
			$terminateResult = $this->terminateDocumentWorkflows();
			if (!$terminateResult->isSuccess())
			{
				$errorMessages = $terminateResult->getErrorMessages();
				$this->WriteToTrackingService(
					implode(', ', $errorMessages),
					0,
					CBPTrackingType::Error
				);
			}
			else
			{
				$itemBeforeSave = $operation->getItemBeforeSave();

				$starter = new Crm\Automation\Starter($item->getEntityTypeId(), $item->getId());
				$starter->setContextToBizproc()->runOnUpdate(
					Crm\Automation\Helper::prepareCompatibleData(
						$itemBeforeSave->getEntityTypeId(),
						$itemBeforeSave->getCompatibleData(\Bitrix\Main\ORM\Objectify\Values::CURRENT),
					),
					Crm\Automation\Helper::prepareCompatibleData(
						$itemBeforeSave->getEntityTypeId(),
						$itemBeforeSave->getCompatibleData(\Bitrix\Main\ORM\Objectify\Values::ACTUAL),
					)
				);
			}

			throw new Exception('TerminateWorkflow');
		}

		if ($errorMessages)
		{
			$this->WriteToTrackingService(
				implode(', ', $errorMessages),
				0,
				CBPTrackingType::Error
			);
		}
	}

	protected function terminateDocumentWorkflows(): \Bitrix\Main\Result
	{
		$result = new \Bitrix\Main\Result();

		$documentId = $this->GetDocumentId();
		$instanceIds = \Bitrix\Bizproc\WorkflowInstanceTable::getIdsByDocument($documentId);
		$instanceIds[] = $this->GetWorkflowInstanceId();
		$instanceIds = array_unique($instanceIds);

		foreach ($instanceIds as $instanceId)
		{
			$errors = [];

			\CBPDocument::TerminateWorkflow(
				$instanceId,
				$documentId,
				$errors,
				Loc::getMessage('CRM_CDCA_MOVE_TERMINATION_TITLE')
			);

			foreach ($errors as $error)
			{
				$result->addError(new \Bitrix\Main\Error($error['message'], $error['code']));
			}
		}

		return $result;
	}

	public static function GetPropertiesDialog(
		$documentType,
		$activityName,
		$workflowTemplate,
		$workflowParameters,
		$workflowVariables,
		$currentValues = null,
		$formName = '',
		$popupWindow = null,
		$siteId = ''
	)
	{
		$dialog = parent::GetPropertiesDialog(...func_get_args());
		$dialog->setActivityFile(__FILE__);

		$factory = static::getFactoryByType($documentType[2]);
		if (!$factory->isCategoriesEnabled())
		{
			$dialog->setRuntimeData([
				'errors' => [new \Bitrix\Main\Error(Loc::getMessage('CRM_CDCA_ENABLING_CATEGORIES_ERROR'))],
			]);
		}

		return $dialog;
	}

	public static function getPropertiesDialogMap(\Bitrix\Bizproc\Activity\PropertiesDialog $dialog): array
	{
		$map = parent::getPropertiesDialogMap($dialog);

		if (isset($map['CategoryId']))
		{
			$map['CategoryId']['Required'] = true;
		}
		if (isset($map['StageId']))
		{
			$map['StageId']['Required'] = true;
		}

		unset($map['ItemTitle']);
		unset($map['Responsible']);

		return $map;
	}
}
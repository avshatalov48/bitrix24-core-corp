<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;

class CBPCrmCopyDealActivity extends CBPActivity
{
	private static $cycleCounter = [];
	const CYCLE_LIMIT = 150;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'DealTitle' => '',
			'CategoryId' => 0,
			'StageId' => null,
			'Responsible' => null,

			//return
			'DealId' => 0,
		];

		$this->SetPropertiesTypes([
			'DealId' => [
				'Type' => 'int',
			],
		]);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		$this->DealId = 0;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentId = $this->GetDocumentId();
		$this->checkCycling($documentId);

		$sourceDealId = explode('_', $documentId[2])[1];
		$sourceFields = $fields = [];

		if ($sourceDealId > 0)
		{
			$dbResult = \CCrmDeal::GetListEx(
				[],
				['=ID' => $sourceDealId, 'CHECK_PERMISSIONS' => 'N'],
				false,
				false,
				['*', 'UF_*']
			);
			$sourceFields = $dbResult->Fetch();
		}

		if (!$sourceFields)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CDA_NO_SOURCE_FIELDS'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$this->prepareSourceFields($sourceFields);

		$merger = new Crm\Merger\DealMerger(1, false);
		$merger->mergeFields($sourceFields, $fields, true);

		unset($fields['STAGE_ID']);
		$fields['CATEGORY_ID'] = (int)$this->CategoryId;

		$stageId = (string)$this->StageId;
		if ($stageId)
		{
			$fields['STAGE_ID'] = $stageId;

			if ($fields['CATEGORY_ID'] !== Crm\Category\DealCategory::resolveFromStageID($stageId))
			{
				$this->WriteToTrackingService(GetMessage("CRM_CDA_STAGE_SELECTION_ERROR"), 0, CBPTrackingType::Error);

				return CBPActivityExecutionStatus::Closed;
			}
		}

		$responsibles = CBPHelper::ExtractUsers($this->Responsible, $this->GetDocumentId());
		if (count($responsibles) > 1)
		{
			shuffle($responsibles);
		}
		elseif (!$responsibles)
		{
			$responsibles[] = $sourceFields['ASSIGNED_BY_ID'];
		}

		$dealTitle = $this->DealTitle;
		if (empty($dealTitle))
		{
			$dealTitle = GetMessage('CRM_CDA_NEW_DEAL_TITLE', ['#SOURCE_TITLE#' => $sourceFields['TITLE']]);
		}

		$fields['TITLE'] = $dealTitle;
		$fields['ASSIGNED_BY_ID'] = $responsibles[0];
		$fields['CONTACT_IDS'] = Crm\Binding\DealContactTable::getDealContactIDs($sourceDealId);

		$entity = new \CCrmDeal(false);
		$newDealId = $entity->Add(
			$fields,
			true,
			['REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => 0]
		);

		if (!$newDealId)
		{
			$this->WriteToTrackingService($entity->LAST_ERROR, 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$this->DealId = $newDealId;

		$oldProducts = \CCrmProductRow::LoadRows('D', $sourceDealId, true);
		foreach ($oldProducts as $i => $product)
		{
			unset($oldProducts[$i]['ID'], $oldProducts[$i]['OWNER_ID']);
		}

		if (!CCrmProductRow::SaveRows('D', $newDealId, $oldProducts))
		{
			$this->WriteToTrackingService(GetMessage('CRM_CDA_COPY_PRODUCTS_ERROR'), 0, CBPTrackingType::Error);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('DEAL');
			if ($CCrmBizProc->CheckFields(false, true))
			{
				$CCrmBizProc->StartWorkflow($newDealId);
			}
		}

		//Region automation
		$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $newDealId);
		$starter->setContextToBizproc()->runOnAdd();
		//End region

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]);

		$defaultTitle = GetMessage('CRM_CDA_NEW_DEAL_TITLE', ['#SOURCE_TITLE#' => '{=Document:TITLE}']);
		if ($formName === 'bizproc_automation_robot_dialog')
		{
			$defaultTitle = Crm\Automation\Helper::convertExpressions($defaultTitle, $documentType);
		}

		$dialog->setMap([
			'DealTitle' => [
				'Name' => GetMessage('CRM_CDA_DEAL_TITLE'),
				'FieldName' => 'deal_title',
				'Type' => 'string',
				'Default' => $defaultTitle,
			],
			'CategoryId' => [
				'Name' => GetMessage('CRM_CDA_MOVE_TO_CATEGORY'),
				'FieldName' => 'category_id',
				'Type' => 'deal_category',
				'Required' => true,
			],
			'StageId' => [
				'Name' => GetMessage('CRM_CDA_CHANGE_STAGE'),
				'FieldName' => 'stage_id',
				'Type' => 'deal_stage',
			],
			'Responsible' => [
				'Name' => GetMessage('CRM_CDA_CHANGE_RESPONSIBLE'),
				'FieldName' => 'responsible',
				'Type' => 'user',
			],
		]);

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];

		$properties = [
			'DealTitle' => $arCurrentValues['deal_title'],
			'CategoryId' => $arCurrentValues['category_id'],
			'StageId' => $arCurrentValues['stage_id'],
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues['responsible'], $documentType, $errors),
		];

		if ($properties['CategoryId'] === '' && static::isExpression($arCurrentValues['category_id_text']))
		{
			$properties['CategoryId'] = $arCurrentValues['category_id_text'];
		}
		if ($properties['StageId'] === '' && static::isExpression($arCurrentValues['stage_id_text']))
		{
			$properties['StageId'] = $arCurrentValues['stage_id_text'];
		}

		if (count($errors) > 0)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$activity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$activity["Properties"] = $properties;

		return true;
	}

	private function checkCycling(array $documentId)
	{
		//check deal only.
		if (!($documentId[0] === 'crm' && $documentId[1] === 'CCrmDocumentDeal'))
		{
			return true;
		}

		$key = $this->GetName();

		if (!isset(self::$cycleCounter[$key]))
		{
			self::$cycleCounter[$key] = 0;
		}

		self::$cycleCounter[$key]++;
		if (self::$cycleCounter[$key] > self::CYCLE_LIMIT)
		{
			$this->WriteToTrackingService(GetMessage("CRM_CDA_CYCLING_ERROR"), 0, CBPTrackingType::Error);
			throw new Exception();
		}
	}

	private function prepareSourceFields(&$sourceFields)
	{
		if (
			!\Bitrix\Main\Loader::includeModule('calendar')
			||
			!method_exists('\Bitrix\Calendar\UserField\ResourceBooking', 'prepareValue')
		)
		{
			return false;
		}

		$userFieldsList = CCrmDeal::GetUserFields();
		if (is_array($userFieldsList))
		{
			foreach ($userFieldsList as $userFieldName => $userFieldParams)
			{
				$fieldTypeId = isset($userFieldParams['USER_TYPE']) ? $userFieldParams['USER_TYPE']['USER_TYPE_ID'] : '';
				$fieldValue = isset($sourceFields[$userFieldName]) ? $sourceFields[$userFieldName] : null;

				if (!$fieldValue)
				{
					continue;
				}

				if ($fieldTypeId === 'resourcebooking')
				{
					$newValue = [];
					$resourceList = \Bitrix\Calendar\UserField\ResourceBooking::getResourceEntriesList((array)$fieldValue);

					if ($resourceList)
					{
						foreach ($resourceList['ENTRIES'] as $entry)
						{
							$newValue[] = \Bitrix\Calendar\UserField\ResourceBooking::prepareValue(
								$entry['TYPE'],
								$entry['RESOURCE_ID'],
								$resourceList['DATE_FROM'],
								MakeTimeStamp($resourceList['DATE_TO']) - MakeTimeStamp($resourceList['DATE_FROM']),
								$resourceList['SERVICE_NAME']
							);
						}
					}
					$sourceFields[$userFieldName] = $newValue;
				}
			}

		}
	}
}

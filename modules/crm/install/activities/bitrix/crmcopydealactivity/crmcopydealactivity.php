<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmCopyDealActivity
	extends CBPActivity
{
	private static $cycleCounter = [];
	const CYCLE_LIMIT = 1000;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'DealTitle' => '',
			'CategoryId' => 0,
			//'StageId' => null,
			'Responsible' => null,

			//return
			'DealId' => 0
		);

		$this->SetPropertiesTypes(array(
			'DealId' => array(
				'Type' => 'int'
			)
		));
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

		if($sourceDealId > 0)
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $sourceDealId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('*', 'UF_*')
			);
			$sourceFields = $dbResult->Fetch();
		}

		if (!$sourceFields)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CDA_NO_SOURCE_FIELDS'), 0, CBPTrackingType::Error);
			return CBPActivityExecutionStatus::Closed;
		}

		$merger = new \Bitrix\Crm\Merger\DealMerger(1, false);
		$merger->mergeFields($sourceFields, $fields, true);

		unset($fields['STAGE_ID']);
		$fields['CATEGORY_ID'] = (int)$this->CategoryId;

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
		$fields['CONTACT_IDS'] = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($sourceDealId);

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
		\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $newDealId);
		//End region

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = [];

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule("crm"))
			return '';

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId
		));

		$dialog->setMap(array(
			'DealTitle' => array(
				'Name' => GetMessage('CRM_CDA_DEAL_TITLE'),
				'FieldName' => 'deal_title',
				'Type' => 'string',
				'Default' => GetMessage('CRM_CDA_NEW_DEAL_TITLE', ['#SOURCE_TITLE#' => '{=Document:TITLE}'])
			),
			'CategoryId' => array(
				'Name' => GetMessage('CRM_CDA_MOVE_TO_CATEGORY'),
				'FieldName' => 'category_id',
				'Type' => 'select',
				'Options' => \Bitrix\Crm\Category\DealCategory::getSelectListItems(),
			),
			//'StageId' => array(
			//	'Name' => GetMessage('CRM_CDA_CHANGE_STAGE'),
			//	'FieldName' => 'stage_id',
			//	'Type' => 'string',
			//	'Default' => 'NEW'
			//),
			'Responsible' => array(
				'Name' => GetMessage('CRM_CDA_CHANGE_RESPONSIBLE'),
				'FieldName' => 'responsible',
				'Type' => 'user'
			),
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = [];

		$arProperties = array(
			'DealTitle' => $arCurrentValues["deal_title"],
			'CategoryId' => $arCurrentValues['category_id'],
			//'StageId' => $arCurrentValues['stage_id'],
			'Responsible' => CBPHelper::UsersStringToArray($arCurrentValues["responsible"], $documentType, $arErrors),
		);

		if (count($arErrors) > 0)
		{
			return false;
		}

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
		{
			return false;
		}

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

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
}
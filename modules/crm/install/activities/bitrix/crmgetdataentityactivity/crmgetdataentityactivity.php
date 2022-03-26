<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

class CBPCrmGetDataEntityActivity extends CBPActivity
{
	protected static $listDefaultEntityType = ['LEAD', 'CONTACT', 'COMPANY', 'DEAL'];

	private $fieldsMap;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title'            => '',
			'EntityId'         => null,
			'EntityType'       => null,
			'EntityFields'     => null,
			'PrintableVersion' => null
		];
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();
		if ($this->fieldsMap)
		{
			foreach ($this->fieldsMap as $field)
			{
				$this->__set($field, null);
			}
		}
	}

	public function Execute()
	{
		if (
			!($this->EntityId) ||
			!is_array($this->EntityFields) || count($this->EntityFields) <= 0 ||
			!self::checkEntityType($this->EntityType) ||
			!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$listFields = [];
		foreach ($this->getEntityData() as $fieldId => $fieldValue)
		{
			$listFields[$fieldId] = $fieldValue;
		}

		$this->SetProperties($listFields);
		// $this->setPropertiesTypes($this->EntityFields);

		$this->fieldsMap = array_keys($listFields);

		return CBPActivityExecutionStatus::Closed;
	}

	protected function getEntityData()
	{
		$entityId = null;
		$objectResult = null;

		$selectedUfFields = [];
		if (is_array($this->EntityFields))
		{
			foreach ($this->EntityFields as $fieldId => $fieldName)
			{
				if (mb_strpos($fieldId, 'UF_CRM') === 0)
					$selectedUfFields[] = $fieldId;
			}
		}

		if (is_array($this->EntityId))
		{
			foreach ($this->EntityId as $entityId)
			{
				if (intval($entityId))
				{
					$entityId = intval($entityId);
				}
				else
				{
					$explode = explode('_', $entityId);
					if (CUserTypeCrm::getLongEntityType($explode[0]) == $this->EntityType)
					{
						$entityId = intval($explode[1]);
						break;
					}
				}
			}
		}
		else
		{
			if (intval($this->EntityId))
			{
				$entityId = intval($this->EntityId);
			}
			else
			{
				$explode = explode('_', $this->EntityId);
				if (CUserTypeCrm::getLongEntityType($explode[0]) == $this->EntityType)
					$entityId = intval($explode[1]);
			}
		}

		$printableVersion = $this->PrintableVersion == 'Y' ? true : false;
		$entityData = [];
		switch ($this->EntityType)
		{
			case 'LEAD':
				{
					$objectResult = CCrmLead::getListEx(
						['ID' => 'DESC'],
						['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N']
					);
					if (!$entityData = $objectResult->fetch())
					{
						return [];
					}
					$this->getMultiValue('LEAD', $entityData);
					$this->getUserNameBySiteFormat($entityData);
					$this->getUserByBpFormat($entityData);

					if ($printableVersion)
					{
						$statusList = CCrmStatus::getStatusList('STATUS');
						$entityData['STATUS_ID'] = $statusList[$entityData['STATUS_ID']];
						$sourceList = CCrmStatus::getStatusListEx('SOURCE');
						$entityData['SOURCE_ID'] = $sourceList[$entityData['SOURCE_ID']];
						$honorificList = CCrmStatus::getStatusList('HONORIFIC');
						$entityData['HONORIFIC'] = $honorificList[$entityData['HONORIFIC']];

						$currencyId = !empty($entityData['CURRENCY_ID']) ?
							$entityData['CURRENCY_ID'] : CCrmCurrency::getBaseCurrencyID();
						$currencyList = CCrmCurrencyHelper::prepareListItems();
						$entityData['CURRENCY_ID'] = isset($currencyList[$currencyId]) ?
							$currencyList[$currencyId] : $currencyId;
						$entityData['OPPORTUNITY'] = CCrmCurrency::moneyToString($entityData['OPPORTUNITY'], $currencyId, '#');
						$entityData['OPENED'] = $entityData['OPENED'] == 'Y' ?
							GetMessage('CRM_ACTIVITY_FIELD_MAIN_YES') : GetMessage('CRM_ACTIVITY_FIELD_MAIN_NO');
						$entityData['BIRTHDATE'] = convertTimeStamp(makeTimeStamp($entityData['BIRTHDATE']), 'SHORT', SITE_ID);
						$entityData['ASSIGNED_BY_ID'] = $entityData['ASSIGNED_BY_FORMATTED_NAME'];
					}
					global $USER_FIELD_MANAGER;
					$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
					$this->getUfValue($CCrmUserType, $this->EntityType, $entityData, $selectedUfFields, $printableVersion);
					break;
				}
			case 'CONTACT':
				{
					$objectResult = CCrmContact::getListEx(
						['ID' => 'DESC'],
						['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N']
					);
					if (!$entityData = $objectResult->fetch())
					{
						return [];
					}
					$this->getMultiValue('CONTACT', $entityData);
					$this->getUserNameBySiteFormat($entityData);
					$this->getUserByBpFormat($entityData);

					$entityData['COMPANY_IDS'] = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($entityId);

					if ($printableVersion)
					{
						$typeList = CCrmStatus::getStatusList('CONTACT_TYPE');
						$entityData['TYPE_ID'] = $typeList[$entityData['TYPE_ID']];
						$sourceList = CCrmStatus::getStatusList('SOURCE');
						$entityData['SOURCE_ID'] = $sourceList[$entityData['SOURCE_ID']];
						$honorificList = CCrmStatus::getStatusList('HONORIFIC');
						$entityData['HONORIFIC'] = $honorificList[$entityData['HONORIFIC']];

						$entityData['OPENED'] = $entityData['OPENED'] == 'Y' ?
							GetMessage('CRM_ACTIVITY_FIELD_MAIN_YES') : GetMessage('CRM_ACTIVITY_FIELD_MAIN_NO');
						$entityData['EXPORT'] = $entityData['EXPORT'] == 'Y' ?
							GetMessage('CRM_ACTIVITY_FIELD_MAIN_YES') : GetMessage('CRM_ACTIVITY_FIELD_MAIN_NO');
						$entityData['BIRTHDATE'] = convertTimeStamp(makeTimeStamp($entityData['BIRTHDATE']), 'SHORT', SITE_ID);
						$entityData['ASSIGNED_BY_ID'] = $entityData['ASSIGNED_BY_FORMATTED_NAME'];
						if ($entityData['COMPANY_ID'])
						{
							$objectResult = CCrmCompany::getList(
								['ID' => 'DESC'],
								['=ID' => $entityData['COMPANY_ID'], 'CHECK_PERMISSIONS' => 'N'],
								['TITLE']
							);
							$companyData = $objectResult->fetch();
							$entityData['COMPANY_ID'] = $companyData['TITLE'];
						}
						if ($entityData['COMPANY_IDS'])
						{
							$objectResult = CCrmCompany::getList(
								['ID' => 'DESC'],
								['=ID' => $entityData['COMPANY_IDS'], 'CHECK_PERMISSIONS' => 'N'],
								['TITLE']
							);
							$listCompanyName = [];
							while ($companyData = $objectResult->fetch())
								$listCompanyName[] = $companyData['TITLE'];
							$entityData['COMPANY_IDS'] = implode(', ', $listCompanyName);
						}

						if (!empty($entityData['PHOTO']))
						{
							$photo = $entityData['PHOTO'];
							if (is_array($entityData['PHOTO']))
							{
								foreach ($entityData['PHOTO'] as $r)
								{
									$r = intval($r);
									$dbImg = CFile::getByID($r);
									if ($arImg = $dbImg->fetch())
									{
										$photo[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".
											urlencode($arImg["FILE_NAME"])."&i=".$r."&h=".md5($arImg["SUBDIR"])."]".
											htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
									}
								}
							}
							else
							{
								$entityData['PHOTO'] = intval($entityData['PHOTO']);
								$dbImg = CFile::getByID($entityData['PHOTO']);
								if ($arImg = $dbImg->fetch())
								{
									$photo = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"]).
										"&i=".$entityData['PHOTO']."&h=".md5($arImg["SUBDIR"])."]".
										htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
								}
							}
							$entityData['PHOTO'] = $photo;
						}
					}

					global $USER_FIELD_MANAGER;
					$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);
					$this->getUfValue($CCrmUserType, $this->EntityType, $entityData, $selectedUfFields, $printableVersion);
					break;
				}
			case 'COMPANY':
				{
					$objectResult = CCrmCompany::getListEx(
						['ID' => 'DESC'],
						['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N']
					);
					if (!$entityData = $objectResult->fetch())
					{
						return [];
					}
					$this->getMultiValue('COMPANY', $entityData);
					$this->getUserNameBySiteFormat($entityData);
					$this->getUserByBpFormat($entityData);
					if ($printableVersion)
					{
						$companyType = CCrmStatus::getStatusListEx('COMPANY_TYPE');
						$employessList = CCrmStatus::getStatusListEx('EMPLOYEES');
						$industryList = CCrmStatus::getStatusListEx('INDUSTRY');
						$entityData['COMPANY_TYPE'] = $companyType[$entityData['COMPANY_TYPE']];
						$entityData['EMPLOYEES'] = $employessList[$entityData['EMPLOYEES']];
						$entityData['INDUSTRY'] = $industryList[$entityData['INDUSTRY']];

						$currencyId = !empty($entityData['CURRENCY_ID']) ?
							$entityData['CURRENCY_ID'] : CCrmCurrency::getBaseCurrencyID();
						$entityData['CURRENCY_ID'] = CCrmCurrency::getCurrencyName($currencyId);
						$entityData['REVENUE'] = CCrmCurrency::moneyToString($entityData['REVENUE'], $currencyId, '#');

						$entityData['OPENED'] = $entityData['OPENED'] == 'Y' ?
							GetMessage('CRM_ACTIVITY_FIELD_MAIN_YES') : GetMessage('CRM_ACTIVITY_FIELD_MAIN_NO');
						$entityData['IS_MY_COMPANY'] = $entityData['IS_MY_COMPANY'] == 'Y' ?
							GetMessage('CRM_ACTIVITY_FIELD_MAIN_YES') : GetMessage('CRM_ACTIVITY_FIELD_MAIN_NO');
						$entityData['ASSIGNED_BY_ID'] = $entityData['ASSIGNED_BY_FORMATTED_NAME'];
					}
					global $USER_FIELD_MANAGER;
					$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
					$this->getUfValue($CCrmUserType, $this->EntityType, $entityData, $selectedUfFields, $printableVersion);
					break;
				}
			case 'DEAL':
				{
					$objectResult = CCrmDeal::getListEx(
						['ID' => 'DESC'],
						['=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N']
					);
					if (!$entityData = $objectResult->fetch())
					{
						return [];
					}
					$this->getUserNameBySiteFormat($entityData);
					$this->getUserByBpFormat($entityData);

					$entityData['CONTACT_IDS'] = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($entityId);
					$entityData['CATEGORY_ID'] = isset($entityData['CATEGORY_ID'])
						? $entityData['CATEGORY_ID'] : CCrmDeal::getCategoryID($entityId);
					if ($printableVersion)
					{
						$stageList = Bitrix\Crm\Category\DealCategory::getStageList(
							isset($entityData['CATEGORY_ID']) ? (int)$entityData['CATEGORY_ID'] : 0);
						$currencyId = !empty($entityData['CURRENCY_ID']) ?
							$entityData['CURRENCY_ID'] : CCrmCurrency::getBaseCurrencyID();
						$accountCurrencyId = !empty($entityData['ACCOUNT_CURRENCY_ID']) ?
							$entityData['ACCOUNT_CURRENCY_ID'] : CCrmCurrency::getBaseCurrencyID();
						$typeList = CCrmStatus::getStatusListEx('DEAL_TYPE');
						$entityData['TYPE_ID'] = isset($typeList[$entityData['TYPE_ID']]) ?
							$typeList[$entityData['TYPE_ID']] : '';
						$entityData['CURRENCY_ID'] = CCrmCurrency::getCurrencyName($currencyId);
						$entityData['OPPORTUNITY'] = CCrmCurrency::moneyToString($entityData['OPPORTUNITY'], $currencyId, '#');
						$entityData['ACCOUNT_CURRENCY_ID'] = CCrmCurrency::getCurrencyName($accountCurrencyId);
						$entityData['OPPORTUNITY_ACCOUNT'] = CCrmCurrency::moneyToString(
							$entityData['OPPORTUNITY_ACCOUNT'], $accountCurrencyId, '#');
						$entityData['STAGE_ID'] = $stageList[$entityData['STAGE_ID']];
						$entityData['CLOSED'] = $entityData['CLOSED'] == 'Y' ?
							GetMessage('CRM_ACTIVITY_FIELD_MAIN_YES') : GetMessage('CRM_ACTIVITY_FIELD_MAIN_NO');
						$entityData['OPENED'] = $entityData['OPENED'] == 'Y' ?
							GetMessage('CRM_ACTIVITY_FIELD_MAIN_YES') : GetMessage('CRM_ACTIVITY_FIELD_MAIN_NO');
						$entityData['ASSIGNED_BY_ID'] = $entityData['ASSIGNED_BY_FORMATTED_NAME'];
						if ($entityData['CONTACT_ID'])
						{
							$objectResult = CCrmContact::getList(
								['ID' => 'DESC'],
								['=ID' => $entityData['CONTACT_ID'], 'CHECK_PERMISSIONS' => 'N'],
								['FULL_NAME']
							);
							$contactData = $objectResult->fetch();
							$entityData['CONTACT_ID'] = $contactData['FULL_NAME'];
						}
						if ($entityData['CONTACT_IDS'])
						{
							$objectResult = CCrmContact::getList(
								['ID' => 'DESC'],
								['=ID' => $entityData['CONTACT_IDS'], 'CHECK_PERMISSIONS' => 'N'],
								['FULL_NAME']
							);
							$listContactName = [];
							while ($contactData = $objectResult->fetch())
								$listContactName[] = $contactData['FULL_NAME'];
							$entityData['CONTACT_IDS'] = implode(', ', $listContactName);
						}
						if (empty($entityData['COMPANY_TITLE']) && $entityData['COMPANY_ID'])
						{
							$objectResult = CCrmCompany::getList(
								['ID' => 'DESC'],
								['=ID' => $entityData['COMPANY_ID'], 'CHECK_PERMISSIONS' => 'N'],
								['TITLE']
							);
							$companyData = $objectResult->fetch();
							$entityData['COMPANY_ID'] = $companyData['TITLE'];
						}
						else
						{
							$entityData['COMPANY_ID'] = $entityData['COMPANY_TITLE'];
						}
					}
					global $USER_FIELD_MANAGER;
					$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);
					$this->getUfValue($CCrmUserType, $this->EntityType, $entityData, $selectedUfFields, $printableVersion);
					break;
				}
		}

		if ($entityData)
		{
			return $entityData;
		}
		else
		{
			return [];
		}
	}

	public static function ValidateProperties($testProperties = [], CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		try
		{
			CBPHelper::ParseDocumentId($testProperties['DocumentType']);
		} catch (Exception $e)
		{
			$errors[] = [
				'code'      => 'NotExist',
				'parameter' => 'DocumentType',
				'message'   => GetMessage('CRM_ACTIVITY_ERROR_DT_1')
			];
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	public static function GetPropertiesDialog(
		$documentType, $activityName, $workflowTemplate, $workflowParameters,
		$workflowVariables, $currentValues = null, $formName = '')
	{
		if (!is_array($workflowParameters))
			$workflowParameters = [];
		if (!is_array($workflowVariables))
			$workflowVariables = [];
		$renderEntityFields = '';

		if (!is_array($currentValues))
		{
			$currentValues = [
				'EntityId'         => null,
				'EntityType'       => null,
				'EntityFields'     => null,
				'PrintableVersion' => null
			];
			$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
			if (is_array($currentActivity['Properties']))
			{
				$currentValues['EntityId'] = $currentActivity['Properties']['EntityId'];
				$currentValues['EntityType'] = $currentActivity['Properties']['EntityType'];
				$currentValues['EntityFields'] = $currentActivity['Properties']['EntityFields'];
				$currentValues['PrintableVersion'] = $currentActivity['Properties']['PrintableVersion'];

				$renderEntityFields = self::renderEntityFields(
					$currentActivity['Properties']['EntityType'], $currentValues);
			}
		}
		else
		{
			$renderEntityFields = self::renderEntityFields($currentValues['EntityType'], $currentValues);
		}

		$runtime = CBPRuntime::GetRuntime();

		return $runtime->ExecuteResourceFile(
			__FILE__,
			'properties_dialog.php',
			[
				'documentType'       => $documentType,
				'currentValues'      => $currentValues,
				'formName'           => $formName,
				'renderEntityFields' => $renderEntityFields,
			]
		);
	}

	public static function GetPropertiesDialogValues(
		$documentType, $activityName, &$workflowTemplate, &$workflowParameters,
		&$workflowVariables, $currentValues, &$errors)
	{
		$errors = [];

		if (empty($currentValues['EntityId']) || !self::checkEntityType($currentValues['EntityType']))
		{
			$errors[] = [
				'code'    => 'emptyRequiredField',
				'message' => str_replace('#FIELD#',
					GetMessage("CRM_ACTIVITY_ERROR_ENTITY_ID").', '.GetMessage("CRM_ACTIVITY_ERROR_ENTITY_TYPE")
					, GetMessage("CRM_ACTIVITY_ERROR_FIELD_REQUIED")),
			];

			return false;
		}

		$currentFields = current($currentValues['EntityFields']);
		if (empty($currentFields))
		{
			$errors[] = [
				'code'    => 'emptyRequiredField',
				'message' => GetMessage("CRM_ACTIVITY_ERROR_ENTITY_LIST_FIELDS"),
			];

			return false;
		}

		$properties = ['DocumentType' => $documentType];
		$entityFields = self::getEntityFields($currentValues['EntityType']);
		$properties['EntityId'] = $currentValues['EntityId'];
		$properties['EntityType'] = $currentValues['EntityType'];
		$properties['PrintableVersion'] = $currentValues['PrintableVersion'];
		foreach ($currentValues['EntityFields'] as $fieldId)
		{
			if (!array_key_exists($fieldId, $entityFields))
			{
				$errors[] = [
					'code'    => 'incorrectFieldType',
					'message' => str_replace('#FIELD#', $fieldId, GetMessage("CRM_ACTIVITY_ERROR_FIELD_TYPE")),
				];
				break;
			}
			$properties['EntityFields'][$fieldId]['Name'] = $entityFields[$fieldId]['Name'];
			$properties['EntityFields'][$fieldId]['Type'] = $entityFields[$fieldId]['Type'];
			$properties['EntityFields'][$fieldId]['Settings'] = $entityFields[$fieldId]['Settings'];
			$properties['EntityFields'][$fieldId]['Options'] = $entityFields[$fieldId]['Options'];
		}

		if (!empty($errors))
			return false;

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	public static function getAjaxResponse($request)
	{
		$response = '';

		if (empty($request['customer_action']))
			return '';

		if ($request['customer_action'] == 'getEntityFields')
		{
			$response = self::renderEntityFields($request['entity_type']);
		}

		return $response;
	}

	protected static function renderEntityFields($entityType, $currentValues = [])
	{
		$html = '';

		if (!self::checkEntityType($entityType))
			return $html;

		$entityFields = self::getEntityFields($entityType);

		$options = '';
		foreach ($entityFields as $fieldId => $field)
		{
			$selected = '';
			if (is_array($currentValues['EntityFields']) && array_key_exists($fieldId, $currentValues['EntityFields']))
				$selected = 'selected';
			$options .= '<option '.$selected.' value="'.$fieldId.'">'.$field['Name'].'</option>';
		}

		$checked = 'checked';
		if (isset($currentValues['PrintableVersion']))
			$checked = $currentValues['PrintableVersion'] == 'Y' ? 'checked' : '';

		$html .= '
			<tr>
				<td align="right" width="40%" class="adm-detail-content-cell-l">
					<span style="font-weight: bold">'.GetMessage("CRM_ACTIVITY_LABLE_SELECT_FIELDS").'</span>
				</td>
				<td width="60%">
					<select name="EntityFields[]" multiple>'.$options.'</select>
				</td>
			</tr>
			<tr>
				<td align="right" width="40%" class="adm-detail-content-cell-l">
					<span>'.GetMessage("CRM_ACTIVITY_LABLE_PRINTABLE_VERSION").'</span>
				</td>
				<td width="60%">
					<input type="checkbox" name="PrintableVersion" '.$checked.'>
				</td>
			</tr>
		';

		return $html;
	}

	protected static function getEntityFields($entityType)
	{
		$entityFields = [];
		$preparedFields = [];
		if (!CModule::IncludeModule('crm'))
			return [];

		switch ($entityType)
		{
			case 'LEAD':
				$preparedFields = CCrmDocumentLead::getEntityFields($entityType);
				break;
			case 'CONTACT':
				$listIgnoreFieldId = ['LEAD_ID', 'ORIGIN_ID', 'ORIGINATOR_ID'];
				$listIgnoreAddressFieldId = [];
				$preparedFields = CCrmDocumentContact::getEntityFields($entityType);
				if (Bitrix\Crm\Settings\ContactSettings::getCurrent()->areOutmodedRequisitesEnabled())
				{
					$listIgnoreAddressFieldId = ['ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_POSTAL_CODE',
						'ADDRESS_REGION', 'ADDRESS_PROVINCE', 'ADDRESS_COUNTRY'];
				}
				$listIgnoreFieldId = array_merge($listIgnoreFieldId, $listIgnoreAddressFieldId);
				foreach ($listIgnoreFieldId as $fieldId)
					unset($preparedFields[$fieldId]);
				break;
			case 'COMPANY':
				$listIgnoreFieldId = ['LEAD_ID', 'ORIGIN_ID', 'ORIGINATOR_ID', 'CONTACT_ID'];
				$listIgnoreAddressFieldId = [];
				$preparedFields = CCrmDocumentCompany::getEntityFields($entityType);
				if (Bitrix\Crm\Settings\CompanySettings::getCurrent()->areOutmodedRequisitesEnabled())
				{
					$listIgnoreAddressFieldId = ['ADDRESS', 'ADDRESS_LEGAL'];
				}
				$listIgnoreFieldId = array_merge($listIgnoreFieldId, $listIgnoreAddressFieldId);
				foreach ($listIgnoreFieldId as $fieldIdForDelete)
					unset($preparedFields[$fieldIdForDelete]);
				break;
			case 'DEAL':
				$listIgnoreFieldId = ['LEAD_ID', 'ORIGIN_ID', 'ORIGINATOR_ID', 'EVENT_DATE',
					'EVENT_ID', 'EVENT_DESCRIPTION', 'CATEGORY_ID'];
				$preparedFields = CCrmDocumentDeal::getEntityFields($entityType);
				foreach ($listIgnoreFieldId as $fieldId)
					unset($preparedFields[$fieldId]);
				break;
		}

		$userFields = ['CREATED_BY', 'CREATED_BY_ID', 'MODIFY_BY', 'MODIFY_BY_ID', 'ASSIGNED_BY', 'ASSIGNED_BY_ID'];
		foreach ($preparedFields as $fieldId => $field)
		{
			if (!$field['Editable'])
				continue;

			$entityFields[$fieldId] = $field;

			if (in_array($fieldId, $userFields))
			{
				$field['Name'] = $field['Name'].GetMessage("CRM_ACTIVITY_FIELD_USER_TITLE");
				$entityFields[$fieldId.'_USER'] = $field;
			}
		}

		return $entityFields;
	}

	protected static function checkEntityType($entityType)
	{
		return in_array($entityType, self::$listDefaultEntityType);
	}

	protected function getUserNameBySiteFormat(&$entityData)
	{
		$nameFormat = CSite::getNameFormat(false);
		$entityData['ASSIGNED_BY_FORMATTED_NAME'] = CUser::formatName(
			$nameFormat,
			[
				'LOGIN'       => $entityData['ASSIGNED_BY_LOGIN'],
				'NAME'        => $entityData['ASSIGNED_BY_NAME'],
				'LAST_NAME'   => $entityData['ASSIGNED_BY_LAST_NAME'],
				'SECOND_NAME' => $entityData['ASSIGNED_BY_SECOND_NAME']
			],
			true, false
		);
		$entityData['CREATED_BY_FORMATTED_NAME'] = CUser::formatName(
			$nameFormat,
			[
				'LOGIN'       => $entityData['CREATED_BY_LOGIN'],
				'NAME'        => $entityData['CREATED_BY_NAME'],
				'LAST_NAME'   => $entityData['CREATED_BY_LAST_NAME'],
				'SECOND_NAME' => $entityData['CREATED_BY_SECOND_NAME']
			],
			true, false
		);
		$entityData['MODIFY_BY_FORMATTED_NAME'] = CUser::formatName(
			$nameFormat,
			[
				'LOGIN'       => $entityData['MODIFY_BY_LOGIN'],
				'NAME'        => $entityData['MODIFY_BY_NAME'],
				'LAST_NAME'   => $entityData['MODIFY_BY_LAST_NAME'],
				'SECOND_NAME' => $entityData['MODIFY_BY_SECOND_NAME']
			],
			true, false
		);
		$honorific = isset($entityData['HONORIFIC']) ? $entityData['HONORIFIC'] : '';
		$name = isset($entityData['NAME']) ? $entityData['NAME'] : '';
		$secondName = isset($entityData['SECOND_NAME']) ? $entityData['SECOND_NAME'] : '';
		$lastName = isset($entityData['LAST_NAME']) ? $entityData['LAST_NAME'] : '';
		$entityData['FORMATTED_NAME'] = ($name !== '' || $secondName !== '' || $lastName !== '')
			? CCrmLead::PrepareFormattedName(
				[
					'HONORIFIC'   => $honorific,
					'NAME'        => $name,
					'SECOND_NAME' => $secondName,
					'LAST_NAME'   => $lastName
				]
			) : '';
	}

	protected function getUserByBPFormat(&$entityData)
	{
		$userFields = ['CREATED_BY', 'CREATED_BY_ID', 'MODIFY_BY', 'MODIFY_BY_ID', 'ASSIGNED_BY', 'ASSIGNED_BY_ID'];
		foreach ($userFields as $fieldId)
			if (!empty($entityData[$fieldId]))
				$entityData[$fieldId.'_USER'] = 'user_'.$entityData[$fieldId];
	}

	protected function getMultiValue($entityType, &$entityData)
	{
		$printableVersion = $this->PrintableVersion == 'Y' ? true : false;
		$objectResult = CCrmFieldMulti::getList(
			['ID' => 'asc'],
			['ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityData['ID']]
		);
		$entityData['FM'] = [];
		while ($multiFields = $objectResult->fetch())
		{
			$entityData['FM'][$multiFields['TYPE_ID']][$multiFields['ID']] = [
				'VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']];
		}
		$mutliFieldTypeInfos = CCrmFieldMulti::getEntityTypes();
		foreach ($entityData['FM'] as $fieldType => $listValueData)
		{
			$values = [];
			foreach ($listValueData as $fieldValue)
			{
				if ($printableVersion)
				{
					$values[] = $mutliFieldTypeInfos[$fieldType][$fieldValue['VALUE_TYPE']]['SHORT'].': '.$fieldValue['VALUE'];
				}
				else
				{
					$values[] = $fieldValue['VALUE'];
				}
			}
			$entityData[$fieldType] = $printableVersion ? implode('; ', $values) : $values;
		}
	}

	protected function getUfValue(CCrmUserType $CCrmUserType, $entityType, &$entityData, $selectedUfFields, $printableVersion)
	{
		global $USER_FIELD_MANAGER;
		$userFields = $USER_FIELD_MANAGER->getUserFields($CCrmUserType->sEntityID, $entityData['ID'], LANGUAGE_ID);
		$entityFields = self::getEntityFields($entityType);
		$documentId = $entityType.'_'.$entityData['ID'];
		foreach ($userFields as $ufField)
		{
			if (!in_array($ufField['FIELD_NAME'], $selectedUfFields))
				continue;

			switch ($ufField['USER_TYPE_ID'])
			{
				case 'employee':
					{
						if (is_array($ufField['VALUE']))
						{
							foreach ($ufField['VALUE'] as &$value)
								$value = 'user_'.$value;
						}
						else
						{
							$ufField['VALUE'] = 'user_'.$ufField['VALUE'];
						}
						break;
					}
				case 'enumeration':
					{
						if ($printableVersion)
						{
							$fieldTypeData = $entityFields[$ufField['FIELD_NAME']];
							$enumObject = new CUserFieldEnum;
							$enumQuery = $enumObject->getList([], ['USER_FIELD_ID' => $ufField['ID']]);
							$realListValue = [];
							while ($enum = $enumQuery->getNext())
							{
								$realListValue[$enum["ID"]] = $enum["VALUE"];
							}
							if (is_array($ufField['VALUE']))
							{
								foreach ($ufField['VALUE'] as &$value)
									$value = array_search($realListValue[$value], $fieldTypeData['Options']);
							}
							else
							{
								$ufField['VALUE'] = array_search($realListValue[$ufField['VALUE']], $fieldTypeData['Options']);
							}
						}
						break;
					}
				case 'boolean':
					$ufField['VALUE'] = CBPHelper::getBool($ufField['VALUE']) ? 'Y' : 'N';
					break;
			}

			if ($printableVersion)
			{
				switch ($entityType)
				{
					case 'LEAD':
						$entityData[$ufField['FIELD_NAME']] = CCrmDocumentLead::getFieldValuePrintable($documentId,
							$ufField['FIELD_NAME'], $ufField['USER_TYPE_ID'], $ufField['VALUE'],
							$entityFields[$ufField['FIELD_NAME']]);
						break;
					case 'CONTACT':
						$entityData[$ufField['FIELD_NAME']] = CCrmDocumentContact::getFieldValuePrintable($documentId,
							$ufField['FIELD_NAME'], $ufField['USER_TYPE_ID'], $ufField['VALUE'],
							$entityFields[$ufField['FIELD_NAME']]);
						break;
					case 'COMPANY':
						$entityData[$ufField['FIELD_NAME']] = CCrmDocumentCompany::getFieldValuePrintable($documentId,
							$ufField['FIELD_NAME'], $ufField['USER_TYPE_ID'], $ufField['VALUE'],
							$entityFields[$ufField['FIELD_NAME']]);
						break;
					case 'DEAL':
						$entityData[$ufField['FIELD_NAME']] = CCrmDocumentDeal::getFieldValuePrintable($documentId,
							$ufField['FIELD_NAME'], $ufField['USER_TYPE_ID'], $ufField['VALUE'],
							$entityFields[$ufField['FIELD_NAME']]);
						break;
				}
			}
			else
			{
				$entityData[$ufField['FIELD_NAME']] = $ufField['VALUE'];
			}
		}
	}
}

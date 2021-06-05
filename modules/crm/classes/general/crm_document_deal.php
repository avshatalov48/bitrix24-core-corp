<?

use Bitrix\Crm;

if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(dirname(__FILE__)."/crm_document.php");

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Main;

class CCrmDocumentDeal extends CCrmDocument
	implements IBPWorkflowDocument
{
	static public function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		self::appendReferenceFields(
			$arResult,
			\CCrmDocumentContact::getEntityFields(\CCrmOwnerType::ContactName),
			\CCrmOwnerType::Contact
		);

		self::appendReferenceFields(
			$arResult,
			\CCrmDocumentCompany::getEntityFields(\CCrmOwnerType::CompanyName),
			\CCrmOwnerType::Company
		);

		return $arResult;
	}

	private static function appendReferenceFields(array &$thisFields, array $referenceFields, $entityTypeId)
	{
		$fieldNamePrefix = \CCrmOwnerType::GetDescription($entityTypeId) . ': ';
		$fieldIdPrefix = \CCrmOwnerType::ResolveName($entityTypeId);

		foreach ($referenceFields as $id => $field)
		{
			if (mb_strpos($id, '.') !== false)
			{
				continue;
			}

			$field['Filterable'] = $field['Editable'] = false;
			$field['Name'] = $fieldNamePrefix.$field['Name'];
			$thisFields[$fieldIdPrefix.'.'.$id] = $field;
		}
	}

	public static function GetDocument($documentId)
	{
		$document = parent::GetDocument($documentId);
		if ($document)
		{
			if ($document['CONTACT_ID'])
			{
				$contact = parent::GetDocument('CONTACT_'.$document['CONTACT_ID']);
				if ($contact)
				{
					self::appendReferenceValues($document, $contact, \CCrmOwnerType::Contact);
				}
			}

			if ($document['COMPANY_ID'])
			{
				$company = parent::GetDocument( 'COMPANY_'.$document['COMPANY_ID']);
				if ($company)
				{
					self::appendReferenceValues($document, $company, \CCrmOwnerType::Company);
				}
			}
		}

		return $document;
	}

	private static function appendReferenceValues(array &$thisValues, array $referenceValues, $entityTypeId)
	{
		$idPrefix = \CCrmOwnerType::ResolveName($entityTypeId);
		foreach ($referenceValues as $id => $field)
		{
			$thisValues[$idPrefix.'.'.$id] = $field;
		}
	}

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			mb_strtolower($entityType).'.edit/component.php');

		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';

		$arResult = array(
			'ID' => array(
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'TITLE' => array(
				'Name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'OPPORTUNITY' => array(
				'Name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'CURRENCY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'OPPORTUNITY_ACCOUNT' => array(
				'Name' => GetMessage('CRM_FIELD_OPPORTUNITY_ACCOUNT'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'ACCOUNT_CURRENCY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_ACCOUNT_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'PROBABILITY' => array(
				'Name' => GetMessage('CRM_FIELD_PROBABILITY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ASSIGNED_BY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
		);

		$arResult += parent::getAssignedByFields();
		$arResult += array(
			'CREATED_BY_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_CREATED_BY_ID_DEAL'),
				'Type' => 'user',
			),
			'MODIFY_BY_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_MODIFY_BY_ID'),
				'Type' => 'user',
			),
			'CATEGORY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_CATEGORY_ID'),
				'Type' => 'select',
				'Options' => DealCategory::getSelectListItems(true),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false
			),
			'CATEGORY_ID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_CATEGORY_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'STAGE_ID' => array(
				'Name' => GetMessage('CRM_FIELD_STAGE_ID'),
				'Type' => 'select',
				'Options' => DealCategory::getFullStageList(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
				'Settings' => array('Groups' => DealCategory::getStageGroupInfos())
			),
			'STAGE_ID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_STAGE_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'CLOSED' => array(
				'Name' => GetMessage('CRM_FIELD_CLOSED'),
				'Type' => 'bool',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'TYPE_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_DEAL_TYPE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('DEAL_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'BEGINDATE' => array(
				'Name' => GetMessage('CRM_FIELD_BEGINDATE'),
				'Type' => 'date',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'CLOSEDATE' => array(
				'Name' => GetMessage('CRM_FIELD_CLOSEDATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EVENT_DATE' => array(
				'Name' => GetMessage('CRM_FIELD_EVENT_DATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EVENT_ID' => array(
				'Name' => GetMessage('CRM_FIELD_EVENT_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('EVENT_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EVENT_DESCRIPTION' => array(
				'Name' => GetMessage('CRM_FIELD_EVENT_DESCRIPTION'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			"OPENED" => array(
				"Name" => GetMessage("CRM_FIELD_OPENED"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"LEAD_ID" => array(
				"Name" => GetMessage("CRM_FIELD_LEAD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGINATOR_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGINATOR_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGIN_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGIN_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CONTACT_ID" => array(
				"Name" => GetMessage("CRM_FIELD_CONTACT_ID"),
				"Type" => "UF:crm",
				"Options" => array('CONTACT' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
			),
			"CONTACT_IDS" => array(
				"Name" => GetMessage("CRM_FIELD_CONTACT_IDS"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => true,
			),
			"OBSERVER_IDS" => array(
				"Name" => GetMessage("CRM_FIELD_OBSERVER_IDS"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => true,
			),
			"COMPANY_ID" => array(
				"Name" => GetMessage("CRM_FIELD_COMPANY_ID"),
				"Type" => "UF:crm",
				"Options" => array('COMPANY' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
			),
			'SOURCE_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_SOURCE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('SOURCE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'SOURCE_DESCRIPTION' => array(
				'Name' => GetMessage('CRM_DOCUMENT_FIELD_SOURCE_DESCRIPTION'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("CRM_DEAL_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_MODIFY" => array(
				"Name" => GetMessage("CRM_DEAL_EDIT_FIELD_DATE_MODIFY"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			'WEBFORM_ID' => array(
				'Name' => GetMessage('CRM_DOCUMENT_WEBFORM_ID'),
				'Type' => 'select',
				'Options' => static::getWebFormSelectOptions(),
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'IS_RETURN_CUSTOMER' => array(
				'Name' => GetMessage('CRM_DOCUMENT_DEAL_IS_RETURN_CUSTOMER'),
				'Type' => 'bool',
				'Editable' => false,
			),
			"ORDER_IDS" => array(
				"Name" => GetMessage("CRM_FIELD_ORDER_IDS"),
				"Type" => "int",
				"Multiple" => true,
			),
		);

		$arResult += static::getCommunicationFields();

		global $USER_FIELD_MANAGER;
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_DEAL');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		//append UTM fields
		$arResult += parent::getUtmFields();

		//append FORM fields
		$arResult += parent::getSiteFormFields(CCrmOwnerType::Deal);

		return $arResult;
	}

	static public function PrepareDocument(array &$arFields)
	{
		$categoryID = isset($arFields['CATEGORY_ID']) ? (int)$arFields['CATEGORY_ID'] : 0;
		$arFields['CATEGORY_ID_PRINTABLE'] = DealCategory::getName($categoryID);

		$stageID = isset($arFields['STAGE_ID']) ? $arFields['STAGE_ID'] : '';
		$arFields['STAGE_ID_PRINTABLE'] = DealCategory::getStageName($stageID, $categoryID);

		$arFields['CONTACT_IDS'] = Crm\Binding\DealContactTable::getDealContactIDs($arFields['ID']);

		$orderIds = Crm\Binding\OrderDealTable::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=DEAL_ID' => $arFields['ID'],
			],
			'order' => ['ORDER_ID' => 'DESC']
		])->fetchAll();

		$arFields['ORDER_IDS'] = array_column($orderIds, 'ORDER_ID');

		if ($arFields['COMPANY_ID'] <= 0)
		{
			//set empty value instead "0"
			$arFields['COMPANY_ID'] = null;
		}

		if ($arFields['CONTACT_ID'] <= 0)
		{
			//set empty value instead "0"
			$arFields['CONTACT_ID'] = null;
		}
	}

	static public function CreateDocument($parentDocumentId, $arFields)
	{
		if(!is_array($arFields))
		{
			throw new Exception("Entity fields must be array");
		}

		global $DB;
		$arDocumentID = self::GetDocumentInfo($parentDocumentId);
		if ($arDocumentID == false)
			$arDocumentID['TYPE'] = $parentDocumentId;

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);

			if ($arDocumentFields[$key]["Type"] == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (mb_substr($v1, 0, mb_strlen("user_")) == "user_")
					{
						$ar[] = mb_substr($v1, mb_strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, "DEAL_0");
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($arDocumentFields[$key]["Type"] == "select" && mb_substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_DEAL', $arFields, $key);
			}
			elseif ($arDocumentFields[$key]["Type"] == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if(isset($arFields['COMMENTS']))
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		if(isset($arFields['BEGINDATE']) && $arFields['BEGINDATE'] instanceof Main\Type\Date)
		{
			$arFields['BEGINDATE'] = (string) $arFields['BEGINDATE'];
		}
		if(isset($arFields['CLOSEDATE']) && $arFields['CLOSEDATE'] instanceof Main\Type\Date)
		{
			$arFields['CLOSEDATE'] = (string) $arFields['CLOSEDATE'];
		}

		//region Category & Stage
		if(isset($arFields['STAGE_ID']))
		{
			if($arFields['STAGE_ID'] === '')
			{
				unset($arFields['STAGE_ID']);
			}
			else
			{
				$stageID = $arFields['STAGE_ID'];
				$stageCategoryID = DealCategory::resolveFromStageID($stageID);
				if(!isset($arFields['CATEGORY_ID']))
				{
					$arFields['CATEGORY_ID'] = $stageCategoryID;
				}
				else
				{
					$categoryID = (int)$arFields['CATEGORY_ID'];
					if($categoryID !== $stageCategoryID)
					{
						throw new Exception(
							GetMessage(
								'CRM_DOCUMENT_DEAL_STAGE_MISMATCH_ERROR',
								array(
									'#CATEGORY#' => DealCategory::getName($categoryID),
									'#TARG_CATEGORY#' => DealCategory::getName($stageCategoryID),
									'#TARG_STAGE#' => DealCategory::getStageName($stageID, $stageCategoryID)
								)
							)
						);
					}
				}
			}
		}
		//endregion

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		$CCrmEntity = new CCrmDeal(false);
		$id = $CCrmEntity->Add(
			$arFields,
			true,
			array('REGISTER_SONET_EVENT' => true, 'CURRENT_USER' => static::getSystemUserId())
		);

		if (!$id || $id <= 0)
		{
			if ($useTransaction)
			{
				$DB->Rollback();
			}
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('DEAL');
			if (false === $CCrmBizProc->CheckFields(false, true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($id && $id > 0 && !$CCrmBizProc->StartWorkflow($id))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		//Region automation
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Deal, $id);
		$starter->setContextToBizproc()->runOnAdd();
		//End region

		if ($id && $id > 0 && $useTransaction)
		{
			$DB->Commit();
		}

		return $id;
	}

	public static function UpdateDocument($documentId, $arFields, $modifiedById = null)
	{
		global $DB;

		if(empty($arFields))
		{
			return;
		}

		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$dbDocumentList = CCrmDeal::GetListEx(
			array(),
			array('ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'CATEGORY_ID', 'STAGE_ID')
		);

		$arPresentFields = $dbDocumentList->Fetch();
		if (!is_array($arPresentFields))
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);

			if ($arDocumentFields[$key]["Type"] == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (mb_substr($v1, 0, mb_strlen("user_")) == "user_")
					{
						$ar[] = mb_substr($v1, mb_strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $documentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($arDocumentFields[$key]["Type"] == "select" && mb_substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_DEAL', $arFields, $key);
			}
			elseif ($arDocumentFields[$key]["Type"] == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					if (\CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions))
					{
						global $USER_FIELD_MANAGER;
						if ($USER_FIELD_MANAGER instanceof \CUserTypeManager)
						{
							$prevValue = $USER_FIELD_MANAGER->GetUserFieldValue(
								\CCrmOwnerType::ResolveUserFieldEntityID(\CCrmOwnerType::Deal),
								$key,
								$arDocumentID['ID']
							);
							if ($prevValue)
							{
								$file['old_id'] = $prevValue;
							}
						}
					}
					$value = $file;
				}
				unset($value, $prevValue);
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if(isset($arFields['COMMENTS']) && $arFields['COMMENTS'] !== '')
		{
			$arFields['COMMENTS'] = static::sanitizeCommentsValue($arFields['COMMENTS']);
		}

		if(isset($arFields['BEGINDATE']) && $arFields['BEGINDATE'] instanceof Main\Type\Date)
		{
			$arFields['BEGINDATE'] = (string) $arFields['BEGINDATE'];
		}
		if(isset($arFields['CLOSEDATE']) && $arFields['CLOSEDATE'] instanceof Main\Type\Date)
		{
			$arFields['CLOSEDATE'] = (string) $arFields['CLOSEDATE'];
		}

		//region Category & Stage
		$categoryID = isset($arPresentFields['CATEGORY_ID']) ? (int)$arPresentFields['CATEGORY_ID'] : 0;
		if(isset($arFields['CATEGORY_ID']) && $arFields['CATEGORY_ID'] != $categoryID)
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_DEAL_CATEGORY_CHANGE_ERROR'));
		}

		if(isset($arFields['STAGE_ID']))
		{
			if($arFields['STAGE_ID'] === '')
			{
				unset($arFields['STAGE_ID']);
			}
			else
			{
				$stageID = $arFields['STAGE_ID'];
				$stageCategoryID = DealCategory::resolveFromStageID($stageID);
				if($stageCategoryID !== $categoryID)
				{
					throw new Exception(
						GetMessage(
							'CRM_DOCUMENT_DEAL_STAGE_MISMATCH_ERROR',
							array(
								'#CATEGORY#' => DealCategory::getName($categoryID),
								'#TARG_CATEGORY#' => DealCategory::getName($stageCategoryID),
								'#TARG_STAGE#' => DealCategory::getStageName($stageID, $stageCategoryID)
							)
						)
					);
				}
			}
		}
		//endregion

		if(empty($arFields))
		{
			return;
		}

		$useTransaction = static::shouldUseTransaction();

		if ($useTransaction)
		{
			$DB->StartTransaction();
		}

		if ($modifiedById > 0)
		{
			$arFields['MODIFY_BY_ID'] = $modifiedById;
		}

		$CCrmEntity = new CCrmDeal(false);
		$res = $CCrmEntity->Update(
			$arDocumentID['ID'],
			$arFields,
			true,
			true,
			[
				'DISABLE_USER_FIELD_CHECK' => true,
				'REGISTER_SONET_EVENT' => true,
				'CURRENT_USER' => static::getSystemUserId()
			]
		);

		if (!$res)
		{
			if ($useTransaction)
			{
				$DB->Rollback();
			}
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('DEAL');
			if (false === $CCrmBizProc->CheckFields($arDocumentID['ID'], true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($res && !$CCrmBizProc->StartWorkflow($arDocumentID['ID']))
			{
				if ($useTransaction)
				{
					$DB->Rollback();
				}
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}
		//Region automation
		$starter = new Crm\Automation\Starter(\CCrmOwnerType::Deal, $arDocumentID['ID']);
		$starter->setContextToBizproc()->runOnUpdate($arFields, $arPresentFields);
		//End region

		if ($res && $useTransaction)
		{
			$DB->Commit();
		}
	}

	public static function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		$dbRes = CCrmDeal::GetListEx([], ['=ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'],
			false, false, ['TITLE']
		);
		$arRes = $dbRes ? $dbRes->Fetch() : null;
		return $arRes ? $arRes['TITLE'] : '';
	}

	public static function normalizeDocumentId($documentId)
	{
		return parent::normalizeDocumentIdInternal(
			$documentId,
			CCrmOwnerType::DealName,
			CCrmOwnerTypeAbbr::Deal
		);
	}

	public static function createAutomationTarget($documentType)
	{
		return Crm\Automation\Factory::createTarget(\CCrmOwnerType::Deal);
	}
}

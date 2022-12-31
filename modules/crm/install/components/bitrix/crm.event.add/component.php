<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

use Bitrix\Crm\Service\UserPermissions;

global $USER;


$CCrmPerms = \CCrmAuthorizationHelper::GetUserPermissions();

$entityTypeName = isset($arParams['ENTITY_TYPE']) ? $arParams['ENTITY_TYPE'] : '';
$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
$entityID = isset($arParams['ENTITY_ID']) ? (int)$arParams['ENTITY_ID'] : 0;

if($entityTypeID === CCrmOwnerType::Undefined
	|| !\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($entityTypeID, $entityID, $CCrmPerms)
)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if(!function_exists('__CrmEventGetPhones'))
{
	function __CrmEventGetPhones($entityID, $elementID)
	{
		$result = array();
		$arFields = CCrmFieldMulti::GetEntityFields($entityID, $elementID, 'PHONE', true, false);
		foreach($arFields as $arField)
		{
			$result[] = array(
				'TITLE' => $arField['ENTITY_NAME'],
				'NUMBER' => $arField['VALUE']
			);
		}
		return $result;
	}
}

CUtil::InitJSCore();
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$eventPage = isset($_POST['EVENT_PAGE']) ? $_POST['EVENT_PAGE'] : '';
	if($eventPage === '' || !CCrmUrlUtil::IsSecureUrl($eventPage))
	{
		$eventPage = $GLOBALS['APPLICATION']->GetCurPage();
	}

	$formID = isset($_POST['FORM_ID']) ? $_POST['FORM_ID'] : '';
	if($formID === '')
	{
		$formID = 'CRM_'.trim($_POST['ENTITY_TYPE']).'_'.trim($_POST['FORM_TYPE']).'_V12';
	}

	$arResult['EVENT_PAGE'] = CHTTP::urlAddParams(
		$eventPage,
		array($formID.'_active_tab' => (!empty($_POST['TAB_ID']) ? $_POST['TAB_ID'] : 'tab_event'))
	);

	if (check_bitrix_sessid())
	{
		$entityTypeID = isset($_POST['ENTITY_TYPE']) ? trim($_POST['ENTITY_TYPE']) : '';
		$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
		$eventID = isset($_POST['EVENT_ID']) ? trim($_POST['EVENT_ID']) : '';
		$eventDesc = isset($_POST['EVENT_DESC']) ? trim($_POST['EVENT_DESC']) : '';
		$eventDate = isset($_POST['EVENT_DATE']) ? trim($_POST['EVENT_DATE']) : '';
		$postFiles = isset($_FILES['ATTACH'])? $_FILES['ATTACH'] : array();
		$attachedFiles = array();
		if(!empty($postFiles))
		{
			CFile::ConvertFilesToPost($postFiles, $attachedFiles);
		}

		$CCrmEvent = new CCrmEvent();

		$eventFiles = array();
		foreach($attachedFiles as &$arFile)
		{
			if(isset($arFile['tmp_name']) && is_uploaded_file($arFile['tmp_name']))
			{
				$eventFiles[] = $arFile;
			}
		}
		unset($arFile);

		if($eventDate !== '')
		{
			if(!CheckDateTime($eventDate, FORMAT_DATETIME))
			{
				$eventDate = '';
			}
			else
			{
				// Check for max database datetime
				$eventTimestamp = MakeTimeStamp($eventDate, FORMAT_DATETIME);
				if(!is_int($eventTimestamp))
				{
					$eventDate = '';
				}
				else
				{
					$maxDataTime = new DateTime('9999-12-31T23:59:59');
					$maxTimestamp = $maxDataTime->format('U');

					if($maxTimestamp < $eventTimestamp)
					{
						$eventDate = '';
					}
				}
			}
		}

		if(!($eventDate !== '' && CheckDateTime($eventDate, FORMAT_DATETIME)))
		{
			$eventDate = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', SITE_ID);
		}

		$CCrmEvent->Add(
			array(
				'ENTITY_TYPE'=> $entityTypeID,
				'ENTITY_ID' => $entityID,
				'EVENT_ID' => $eventID,
				'EVENT_TEXT_1' => $eventDesc,
				'DATE_CREATE' => $eventDate,
				'FILES' => $eventFiles
			)
		);

		switch ($entityTypeID)
		{
			case 'LEAD':
			{
				if (isset($_POST['STATUS_ID']))
				{
					$statusID = $_POST['STATUS_ID'];

					$dbResult = CCrmLead::GetListEx(
						array(),
						array('=ID' => $entityID),
						false,
						false,
						array('STATUS_ID')
					);

					$arPrevious = $dbResult ? $dbResult->Fetch() : null;
					if(is_array($arPrevious)
						&& isset($arPrevious['STATUS_ID'])
						&& $arPrevious['STATUS_ID'] !== $statusID)
					{
						$CCrmLead = new CCrmLead();
						$arField = array('STATUS_ID' => $statusID);
						if($CCrmLead->Update($entityID, $arField))
						{
							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Lead,
								$entityID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);
						}
						//Region automation
						$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $entityID);
						$starter->setUserIdFromCurrent()->runOnUpdate($arField, []);
						//end region
					}
				}

				$dbRes = CCrmLead::GetListEx(
					array(),
					array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'STATUS_ID', 'CONTACT_ID', 'COMPANY_ID')
				);

				$arLead = $dbRes->Fetch();
				if(is_array($arLead))
				{
					$statusID = isset($arLead['STATUS_ID']) ? $arLead['STATUS_ID'] : '';

					$contactID = isset($arLead['CONTACT_ID']) ? intval($arLead['CONTACT_ID']) : 0;
					if($contactID > 0 && !CCrmContact::Exists($contactID))
					{
						$contactID = 0;
					}

					$companyID = isset($arLead['COMPANY_ID']) ? intval($arLead['COMPANY_ID']) : 0;
					if($companyID > 0 && !CCrmCompany::Exists($companyID))
					{
						$companyID = 0;
					}

					if($statusID === 'CONVERTED')
					{
						if($contactID !== 0)
						{
							$CCrmEvent->Add(
								array(
									'ENTITY_TYPE'=> 'CONTACT',
									'ENTITY_ID' => $contactID,
									'EVENT_ID' => $eventID,
									'EVENT_TEXT_1' => $eventDesc,
									'DATE_CREATE' => $eventDate,
									'FILES' => $eventFiles
								)
							);
						}
						if($companyID !== 0)
						{
							$CCrmEvent->Add(
								array(
									'ENTITY_TYPE'=> 'COMPANY',
									'ENTITY_ID' => $companyID,
									'EVENT_ID' => $eventID,
									'EVENT_TEXT_1' => $eventDesc,
									'DATE_CREATE' => $eventDate,
									'FILES' => $eventFiles
								)
							);
						}
					}
				}
			}
			break;
			case 'CONTACT':
				$CCrmContact = new CCrmContact();
				$arField = [];
				$CCrmContact->Update($_POST['ENTITY_ID'], $arField, false);
			break;
			case 'COMPANY':
				$CCrmCompany = new CCrmCompany();
				$arField = [];
				$CCrmCompany->Update($_POST['ENTITY_ID'], $arField, false);
			break;
			case 'DEAL':
				if (isset($_POST['STAGE_ID']))
				{
					$stageID = $_POST['STAGE_ID'];

					$dbResult = CCrmDeal::GetListEx(
						array(),
						array('=ID' => $entityID),
						false,
						false,
						array('STAGE_ID')
					);

					$arPrevious = $dbResult ? $dbResult->Fetch() : null;
					if(is_array($arPrevious)
						&& isset($arPrevious['STAGE_ID'])
						&& $arPrevious['STAGE_ID'] !== $stageID)
					{
						$CCrmDeal = new CCrmDeal();
						$arField = array('STAGE_ID' => $stageID);
						if($CCrmDeal->Update($entityID, $arField))
						{
							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Deal,
								$entityID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);
						}
						//Region automation
						$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $entityID);
						$starter->setUserIdFromCurrent()->runOnUpdate($arField, []);
						//end region
					}
				}
			break;
			case 'QUOTE':
				if (isset($_POST['STATUS_ID']))
				{
					$statusID = $_POST['STATUS_ID'];

					$dbResult = CCrmQuote::GetList(
						array(),
						array('=ID' => $entityID),
						false,
						false,
						array('STATUS_ID')
					);

					$arPrevious = $dbResult ? $dbResult->Fetch() : null;
					if(is_array($arPrevious)
						&& isset($arPrevious['STATUS_ID'])
						&& $arPrevious['STATUS_ID'] !== $statusID)
					{
						$CCrmDeal = new CCrmQuote();
						$arField = array('STATUS_ID' => $statusID);
						if($CCrmDeal->Update($entityID, $arField))
						{
							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Quote,
								$entityID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);
						}
					}
				}
			break;
			case 'ORDER':
				$arErrors = array();
				CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Order,
					$entityID,
					CCrmBizProcEventType::Edit,
					$arErrors
				);
				break;

			default:
				$stageId = $_POST['STAGE_ID'] ?? null;
				if (!$stageId)
				{
					break;
				}

				$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::ResolveID($entityTypeID));
				if (!$factory)
				{
					break;
				}

				$item = $factory->getItem((int)$_POST['ENTITY_ID']);
				if (!$item)
				{
					break;
				}

				$item->setStageId($stageId);
				$factory->getUpdateOperation($item)->launch();
				break;
		}
	}
}
else
{
	$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
	$arResult['FORM_TYPE'] = $arParams['FORM_TYPE'];
	$arResult['ENTITY_TYPE'] = $arParams['ENTITY_TYPE'];
	$arResult['ENTITY_ID'] 	= intval($arParams['ENTITY_ID']);
	$arResult['ENTITY_TITLE'] = GetMessage('CRM_EVENT_DEFAULT_TITLE');
	$arResult['EVENT_TYPE'] = $arParams['EVENT_TYPE'];

	$arResult['FREEZE_EVENT_ID'] = isset($arParams['FREEZE_EVENT_ID'])? mb_strtoupper($arParams['FREEZE_EVENT_ID']) : '';

	if($arParams['EVENT_TYPE'] === 'PHONE')
	{
		$arResult['PHONE_GROUPS'] = array();
	}

	switch ($arParams['ENTITY_TYPE'])
	{
		case 'LEAD':
			$dbRes = CCrmLead::GetListEx(
				array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'),
				array('ID' => $arResult['ENTITY_ID'])
			);
			if ($arRes = $dbRes->Fetch())
			{
				$arResult['ENTITY_TITLE'] = $arRes['TITLE'];
				$arResult['STATUS_ID'] = $arRes['STATUS_ID'];
				$arResult['ENTITY_CONVERTED'] = $arRes['STATUS_ID'] == 'CONVERTED'? 'Y': 'N';
			}
			$arEntityAttr = $CCrmPerms->GetEntityAttr('LEAD', $arResult['ENTITY_ID']);
			if ($CCrmPerms->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$arResult['ENTITY_ID']]))
			{
				$arResult['STATUS_LIST'] = array();
				$arResult['STATUS_LIST_EX'] = CCrmStatus::GetStatusList('STATUS');
				foreach($arResult['STATUS_LIST_EX'] as $key => $value)
				{
					if ($key == 'CONVERTED')
						continue;
					if ($CCrmPerms->GetPermType('LEAD', 'WRITE', array('STATUS_ID'.$key)) > BX_CRM_PERM_NONE)
					{
						$arResult['STATUS_LIST']['REFERENCE'][] = $value;
						$arResult['STATUS_LIST']['REFERENCE_ID'][] = $key;
					}
				}

				$arResult['PHONE_GROUPS'][] = array(
					'PHONES' => __CrmEventGetPhones('LEAD', $arResult['ENTITY_ID'])
				);
			}
		break;
		case 'CONTACT':

			$dbRes = CCrmContact::GetListEx(
				array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'),
				array('ID' => $arResult['ENTITY_ID'])
			);
			if ($arRes = $dbRes->Fetch())
				$arResult['ENTITY_TITLE'] = $arRes['LAST_NAME'].' '.$arRes['NAME'];

		break;
		case 'COMPANY':

			$dbRes = CCrmCompany::GetListEx(
				array('TITLE'=>'ASC'),
				array('ID' => $arResult['ENTITY_ID'])
			);
			if ($arRes = $dbRes->Fetch())
				$arResult['ENTITY_TITLE'] = $arRes['TITLE'];

		break;
		case 'DEAL':
			$categoryID = CCrmDeal::GetCategoryID($arResult['ENTITY_ID']);
			$arEntityAttr = CCrmDeal::GetPermissionAttributes(array($arResult['ENTITY_ID']), $categoryID);
			if (CCrmDeal::CheckUpdatePermission($arResult['ENTITY_ID'], $CCrmPerms, $categoryID, array('ENTITY_ATTRS' => $arEntityAttr)))
			{
				$dbRes = CCrmDeal::GetListEx(
					array('TITLE'=>'ASC'),
					array('ID' => $arResult['ENTITY_ID'])
				);
				if ($arRes = $dbRes->Fetch())
				{
					$arResult['ENTITY_TITLE'] = $arRes['TITLE'];
					$arResult['STAGE_ID'] = $arRes['STAGE_ID'];
				}
				$arResult['STAGE_LIST'] = Array();
				$arEventType = CCrmDeal::GetStageNames($categoryID);
				foreach($arEventType as $key => $value)
				{
					if(CCrmDeal::GetStageUpdatePermissionType($key, $CCrmPerms, $categoryID) > BX_CRM_PERM_NONE)
					{
						$arResult['STAGE_LIST']['REFERENCE'][] = $value;
						$arResult['STAGE_LIST']['REFERENCE_ID'][] = $key;
					}
				}

				if($arParams['EVENT_TYPE'] === 'PHONE')
				{
					$contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
					if($contactID > 0)
					{
						$rsContacts = CCrmContact::GetListEx(
							array(),
							array('ID' => $contactID),
							false,
							false,
							array('FULL_NAME')
						);
						$arContact = $rsContacts->Fetch();
						if($arContact)
						{
							$arResult['PHONE_GROUPS'][] = array(
								'TITLE' => $arContact['FULL_NAME'],
								'PHONES' => __CrmEventGetPhones('CONTACT', $contactID)
							);
						}
					}

					$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
					if($companyID > 0)
					{
						$rsCompanies = CCrmCompany::GetListEx(
							array(),
							array('ID' => $companyID),
							false,
							false,
							array('TITLE')
						);
						$arCompany = $rsCompanies->Fetch();
						if($arCompany)
						{
							$arResult['PHONE_GROUPS'][] = array(
								'TITLE' => $arCompany['TITLE'],
								'PHONES' => __CrmEventGetPhones('COMPANY', $companyID)
							);
						}
					}
				}
			}
		break;
		case 'QUOTE':
			$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', $arResult['ENTITY_ID']);
			if ($CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$arResult['ENTITY_ID']]))
			{
				$dbRes = CCrmQuote::GetList(Array('TITLE'=>'ASC'), array('ID' => $arResult['ENTITY_ID']));
				if ($arRes = $dbRes->Fetch())
				{
					$arResult['ENTITY_TITLE'] = $arRes['TITLE'];
					$arResult['STATUS_ID'] = $arRes['STATUS_ID'];
				}
				$arResult['STATUS_LIST'] = Array();
				$arEventType = CCrmStatus::GetStatusList('QUOTE_STATUS');
				foreach($arEventType as $key => $value)
				{
					if ($CCrmPerms->GetPermType('QUOTE', 'WRITE', array('QUOTE_STATUS'.$key)) > BX_CRM_PERM_NONE)
					{
						$arResult['STATUS_LIST']['REFERENCE'][] = $value;
						$arResult['STATUS_LIST']['REFERENCE_ID'][] = $key;
					}
				}

				if($arParams['EVENT_TYPE'] === 'PHONE')
				{
					$contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
					if($contactID > 0)
					{
						$rsContacts = CCrmContact::GetListEx(
							array(),
							array('ID' => $contactID),
							false,
							false,
							array('FULL_NAME'));
						$arContact = $rsContacts->Fetch();
						if($arContact)
						{
							$arResult['PHONE_GROUPS'][] = array(
								'TITLE' => $arContact['FULL_NAME'],
								'PHONES' => __CrmEventGetPhones('CONTACT', $contactID)
							);
						}
					}

					$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
					if($companyID > 0)
					{
						$rsCompanies = CCrmCompany::GetList(array(), array('ID' => $companyID), array('TITLE'), 1);
						$arCompany = $rsCompanies->Fetch();
						if($arCompany)
						{
							$arResult['PHONE_GROUPS'][] = array(
								'TITLE' => $arCompany['TITLE'],
								'PHONES' => __CrmEventGetPhones('COMPANY', $companyID)
							);
						}
					}
				}
			}
		break;
	case 'ORDER':
		$arEntityAttr = $CCrmPerms->GetEntityAttr('ORDER', $arResult['ENTITY_ID']);
		if ($CCrmPerms->CheckEnityAccess('ORDER', 'WRITE', $arEntityAttr[$arResult['ENTITY_ID']]))
		{
			$dbRes = Bitrix\Crm\Order\Order::getList(array(
				'filter' => array('=ID' => $arResult['ENTITY_ID']),
				'order' => array('ACCOUNT_NUMBER' => 'ASC', 'ORDER_TOPIC'=>'ASC')
			));

			if ($arRes = $dbRes->Fetch())
			{
				$arResult['ENTITY_TITLE'] = 'N '.$arRes['ACCOUNT_NUMBER'].(!empty($arRes['ORDER_TOPIC']) ? ' ('.$arRes['ORDER_TOPIC'].')' : '');
				$arResult['STATUS_ID'] = $arRes['STATUS_ID'];
			}

			$arResult['STATUS_LIST'] = Array();
			$statusList = \Bitrix\Crm\Order\OrderStatus::getListInCrmFormat();
			foreach ($statusList as $status)
			{
				$arEventType[$status['STATUS_ID']] = $status['NAME'];
			}

			foreach($arEventType as $key => $value)
			{
				if ($CCrmPerms->GetPermType('ORDER', 'WRITE', array('ORDER_STATUS'.$key)) > BX_CRM_PERM_NONE)
				{
					$arResult['STATUS_LIST']['REFERENCE'][] = $value;
					$arResult['STATUS_LIST']['REFERENCE_ID'][] = $key;
				}
			}

			if($arParams['EVENT_TYPE'] === 'PHONE')
			{
				$contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
				if($contactID > 0)
				{
					$rsContacts = CCrmContact::GetListEx(
						array(),
						array('ID' => $contactID),
						false,
						false,
						array('FULL_NAME'));
					$arContact = $rsContacts->Fetch();
					if($arContact)
					{
						$arResult['PHONE_GROUPS'][] = array(
							'TITLE' => $arContact['FULL_NAME'],
							'PHONES' => __CrmEventGetPhones('CONTACT', $contactID)
						);
					}
				}

				$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
				if($companyID > 0)
				{
					$rsCompanies = CCrmCompany::GetList(array(), array('ID' => $companyID), array('TITLE'), 1);
					$arCompany = $rsCompanies->Fetch();
					if($arCompany)
					{
						$arResult['PHONE_GROUPS'][] = array(
							'TITLE' => $arCompany['TITLE'],
							'PHONES' => __CrmEventGetPhones('COMPANY', $companyID)
						);
					}
				}
			}
		}
		break;

		default:
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(
				\CCrmOwnerType::ResolveID($arResult['ENTITY_TYPE'])
			);
			if (!$factory)
			{
				break;
			}

			$item = $factory->getItem((int)$arResult['ENTITY_ID']);
			if (!$item)
			{
				break;
			}

			if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->canUpdateItem($item))
			{
				break;
			}

			$arResult['ENTITY_TYPE_CAPTION'] = $factory->getEntityDescription();
			$arResult['ENTITY_TITLE'] = $item->getHeading();
			if ($factory->isStagesEnabled())
			{
				$arResult['STAGE_ID'] = $item->getStageId();
				$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions();
				foreach ($factory->getStages($item->getCategoryId()) as $stage)
				{
					if ($userPermissions->getPermissionType($item, UserPermissions::OPERATION_UPDATE) > UserPermissions::PERMISSION_NONE)
					{
						$arResult['FACTORY_STAGE_LIST']['REFERENCE'][] = $stage->getName();
						$arResult['FACTORY_STAGE_LIST']['REFERENCE_ID'][] = $stage->getStatusId();
					}
				}
			}
			break;
	}

	$arResult['EVENT_TYPE'] = Array();
	$arEventType = CCrmStatus::GetStatusList('EVENT_TYPE');
	foreach($arEventType as $key => $value)
	{
		if ($arResult['ENTITY_TYPE'] !== 'QUOTE' || !($key === 'PHONE' || $key === 'MESSAGE'))
		{
			$arResult['EVENT_TYPE']['REFERENCE'][] = $value;
			$arResult['EVENT_TYPE']['REFERENCE_ID'][] = $key;
		}
	}
}

if($arParams['EVENT_TYPE'] === 'PHONE')
{
	$this->__templateName = 'phone';
}
$this->IncludeComponentTemplate();
?>

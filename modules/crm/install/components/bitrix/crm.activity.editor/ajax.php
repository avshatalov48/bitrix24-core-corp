<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

use Bitrix\Main\Config;
use Bitrix\Main\Mail;
use Bitrix\Main\Loader;
use Bitrix\Mail\Helper;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE_ACTIVITY' - save activity (CALL, MEETING)
 * 'SAVE_EMAIL'
 * 'SET_NOTIFY' - change notification settings
 * 'SET_PRIORITY'
 * 'COMPLETE' - mark activity as completed
 * 'POSTPONE'
 * 'DELETE' - delete activity
 * 'GET_ENTITY_COMMUNICATIONS' - get entity communications
 * 'GET_ACTIVITY_COMMUNICATIONS_PAGE'
 * 'GET_TASK'
 * 'SEARCH_COMMUNICATIONS'
 * 'GET_ACTIVITIES'
 * 'GET_WEBDAV_ELEMENT_INFO'
 * 'PREPARE_MAIL_TEMPLATE'
 */

global $DB, $APPLICATION;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
if(!function_exists('__CrmActivityEditorEndResponse'))
{
	function __CrmActivityEditorEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
	__CrmActivityEditorEndResponse(array('ERROR' => getMessage('CRM_PERMISSION_DENIED')));

if (($_REQUEST['soc_net_log_dest'] ?? null) === 'search_email_comms')
{
	$_POST['ACTION'] = 'SEARCH_COMMUNICATIONS';
	$_POST['COMMUNICATION_TYPE'] = 'EMAIL';
	$_POST['NEEDLE'] = $_POST['SEARCH'];
}

$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action == '')
{
	__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data!'));
}

function GetCrmActivityCommunications($ID)
{
	$communications = CCrmActivity::GetCommunications($ID);
	$communicationData = array();
	if(is_array($communications))
	{
		foreach($communications as &$comm)
		{
			CCrmActivity::PrepareCommunicationInfo($comm);
			$datum = array(
				'id' => $comm['ID'],
				'type' => $comm['TYPE'],
				'value' => $comm['VALUE'],
				'entityId' => $comm['ENTITY_ID'],
				'entityType' => CCrmOwnerType::ResolveName($comm['ENTITY_TYPE_ID']),
				'entityTitle' => $comm['TITLE'],
				'entityUrl' => CCrmOwnerType::GetEntityShowPath($comm['ENTITY_TYPE_ID'], $comm['ENTITY_ID'])
			);

			if($datum['type'] === 'PHONE' && CCrmSipHelper::checkPhoneNumber($datum['value']))
			{
				$datum['enableSip'] = true;
			}

			$communicationData[] = &$datum;
			unset($datum);
		}
		unset($comm);
	}

	return array('DATA' => array(
		'ID' => $ID,
		'COMMUNICATIONS' => $communicationData
		)
	);
}
function GetCrmActivityCommunicationsPage($ID, $pageSize, $pageNumber)
{
	$dbRes = CCrmActivity::GetCommunicationList(
		array('ID' => 'ASC'),
		array('ACTIVITY_ID' => $ID),
		false,
		array('bShowAll' => false, 'nPageSize' => $pageSize, 'iNumPage' => $pageNumber)
	);

	$communicationData = array();
	while($result = $dbRes->Fetch())
	{
		$result['ENTITY_SETTINGS'] = isset($result['ENTITY_SETTINGS']) && $result['ENTITY_SETTINGS'] !== '' ? unserialize($result['ENTITY_SETTINGS'], ['allowed_classes' => false]) : array();
		CCrmActivity::PrepareCommunicationInfo($result);
		$communicationData[] = array(
			'id' => $result['ID'],
			'type' => $result['TYPE'],
			'value' => $result['VALUE'],
			'entityId' => $result['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($result['ENTITY_TYPE_ID']),
			'entityTitle' => $result['TITLE'],
			'entityUrl' => CCrmOwnerType::GetEntityShowPath($result['ENTITY_TYPE_ID'], $result['ENTITY_ID'])
		);
	}

	return array(
		'DATA' => array(
			'ID' => $ID,
			'PAGE_SIZE'=> $dbRes->NavPageSize,
			'PAGE_NUMBER'=> $dbRes->NavPageNomer,
			'PAGE_COUNT'=> $dbRes->NavPageCount,
			'COMMUNICATIONS' => $communicationData
		)
	);
}
function GetCrmEntityCommunications($entityType, $entityID, $communicationType)
{
	if($entityType === 'LEAD')
	{
		$data = array(
			'ownerEntityType' => 'LEAD',
			'ownerEntityId' => $entityID,
			'entityType' => 'LEAD',
			'entityId' => $entityID,
			'entityTitle' => "{$entityType}_{$entityID}",
			'entityDescription' => '',
			'tabId' => 'main',
			'communications' => array()
		);

		$entity = CCrmLead::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		// Prepare title
		$title = isset($entity['TITLE']) ? $entity['TITLE'] : '';
		$honorific = isset($entity['HONORIFIC']) ? $entity['HONORIFIC'] : '';
		$name = isset($entity['NAME']) ? $entity['NAME'] : '';
		$secondName = isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : '';
		$lastName = isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '';

		if($title !== '')
		{
			$data['entityTitle'] = $title;
			$data['entityDescription'] = CCrmLead::PrepareFormattedName(
				array(
					'HONORIFIC' => $honorific,
					'NAME' => $name,
					'SECOND_NAME' => $secondName,
					'LAST_NAME' => $lastName
				)
			);
		}
		else
		{
			$data['entityTitle'] = CCrmLead::PrepareFormattedName(
				array(
					'HONORIFIC' => $honorific,
					'NAME' => $name,
					'SECOND_NAME' => $secondName,
					'LAST_NAME' => $lastName
				)
			);
			$data['entityDescription'] = '';
		}

		// Try to load entity communications
		if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID))
		{
			return array('ERROR' => GetMessage('CRM_PERMISSION_DENIED'));
		}

		if($communicationType !== '')
		{
			$dbResFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
			);

			while($arField = $dbResFields->Fetch())
			{
				if(empty($arField['VALUE']))
				{
					continue;
				}

				$comm = array('type' => $communicationType, 'value' => $arField['VALUE']);
				$data['communications'][] = $comm;
			}
		}

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'lead',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_LEAD'), 'active' => true, 'items' => array($data))
				)
			)
		);
	}
	elseif($entityType === 'DEAL')
	{
		$entity = CCrmDeal::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		$dealData = array();

		// Prepare company data
		$entityCompanyData = null;
		$entityCompanyID =  isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
		$entityCompany = $entityCompanyID > 0 ? CCrmCompany::GetByID($entityCompanyID) : null;

		if(is_array($entityCompany))
		{
			$entityCompanyData = array(
				'ownerEntityType' => 'DEAL',
				'ownerEntityId' => $entityID,
				'entityType' => 'COMPANY',
				'entityId' => $entityCompanyID,
				'entityTitle' => isset($entityCompany['TITLE']) ? $entityCompany['TITLE'] : '',
				'entityDescription' => '',
				'communications' => array()
			);

			if($communicationType !== '')
			{
				$entityCompanyComms = CCrmActivity::PrepareCommunications('COMPANY', $entityCompanyID, $communicationType);

				foreach($entityCompanyComms as &$entityCompanyComm)
				{
					$comm = array(
						'type' => $entityCompanyComm['TYPE'],
						'value' => $entityCompanyComm['VALUE']
					);

					$entityCompanyData['communications'][] = $comm;
				}
				unset($entityCompanyComm);
			}
		}

		// Try to get contact of deal
		$entityContactID =  isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		if($entityContactID > 0)
		{
			$entityContact = CCrmContact::GetByID($entityContactID);
			if(is_array($entityContact))
			{
				$item = array(
					'ownerEntityType' => 'DEAL',
					'ownerEntityId' => $entityID,
					'entityType' => 'CONTACT',
					'entityId' => $entityContactID,
					'entityTitle' => CCrmContact::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($entityContact['HONORIFIC']) ? $entityContact['HONORIFIC'] : '',
							'NAME' => isset($entityContact['NAME']) ? $entityContact['NAME'] : '',
							'LAST_NAME' => isset($entityContact['LAST_NAME']) ? $entityContact['LAST_NAME'] : '',
							'SECOND_NAME' => isset($entityContact['SECOND_NAME']) ? $entityContact['SECOND_NAME'] : ''
						)
					),
					'tabId' => 'deal',
					'communications' => array()
				);

				$entityCompany = isset($entityContact['COMPANY_ID']) ? CCrmCompany::GetByID($entityContact['COMPANY_ID']) : null;
				if($entityCompany && isset($entityCompany['TITLE']))
				{
					$item['entityDescription'] = $entityCompany['TITLE'];
				}

				if($communicationType !== '')
				{
					$entityContactComms = CCrmActivity::PrepareCommunications('CONTACT', $entityContactID, $communicationType);
					foreach($entityContactComms as &$contactCommunication)
					{
						$comm = array(
							'type' => $contactCommunication['TYPE'],
							'value' => $contactCommunication['VALUE']
						);

						$item['communications'][] = $comm;
					}
					unset($contactCommunication);
				}

				if($communicationType === '' || !empty($item['communications']))
				{
					$dealData["CONTACT_{$entityContactID}"] = $item;
				}
			}
		}

		if($entityCompanyData && !empty($entityCompanyData['communications']))
		{
			$dealData['COMPANY_'.$entityCompanyID] = $entityCompanyData;
			$dealData['COMPANY_'.$entityCompanyID]['tabId'] = 'deal';
		}

		// Try to get previous communications
		$entityComms = CCrmActivity::GetCommunicationsByOwner('DEAL', $entityID, $communicationType);
		foreach($entityComms as &$entityComm)
		{
			CCrmActivity::PrepareCommunicationInfo($entityComm);
			$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
			if(!isset($dealData[$key]))
			{
				$dealData[$key] = array(
					'ownerEntityType' => 'DEAL',
					'ownerEntityId' => $entityID,
					'entityType' => CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
					'entityId' => $entityComm['ENTITY_ID'],
					'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
					'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
					'tabId' => 'deal',
					'communications' => array()
				);
			}

			if($communicationType !== '')
			{
				$commFound = false;
				foreach($dealData[$key]['communications'] as &$comm)
				{
					if($comm['value'] === $entityComm['VALUE'])
					{
						$commFound = true;
						break;
					}
				}
				unset($comm);

				if($commFound)
				{
					continue;
				}

				$comm = array(
					'type' => $entityComm['TYPE'],
					'value' => $entityComm['VALUE']
				);

				$dealData[$key]['communications'][] = $comm;
			}
		}
		unset($entityComm);

		$companyData = array();
		// Try to get contacts of company
		if($entityCompany > 0)
		{
			$entityComms = CCrmActivity::GetCompanyCommunications($entityCompanyID, $communicationType);
			foreach($entityComms as &$entityComm)
			{
				CCrmActivity::PrepareCommunicationInfo($entityComm);
				$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
				if(!isset($companyData[$key]))
				{
					$companyData[$key] = array(
						'ownerEntityType' => 'DEAL',
						'ownerEntityId' => $entityID,
						'entityType' => CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
						'entityId' => $entityComm['ENTITY_ID'],
						'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
						'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
						'tabId' => 'company',
						'communications' => array()
					);
				}

				if($communicationType !== '')
				{
					$comm = array(
						'type' => $entityComm['TYPE'],
						'value' => $entityComm['VALUE']
					);

					$companyData[$key]['communications'][] = $comm;
				}
			}
			unset($entityComm);
		}

		if($entityCompanyData && !empty($entityCompanyData['communications']))
		{
			$companyData['COMPANY_'.$entityCompanyID] = $entityCompanyData;
			$companyData['COMPANY_'.$entityCompanyID]['tabId'] = 'company';
		}

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'deal',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_DEAL'),
						'active' => true,
						'items' => array_values($dealData)
					),
					array(
						'id' => 'company',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_COMPANY'),
						'items' => array_values($companyData)
					)
				)
			)
		);
	}
	elseif($entityType === 'COMPANY')
	{
		$companyData = array();

		$entity = CCrmCompany::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		$companyItem = array(
			'ownerEntityType' => 'COMPANY',
			'ownerEntityId' => $entityID,
			'entityType' => 'COMPANY',
			'entityId' => $entityID,
			'entityTitle' => isset($entity['TITLE']) ? $entity['TITLE'] : "{$entityType}_{$entityID}",
			'entityDescription' => '',
			'tabId' => 'company',
			'communications' => array()
		);

		// Try to load entity communications
		if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID))
		{
			return array('ERROR' => GetMessage('CRM_PERMISSION_DENIED'));
		}

		if($communicationType !== '')
		{
			$dbResFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
			);

			while($arField = $dbResFields->Fetch())
			{
				if(empty($arField['VALUE']))
				{
					continue;
				}

				$comm = array(
					'type' => $communicationType,
					'value' => $arField['VALUE']
				);

				$companyItem['communications'][] = $comm;
			}
		}

		$companyData["{$entityType}_{$entityID}"] = $companyItem;

		//if($communicationType !== '')
		{
			$entityComms = CCrmActivity::GetCompanyCommunications($entityID, $communicationType);
			foreach($entityComms as &$entityComm)
			{
				CCrmActivity::PrepareCommunicationInfo($entityComm);
				$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
				if(!isset($companyData[$key]))
				{
					$companyData[$key] = array(
						'ownerEntityType' => 'COMPANY',
						'ownerEntityId' => $entityID,
						'entityType' => $entityComm['ENTITY_TYPE'],
						'entityId' => $entityComm['ENTITY_ID'],
						'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
						'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
						'tabId' => 'company',
						'communications' => array()
					);
				}

				$comm = array(
					'type' => $entityComm['TYPE'],
					'value' => $entityComm['VALUE']
				);

				$companyData[$key]['communications'][] = $comm;
			}
			unset($entityComm);
		}

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'company',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_COMPANY'),
						'active' => true,
						'items' => array_values($companyData)
					)
				)
			)
		);
	}
	elseif($entityType === 'CONTACT')
	{
		$contactData = array();

		$entity = CCrmContact::GetByID($entityID);
		if(!$entity)
		{
			return array('ERROR' => 'Invalid data');
		}

		$entityCompany = isset($entity['COMPANY_ID']) ? CCrmCompany::GetByID($entity['COMPANY_ID']) : null;

		$contactItem = array(
			'ownerEntityType' => 'CONTACT',
			'ownerEntityId' => $entityID,
			'entityType' => 'CONTACT',
			'entityId' => $entityID,
			'entityTitle' => CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($entity['HONORIFIC']) ? $entity['HONORIFIC'] : '',
					'NAME' => isset($entity['NAME']) ? $entity['NAME'] : '',
					'LAST_NAME' => isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '',
					'SECOND_NAME' => isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : ''
				)
			),
			'entityDescription' => ($entityCompany && isset($entityCompany['TITLE'])) ? $entityCompany['TITLE'] : '',
			'tabId' => 'contact',
			'communications' => array()
		);

		// Try to load entity communications
		if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID))
		{
			return array('ERROR' => GetMessage('CRM_PERMISSION_DENIED'));
		}

		if($communicationType !== '')
		{
			$dbResFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
			);

			while($arField = $dbResFields->Fetch())
			{
				if(empty($arField['VALUE']))
				{
					continue;
				}

				$comm = array(
					'type' => $communicationType,
					'value' => $arField['VALUE']
				);

				$contactItem['communications'][] = $comm;
			}
		}

		$contactData["{$entityType}_{$entityID}"] = $contactItem;

		return array(
			'DATA' => array(
				'TABS' => array(
					array(
						'id' => 'contact',
						'title' => GetMessage('CRM_COMMUNICATION_TAB_CONTACT'),
						'active' => true,
						'items' => array_values($contactData)
					)
				)
			)
		);
	}
	else
	{
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::ResolveID($entityType));
		if($factory)
		{
			$fieldsCollection = $factory->getFieldsCollection();
			if (
				!$fieldsCollection->hasField(Bitrix\Crm\Item::FIELD_NAME_COMPANY)
				&& !$fieldsCollection->hasField(Bitrix\Crm\Item::FIELD_NAME_CONTACTS)
			)
			{
				return ['ERROR' => 'Invalid data'];
			}
			$item = $factory->getItemsFilteredByPermissions([
				'select' => [Bitrix\Crm\Item::FIELD_NAME_COMPANY, Bitrix\Crm\Item::FIELD_NAME_CONTACTS],
				'filter' => ['='.\Bitrix\Crm\Item::FIELD_NAME_ID => $entityID]
			])[0] ?? null;
			if(!$item)
			{
				return ['ERROR' => 'Invalid data'];
			}

			$tabId = mb_strtolower($entityType);
			$company = null;
			$itemData = [];
			$company = $item->getCompany();
			if($company)
			{
				$itemCompanyData = [
					'ownerEntityType' => $entityType,
					'ownerEntityId' => $entityID,
					'entityType' => 'COMPANY',
					'entityId' => $company->getId(),
					'entityTitle' => $company->getTitle(),
					'entityDescription' => '',
					'communications' => [],
				];

				if($communicationType !== '')
				{
					$companyCommunications = CCrmActivity::PrepareCommunications('COMPANY', $company->getId(), $communicationType);

					foreach($companyCommunications as &$companyCommunication)
					{
						$itemCompanyData['communications'][] = [
							'type' => $companyCommunication['TYPE'],
							'value' => $companyCommunication['VALUE']
						];
					}
					unset($companyCommunication);
				}

				if(!empty($itemCompanyData['communications']))
				{
					$itemData['COMPANY_'.$company->getId()] = $itemCompanyData;
					$itemData['COMPANY_'.$company->getId()]['tabId'] = $tabId;
				}
			}

			$contact = $item->getPrimaryContact();
			if($contact)
			{
				$itemContactData = [
					'ownerEntityType' => $entityType,
					'ownerEntityId' => $entityID,
					'entityType' => 'CONTACT',
					'entityId' => $contact->getId(),
					'entityTitle' => $contact->getFormattedName(),
					'tabId' => $tabId,
					'communications' => [],
				];

				$contactCompany = $contact->getCompany();
				if($contactCompany && !empty($contactCompany->getTitle()))
				{
					$itemContactData['entityDescription'] = $contactCompany->getTitle();
				}

				if($communicationType !== '')
				{
					$contactCommunications = CCrmActivity::PrepareCommunications('CONTACT', $contact->getId(), $communicationType);
					foreach($contactCommunications as &$contactCommunication)
					{
						$itemContactData['communications'][] = [
							'type' => $contactCommunication['TYPE'],
							'value' => $contactCommunication['VALUE'],
						];
					}
					unset($contactCommunication);
				}

				if($communicationType === '' || !empty($itemContactData['communications']))
				{
					$itemData["CONTACT_{$contact->getId()}"] = $itemContactData;
				}
			}

			$entityComms = CCrmActivity::GetCommunicationsByOwner($entityType, $entityID, $communicationType);
			foreach($entityComms as &$entityComm)
			{
				CCrmActivity::PrepareCommunicationInfo($entityComm);
				$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
				if(!isset($itemData[$key]))
				{
					$itemData[$key] = [
						'ownerEntityType' => $entityType,
						'ownerEntityId' => $entityID,
						'entityType' => CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
						'entityId' => $entityComm['ENTITY_ID'],
						'entityTitle' => $entityComm['TITLE'] ?? '',
						'entityDescription' => $entityComm['DESCRIPTION'] ?? '',
						'tabId' => $tabId,
						'communications' => []
					];
				}

				if($communicationType !== '')
				{
					$commFound = false;
					foreach($itemData[$key]['communications'] as &$comm)
					{
						if($comm['value'] === $entityComm['VALUE'])
						{
							$commFound = true;
							break;
						}
					}
					unset($comm);

					if($commFound)
					{
						continue;
					}

					$comm = array(
						'type' => $entityComm['TYPE'],
						'value' => $entityComm['VALUE']
					);

					$itemData[$key]['communications'][] = $comm;
				}
			}
			unset($entityComm);

			$companyData = [];
			// Try to get contacts of company
			if($company)
			{
				$entityComms = CCrmActivity::GetCompanyCommunications($company->getId(), $communicationType);
				foreach($entityComms as &$entityComm)
				{
					CCrmActivity::PrepareCommunicationInfo($entityComm);
					$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
					if(!isset($companyData[$key]))
					{
						$companyData[$key] = [
							'ownerEntityType' => $entityType,
							'ownerEntityId' => $entityID,
							'entityType' => CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
							'entityId' => $entityComm['ENTITY_ID'],
							'entityTitle' => $entityComm['TITLE'] ?? '',
							'entityDescription' => $entityComm['DESCRIPTION'] ?? '',
							'tabId' => 'company',
							'communications' => []
						];
					}

					if($communicationType !== '')
					{
						$companyData[$key]['communications'][] = [
							'type' => $entityComm['TYPE'],
							'value' => $entityComm['VALUE']
						];;
					}
				}
				unset($entityComm);
			}

			if($itemCompanyData && !empty($itemCompanyData['communications']))
			{
				$companyData['COMPANY_'.$company->getId()] = $itemCompanyData;
				$companyData['COMPANY_'.$company->getId()]['tabId'] = 'company';
			}

			return [
				'DATA' => [
					'TABS' => [
						[
							'id' => $tabId,
							'title' => $factory->getEntityDescription(),
							'active' => true,
							'items' => array_values($itemData)
						],
						[
							'id' => 'company',
							'title' => GetMessage('CRM_COMMUNICATION_TAB_COMPANY'),
							'items' => array_values($companyData)
						]
					]
				]
			];
		}
	}

	return array('ERROR' => 'Invalid data');
}

if($action == 'DELETE')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

	if($ID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid parameters!'));
	}

	$arActivity = CCrmActivity::GetByID($ID);
	if(!$arActivity)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Activity not found!'));
	}

	$provider = CCrmActivity::GetActivityProvider($arActivity);
	if(!$provider)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Provider not found!'));
	}

	$ownerTypeName = isset($_POST['OWNER_TYPE'])? mb_strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	if($provider::checkOwner() && !isset($ownerTypeName[0]))
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
	if($provider::checkOwner() && $ownerID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	if($provider::checkOwner() && !CCrmActivity::CheckUpdatePermission(CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$currentUser = \CCrmSecurityHelper::getCurrentUserId();

	$isOutgoing = \CCrmActivityDirection::Outgoing == $arActivity['DIRECTION'];
	$isSkiplist = $isBlacklist = false;

	$isSkip = !empty($_REQUEST['IS_SKIP']) && $_REQUEST['IS_SKIP'] == 'Y';
	if ($isSkip && !$isOutgoing)
	{
		$exclusionAccess = new \Bitrix\Crm\Exclusion\Access($currentUser);

		$isSkiplist = $exclusionAccess->canWrite();
	}

	$isSpam = !empty($_REQUEST['IS_SPAM']) && $_REQUEST['IS_SPAM'] == 'Y';
	if ($isSpam && !$isOutgoing && \CModule::includeModule('mail'))
	{
		$res = \Bitrix\Mail\MailboxTable::getList(array(
			'select' => array('ID', 'OPTIONS'),
			'filter' => array(
				'=LID'     => SITE_ID,
				'=ACTIVE'  => 'Y',
				'=USER_ID' => $currentUser,
			),
			'order' => array('ID' => 'DESC'),
		));

		while ($mailbox = $res->fetch())
		{
			if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', (array) $mailbox['OPTIONS']['flags']))
			{
				$isBlacklist = true;
				break;
			}
		}
	}

	if ($isSkiplist || $isBlacklist)
	{
		$communications = \CCrmActivity::getCommunications($ID);
		if (!empty($communications))
		{
			$blacklist = array();
			foreach ($communications as $item)
			{
				if ($item['TYPE'] == 'EMAIL' && !empty($item['VALUE']) && check_email($item['VALUE']))
				{
					// copied from check_email
					if (preg_match('/.*?[<\[\(](.+?)[>\]\)].*/i', $item['VALUE'], $matches))
						$item['VALUE'] = $matches[1];

					$blacklist[] = trim($item['VALUE']);
				}
			}

			$blacklist = array_unique($blacklist);
		}
	}

	if(CCrmActivity::Delete($ID))
	{
		if (!empty($blacklist))
		{
			if ($isSkiplist)
			{
				foreach ($blacklist as $item)
				{
					\Bitrix\Crm\Exclusion\Store::add(\Bitrix\Crm\Communication\Type::EMAIL, $item);
				}
			}

			if ($isBlacklist)
			{
				$existsEntries = \Bitrix\Mail\BlacklistTable::getList(array(
					'select' => array('ITEM_VALUE'),
					'filter' => array(
						'MAILBOX_ID'  => $mailbox['ID'],
						'ITEM_TYPE'   => \Bitrix\Mail\Blacklist\ItemType::EMAIL,
						'@ITEM_VALUE' => $blacklist,
					),
				));
				foreach ($existsEntries as $item)
				{
					if (($k = array_search($item['ITEM_VALUE'], $blacklist)) !== false)
						unset($blacklist[$k]);
				}

				if (!empty($blacklist))
				{
					foreach ($blacklist as $item)
					{
						\Bitrix\Mail\BlacklistTable::add(array(
							'SITE_ID'    => SITE_ID,
							'MAILBOX_ID' => $mailbox['ID'],
							'ITEM_TYPE'  => \Bitrix\Mail\Blacklist\ItemType::EMAIL,
							'ITEM_VALUE' => $item,
						));
					}
				}
			}
		}

		__CrmActivityEditorEndResponse(array('DELETED_ITEM_ID'=> $ID));
	}
	else
	{
		__CrmActivityEditorEndResponse(array('ERROR'=> "Could not delete activity ('$ID')!"));
	}
}
elseif($action == 'COMPLETE')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

	if($ID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data!'));
	}

	$arActivity = CCrmActivity::GetByID($ID, false);
	if(!$arActivity)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Activity not found!'));
	}

	$provider = CCrmActivity::GetActivityProvider($arActivity);
	if(!$provider)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Provider not found!'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID(isset($_POST['OWNER_TYPE'])? mb_strtoupper(strval($_POST['OWNER_TYPE'])) : '');
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

	if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID > 0)
	{
		$ownerTypeID = isset($arActivity['OWNER_TYPE_ID']) ? intval($arActivity['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
		$ownerID = isset($arActivity['OWNER_ID']) ? intval($arActivity['OWNER_ID']) : 0;
	}

	if($provider::checkOwner() && !CCrmOwnerType::IsDefined($ownerTypeID))
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	if($provider::checkOwner() && $ownerID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if($provider::checkOwner() && !CCrmActivity::CheckCompletePermission($ownerTypeID, $ownerID, $userPermissions, array('FIELDS' => $arActivity)))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$completed = (isset($_POST['COMPLETED']) ? intval($_POST['COMPLETED']) : 0) > 0;

	if(CCrmActivity::Complete($ID, $completed, array('REGISTER_SONET_EVENT' => true)))
	{
		$responseData = array('ITEM_ID'=> $ID, 'COMPLETED'=> $completed);
		__CrmActivityEditorEndResponse($responseData);
	}
	else
	{
		$errorMsg = CCrmActivity::GetLastErrorMessage();
		if(!isset($errorMsg[0]))
		{
			$errorMsg = "Could not complete activity ('$ID')!";
		}

		__CrmActivityEditorEndResponse(array('ERROR' => $errorMsg));
	}
}
elseif($action == 'POSTPONE')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

	if($ID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data!'));
	}

	$arActivity = CCrmActivity::GetByID($ID, false);
	if(!$arActivity)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Activity not found!'));
	}

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if(!CCrmActivity::CheckItemUpdatePermission($arActivity, $userPermissions))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$offset = isset($_POST['OFFSET']) ? (int)$_POST['OFFSET'] : 0;
	if($offset <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid offset'));
	}

	$arUpdatedFields = CCrmActivity::Postpone($ID, $offset, array('FIELDS' => $arActivity));
	if(!empty($arUpdatedFields))
	{
		__CrmActivityEditorEndResponse(array('ITEM_ID' => $ID, 'FIELDS' => $arUpdatedFields));
	}
	else
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Postpone denied.'));
	}
}
elseif($action == 'SET_PRIORITY')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;

	if($ID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data!'));
	}

	$arActivity = CCrmActivity::GetByID($ID);
	if(!$arActivity)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Activity not found!'));
	}

	$ownerTypeName = isset($_POST['OWNER_TYPE'])? mb_strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	if(!isset($ownerTypeName[0]))
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
	if($ownerID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER ID IS NOT DEFINED!'));
	}

	if(!CCrmActivity::CheckUpdatePermission(CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$priority = isset($_POST['PRIORITY']) ? intval($_POST['PRIORITY']) : CCrmActivityPriority::Medium;

	if(CCrmActivity::SetPriority($ID, $priority, array('REGISTER_SONET_EVENT' => true)))
	{
		__CrmActivityEditorEndResponse(array('ITEM_ID'=> $ID, 'PRIORITY'=> $priority));
	}
	else
	{
		$errorMsg = CCrmActivity::GetLastErrorMessage();
		if(!isset($errorMsg[0]))
		{
			$errorMsg = "Could not change priority!";
		}

		__CrmActivityEditorEndResponse(array('ERROR' => $errorMsg));
	}
}
elseif($action == 'SAVE_ACTIVITY')
{
	$siteID = !empty($_REQUEST['siteID']) ? $_REQUEST['siteID'] : SITE_ID;

	if (!CModule::IncludeModule('calendar'))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Could not load module "calendar"!'));
	}

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$typeID = isset($data['type']) ? intval($data['type']) : CCrmActivityType::Activity;

	$arActivity = null;
	if($ID > 0)
	{
		$arActivity = CCrmActivity::GetByID($ID, false);
		if(!$arActivity)
		{
			__CrmActivityEditorEndResponse(array('ERROR'=>'IS NOT EXISTS!'));
		}
	}

	$ownerTypeName = isset($data['ownerType'])? mb_strtoupper(strval($data['ownerType'])) : '';
	if($ownerTypeName === '')
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER TYPE IS NOT DEFINED!'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if(!CCrmOwnerType::IsDefined($ownerTypeID))
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER TYPE IS NOT SUPPORTED!'));
	}

	$ownerID = isset($data['ownerID']) ? intval($data['ownerID']) : 0;
	if($ownerID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'OWNER ID IS NOT DEFINED!'));
	}

	if(!CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
	{
		$entityTitle = CCrmOwnerType::GetCaption($ownerTypeID, $ownerID, false);
		if($ownerTypeID === CCrmOwnerType::Contact)
		{
			$errorMsg = GetMessage('CRM_CONTACT_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$errorMsg = GetMessage('CRM_COMPANY_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Lead)
		{
			$errorMsg = GetMessage('CRM_LEAD_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		elseif($ownerTypeID === CCrmOwnerType::Deal)
		{
			$errorMsg = GetMessage('CRM_DEAL_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
		}
		else
		{
			$errorMsg = GetMessage('CRM_PERMISSION_DENIED');
		}
		__CrmActivityEditorEndResponse(array('ERROR' => $errorMsg));
	}

	$responsibleID = isset($data['responsibleID']) ? intval($data['responsibleID']) : 0;

	$userID = $curUser->GetID();
	if($userID <= 0)
	{
		$userID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID, false);
		if($userID <= 0)
		{
			__CrmActivityEditorEndResponse(array('ERROR'=>GetMessage('CRM_ACTIVITY_RESPONSIBLE_NOT_FOUND')));
		}
	}

	$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
	$start = isset($data['start']) ? strval($data['start']) : '';
	if($start === '')
	{
		$start =  $now;
	}

	$descr = isset($data['description']) ? strval($data['description']) : '';
	$priority = isset($data['priority']) ? intval($data['priority']) : CCrmActivityPriority::Medium;
	$location = isset($data['location']) ? strval($data['location']) : '';

	$direction = $typeID === CCrmActivityType::Call
			? (isset($data['direction']) ? intval($data['direction']) : CCrmActivityDirection::Outgoing)
			: CCrmActivityDirection::Undefined;

	// Communications
	$commData = isset($data['communication']) ? $data['communication'] : array();
	$commID = isset($commData['id']) ? intval($commData['id']) : 0;
	$commEntityType = isset($commData['entityType'])? mb_strtoupper(strval($commData['entityType'])) : '';
	$commEntityID = isset($commData['entityId']) ? intval($commData['entityId']) : 0;
	$commType = isset($commData['type'])? mb_strtoupper(strval($commData['type'])) : '';
	$commValue = isset($commData['value']) ? strval($commData['value']) : '';

	$subject = isset($data['subject']) ? strval($data['subject']) : '';
	if($subject === '')
	{
		$msgID = 'CRM_ACTION_DEFAULT_SUBJECT';
		if($typeID === CCrmActivityType::Call)
		{
			if($direction === CCrmActivityDirection::Incoming)
			{
				$msgID = 'CRM_INCOMING_CALL_ACTION_DEFAULT_SUBJECT_EXT';
			}
			elseif($direction === CCrmActivityDirection::Outgoing)
			{
				$msgID = 'CRM_OUTGOING_CALL_ACTION_DEFAULT_SUBJECT_EXT';
			}
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$msgID = 'CRM_MEETING_ACTION_DEFAULT_SUBJECT_EXT';
		}

		$arCommInfo = array(
			'ENTITY_ID' => $commEntityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
		);
		CCrmActivity::PrepareCommunicationInfo($arCommInfo);

		$subject = GetMessage(
			$msgID,
			array(
				'#DATE#'=> $now,
				'#TITLE#' => isset($arCommInfo['TITLE']) ? $arCommInfo['TITLE'] : $commValue,
				'#COMMUNICATION#' => $commValue
			)
		);
	}

	$arFields = array(
		'TYPE_ID' =>  $typeID,
		'SUBJECT' => $subject,
		'COMPLETED' => isset($data['completed']) ? (intval($data['completed']) > 0 ? 'Y' : 'N') : 'N',
		'PRIORITY' => $priority,
		'DESCRIPTION' => $descr,
		'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
		'LOCATION' => $location,
		'DIRECTION' => $direction,
		'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
		'SETTINGS' => array()
	);

	$arBindings = array(
		"{$ownerTypeName}_{$ownerID}" => array(
			'OWNER_TYPE_ID' => $ownerTypeID,
			'OWNER_ID' => $ownerID
		)
	);

	$notify = isset($data['notify']) ? $data['notify'] : null;
	if(is_array($notify))
	{
		$arFields['NOTIFY_TYPE'] = isset($notify['type']) ? intval($notify['type']) : CCrmActivityNotifyType::Min;
		$arFields['NOTIFY_VALUE'] = isset($notify['value']) ? intval($notify['value']) : 15;
	}

	// Communications
	$arComms = array();
	if($commEntityID <= 0 && $commType === 'PHONE' && $ownerTypeName !== 'DEAL')
	{
		// Communication entity ID is 0 (processing of new communications)
		// Communication type must present it determines TYPE_ID (is only 'PHONE' in current context)
		// Deal does not have multi fields.

		$fieldMulti = new CCrmFieldMulti();
		$arFieldMulti = array(
			'ENTITY_ID' => $ownerTypeName,
			'ELEMENT_ID' => $ownerID,
			'TYPE_ID' => 'PHONE',
			'VALUE_TYPE' => 'WORK',
			'VALUE' => $commValue
		);

		$fieldMultiID = $fieldMulti->Add($arFieldMulti);
		if($fieldMultiID > 0)
		{
			$commEntityType = $ownerTypeName;
			$commEntityID = $ownerID;
		}
	}

	if($commEntityType !== '')
	{
		$arComms[] = array(
			'ID' => $commID,
			'TYPE' => $commType,
			'VALUE' => $commValue,
			'ENTITY_ID' => $commEntityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
		);

		$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
		if(!isset($arBindings[$bindingKey]))
		{
			$arBindings[$bindingKey] = array(
				'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
				'OWNER_ID' => $commEntityID
			);
		}
	}

	$isNew = $ID <= 0;
	$arPreviousFields = $ID > 0 ? CCrmActivity::GetByID($ID) : array();

	$storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : CCrmActivityStorageType::Undefined;
	if($storageTypeID === CCrmActivityStorageType::Undefined
		|| !CCrmActivityStorageType::IsDefined($storageTypeID))
	{
		if($isNew)
		{
			$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
		}
		else
		{
			$storageTypeID = CCrmActivity::GetStorageTypeID($ID);
			if($storageTypeID === CCrmActivityStorageType::Undefined)
			{
				$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
			}
		}
	}

	$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
	$disableStorageEdit = isset($data['disableStorageEdit']) && mb_strtoupper($data['disableStorageEdit']) === 'Y';
	if(!$disableStorageEdit)
	{
		if($storageTypeID === CCrmActivityStorageType::File)
		{
			$arPermittedFiles = array();
			$arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
			if(!empty($arUserFiles) || !$isNew)
			{
				$arPreviousFiles = array();
				if(!$isNew)
				{
					CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
					$arPreviousFiles = $arPreviousFields['STORAGE_ELEMENT_IDS'];
					if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
					{
						$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
					}
				}

				$uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
				if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
				{
					$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
					if(!empty($uploadedFiles))
					{
						$arPermittedFiles = array_merge(
							array_intersect($arUserFiles, $uploadedFiles),
							$arPermittedFiles
						);
					}
				}

				$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
			}
		}
		elseif($storageTypeID === CCrmActivityStorageType::WebDav || $storageTypeID === CCrmActivityStorageType::Disk)
		{
			$fileKey = $storageTypeID === CCrmActivityStorageType::Disk ? 'diskfiles' : 'webdavelements';
			$arFileIDs = isset($data[$fileKey]) && is_array($data[$fileKey]) ? $data[$fileKey] : array();
			if(!empty($arFileIDs) || !$isNew)
			{
				CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
				$arPrevStorageElementIDs = $arPreviousFields['STORAGE_ELEMENT_IDS'];
				$arPersistentStorageElementIDs = array_intersect($arPrevStorageElementIDs, $arFileIDs);
				$arAddedStorageElementIDs = Bitrix\Crm\Integration\StorageManager::filterFiles(
					array_diff($arFileIDs, $arPrevStorageElementIDs),
					$storageTypeID,
					$userID
				);

				$arFields['STORAGE_ELEMENT_IDS'] = array_merge(
					$arPersistentStorageElementIDs,
					$arAddedStorageElementIDs
				);
			}
		}
	}

	//TIME FIELDS
	$arFields['START_TIME'] = $arFields['END_TIME'] = $start;

	if($isNew)
	{
		$arFields['OWNER_ID'] = $ownerID;
		$arFields['OWNER_TYPE_ID'] = $ownerTypeID;
		$arFields['RESPONSIBLE_ID'] = $responsibleID > 0 ? $responsibleID : $userID;

		$arFields['BINDINGS'] = array_values($arBindings);
		$arFields['COMMUNICATIONS'] = $arComms;

		if(!($ID = CCrmActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true))))
		{
			__CrmActivityEditorEndResponse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
	}
	else
	{
		$dbResult = CCrmActivity::GetList(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('OWNER_ID', 'OWNER_TYPE_ID', 'START_TIME', 'END_TIME')
		);
		$presentFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($presentFields))
		{
			__CrmActivityEditorEndResponse(array('ERROR' => 'COULD NOT FIND ACTIVITY'));
		}

		$presentOwnerTypeID = intval($presentFields['OWNER_TYPE_ID']);
		$presentOwnerID = intval($presentFields['OWNER_ID']);
		$ownerChanged =  ($presentOwnerTypeID !== $ownerTypeID || $presentOwnerID !== $ownerID);

		$arFields['OWNER_ID'] = $ownerID;
		$arFields['OWNER_TYPE_ID'] = $ownerTypeID;

		if($responsibleID > 0)
		{
			$arFields['RESPONSIBLE_ID'] = $responsibleID;
		}

		//Merge new bindings with old bindings
		$presetCommunicationKeys = array();
		$presetCommunications = CCrmActivity::GetCommunications($ID);
		foreach($presetCommunications as $arComm)
		{
			$commEntityTypeName = CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']);
			$commEntityID = $arComm['ENTITY_ID'];
			$presetCommunicationKeys["{$commEntityTypeName}_{$commEntityID}"] = true;
		}

		$presentBindings = CCrmActivity::GetBindings($ID);
		foreach($presentBindings as &$binding)
		{
			$bindingOwnerID = (int)$binding['OWNER_ID'];
			$bindingOwnerTypeID = (int)$binding['OWNER_TYPE_ID'];
			$bindingOwnerTypeName = CCrmOwnerType::ResolveName($bindingOwnerTypeID);
			$bindingKey = "{$bindingOwnerTypeName}_{$bindingOwnerID}";

			//Skip present present owner if it is changed
			if($ownerChanged && $presentOwnerTypeID === $bindingOwnerTypeID && $presentOwnerID === $bindingOwnerID)
			{
				continue;
			}

			//Skip present communications - new communications already are in bindings
			if(isset($presetCommunicationKeys[$bindingKey]))
			{
				continue;
			}

			$arBindings[$bindingKey] = array(
				'OWNER_TYPE_ID' => $bindingOwnerTypeID,
				'OWNER_ID' => $bindingOwnerID
			);
		}
		unset($binding);
		$arFields['BINDINGS'] = array_values($arBindings);
		if(!CCrmActivity::Update($ID, $arFields, false, true, array('REGISTER_SONET_EVENT' => true)))
		{
			__CrmActivityEditorEndResponse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
		CCrmActivity::SaveCommunications($ID, $arComms, $arFields, true, false);
	}

	$commData = array();
	$communications = CCrmActivity::GetCommunications($ID);
	foreach($communications as &$arComm)
	{
		CCrmActivity::PrepareCommunicationInfo($arComm);
		$commData[] = array(
			'type' => $arComm['TYPE'],
			'value' => $arComm['VALUE'],
			'entityId' => $arComm['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
			'entityTitle' => $arComm['TITLE'],
			'entityUrl' => CCrmOwnerType::GetEntityShowPath($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
		);
	}
	unset($arComm);

	$arFields = CCrmActivity::GetByID($ID);

	$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;
	$responsibleName = $responsibleID > 0 ? CCrmViewHelper::GetFormattedUserName($responsibleID) : '';

	$descrRaw = isset($arFields['DESCRIPTION']) ? $arFields['DESCRIPTION'] : '';
	$descrHtml = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br/>", htmlspecialcharsbx($descrRaw));

	CCrmActivity::PrepareStorageElementIDs($arFields);
	CCrmActivity::PrepareStorageElementInfo($arFields);

	$jsonFields = array(
		'ID' => $ID,
		'typeID' => $arFields['TYPE_ID'],
		'ownerID' => $arFields['OWNER_ID'],
		'ownerType' => CCrmOwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
		'ownerTitle' => CCrmOwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'ownerUrl' => CCrmOwnerType::GetEntityShowPath($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'subject' => $arFields['SUBJECT'],
		'direction' => isset($arFields['DIRECTION']) ? intval($arFields['DIRECTION']) : CCrmActivityDirection::Undefined,
		'description' => $descrRaw,
		'descriptionHtml' => $descrHtml,
		'location' => isset($arFields['LOCATION']) ? $arFields['LOCATION'] : '',
		'start' => isset($arFields['START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arFields['START_TIME']), 'FULL', $siteID) : '',
		'end' => isset($arFields['END_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arFields['END_TIME']), 'FULL', $siteID) : '',
		'deadline' => isset($arFields['DEADLINE']) ? ConvertTimeStamp(MakeTimeStamp($arFields['DEADLINE']), 'FULL', $siteID) : '',
		'completed' => isset($arFields['COMPLETED']) && $arFields['COMPLETED'] == 'Y',
		'notifyType' => isset($arFields['NOTIFY_TYPE']) ? intval($arFields['NOTIFY_TYPE']) : CCrmActivityNotifyType::None,
		'notifyValue' => isset($arFields['NOTIFY_VALUE']) ? intval($arFields['NOTIFY_VALUE']) : 0,
		'priority' => intval($arFields['PRIORITY']),
		'responsibleID' => $responsibleID,
		'responsibleName' => $responsibleName,
		'responsibleUrl' =>
			CComponentEngine::MakePathFromTemplate(
				'/company/personal/user/#user_id#/',
				array('user_id' => $responsibleID)
			),
		'storageTypeID' => $storageTypeID,
		'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
		'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
		'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
		'communications' => $commData
	);

	__CrmActivityEditorEndResponse(array('ACTIVITY' => $jsonFields));
}
elseif($action == 'SAVE_EMAIL')
{
	if (!CModule::IncludeModule('subscribe'))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Could not load module "subscribe"!'));
	}

	if(!CModule::includeModule('mail'))
	{
		__CrmActivityEditorEndResponse(['ERROR' => getMessage(
			'CRM_ACTIVITY_EDITOR_MAIL_MODULE_NOT_INSTALLED'
		)]);
	}

	$siteID = !empty($_REQUEST['siteID']) ? $_REQUEST['siteID'] : SITE_ID;

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();

	if (empty($data))
	{
		__CrmActivityEditorEndResponse(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
	}

	$rawData = (array) \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPostList()->getRaw('DATA');

	$decodedData = $rawData;
	\CUtil::decodeURIComponent($decodedData);

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;

	$userID = $curUser->GetID();
	if ($userID <= 0)
		__CrmActivityEditorEndResponse(array('ERROR' => getMessage('CRM_ACTIVITY_RESPONSIBLE_NOT_FOUND')));

	$now = convertTimeStamp(time() + \CTimeZone::getOffset(), 'FULL', $siteID);

	$subject = isset($data['subject']) ? strval($data['subject']) : '';
	if ($subject == '')
		$subject = getMessage('CRM_EMAIL_ACTION_DEFAULT_SUBJECT', array('#DATE#'=> $now));

	$arErrors = array();

	$socNetLogDestTypes = array(
		\CCrmOwnerType::LeadName    => 'leads',
		\CCrmOwnerType::DealName    => 'deals',
		\CCrmOwnerType::ContactName => 'contacts',
		\CCrmOwnerType::CompanyName => 'companies',
	);

	$to  = array();
	$cc  = array();
	$bcc = array();

	$countCc = 0;
	$countBcc = 0;
	$countTo = 0;

	// Bindings & Communications -->
	$arBindings = array();
	$arComms = array();
	$commData = isset($data['communications']) ? $data['communications'] : array();
	foreach (array('to', 'cc', 'bcc') as $field)
	{
		if (!empty($rawData[$field]) && is_array($rawData[$field]))
		{
			foreach ($rawData[$field] as $item)
			{
				try
				{
					$item = \Bitrix\Main\Web\Json::decode($item);

					$item['entityType'] = array_search($item['entityType'], $socNetLogDestTypes);
					$item['type'] = 'EMAIL';
					$item['value'] = $item['email'];
					$item['__field'] = $field;

					$commData[] = $item;

					if($field === 'to')
					{
						$countTo++;
					}
					else if($field === 'cc')
					{
						$countCc++;
					}
					else if($field === 'bcc')
					{
						$countBcc++;
					}
				}
				catch (\Exception $e)
				{
				}
			}
		}
	}

	$emailsLimitToSendMessage = Helper\LicenseManager::getEmailsLimitToSendMessage();

	if($emailsLimitToSendMessage !== -1 && ($countTo > $emailsLimitToSendMessage || $countCc > $emailsLimitToSendMessage || $countBcc > $emailsLimitToSendMessage))
	{
		__CrmActivityEditorEndResponse([
			'ERROR_HTML' => \Bitrix\Main\Localization\Loc::getMessage('CRM_MESSAGE_NEW_TARIFF_RESTRICTION',["#COUNT#" => $emailsLimitToSendMessage])
		]);
	}

	if (count($commData) > 10)
	{
		__CrmActivityEditorEndResponse([
			'ERROR' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ACTIVITY_EMAIL_MESSAGE_TO_MANY_RECIPIENTS')
		]);
	}

	$sourceList = \CCrmStatus::getStatusList('SOURCE');
	if (isset($sourceList['EMAIL']))
	{
		$sourceId = 'EMAIL';
	}
	else if (isset($sourceList['OTHER']))
	{
		$sourceId = 'OTHER';
	}

	$contactTypes = \CCrmStatus::getStatusList('CONTACT_TYPE');
	if (isset($contactTypes['CLIENT']))
	{
		$contactType = 'CLIENT';
	}
	else if (isset($contactTypes['OTHER']))
	{
		$contactType = 'OTHER';
	}

	foreach ($commData as &$commDatum)
	{
		$commID = isset($commData['id']) ? intval($commData['id']) : 0;
		$commEntityType = isset($commDatum['entityType'])? mb_strtoupper(strval($commDatum['entityType'])) : '';
		$commEntityID = isset($commDatum['entityId']) ? intval($commDatum['entityId']) : 0;

		$commType = isset($commDatum['type'])? mb_strtoupper(strval($commDatum['type'])) : '';
		if($commType === '')
		{
			$commType = 'EMAIL';
		}
		$commValue = isset($commDatum['value']) ? strval($commDatum['value']) : '';

		if($commType === 'EMAIL' && $commValue !== '')
		{
			if(!check_email($commValue))
			{
				$arErrors[] = GetMessage('CRM_ACTIVITY_INVALID_EMAIL', array('#VALUE#' => $commValue));
				continue;
			}

			$rcptFieldName = 'to';
			if (isset($commDatum['__field']))
			{
				$commDatum['__field'] = mb_strtolower($commDatum['__field']);
				if (in_array($commDatum['__field'], array('to', 'cc', 'bcc')))
					$rcptFieldName = $commDatum['__field'];
			}

			${$rcptFieldName}[] = mb_strtolower(trim($commValue));
		}

		if (isset($commDatum['isEmail']) && $commDatum['isEmail'] == 'Y' && mb_strtolower(trim($commValue)))
		{
			$newEntityTypeId = \Bitrix\Crm\Settings\ActivitySettings::getCurrent()->getOutgoingEmailOwnerTypeId();
			if (\CCrmOwnerType::Contact == $newEntityTypeId && \CCrmContact::checkCreatePermission())
			{
				$contactFields = array(
					'NAME'           => isset($commDatum['params']['name']) ? $commDatum['params']['name'] : '',
					'LAST_NAME'      => isset($commDatum['params']['lastName']) ? $commDatum['params']['lastName'] : '',
					'RESPONSIBLE_ID' => $userID,
					'FM'             => array(
						'EMAIL' => array(
							'n1' => array(
								'VALUE_TYPE' => 'WORK',
								'VALUE'      => mb_strtolower(trim($commValue))
							)
						)
					),
				);

				if ('' != $contactType)
				{
					$contactFields['TYPE_ID'] = $contactType;
				}

				if ('' != $sourceId)
				{
					$contactFields['SOURCE_ID'] = $sourceId;
				}

				if ($contactFields['NAME'] == '' && $contactFields['LAST_NAME'] == '')
					$contactFields['NAME'] = mb_strtolower(trim($commValue));

				$contactEntity = new \CCrmContact();
				$contactId = $contactEntity->add(
					$contactFields, true,
					array(
						'DISABLE_USER_FIELD_CHECK' => true,
						'REGISTER_SONET_EVENT'     => true,
						'CURRENT_USER'             => $userID,
					)
				);

				if ($contactId > 0)
				{
					$commEntityType = \CCrmOwnerType::ContactName;
					$commEntityID   = $contactId;

					$bizprocErrors = array();
					\CCrmBizProcHelper::autostartWorkflows(
						\CCrmOwnerType::Contact, $contactId,
						\CCrmBizProcEventType::Create,
						$bizprocErrors
					);
				}
			}
			else if (\CCrmLead::checkCreatePermission())
			{
				$leadFields = array(
					'TITLE'          => $subject,
					'NAME'           => isset($commDatum['params']['name']) ? $commDatum['params']['name'] : '',
					'LAST_NAME'      => isset($commDatum['params']['lastName']) ? $commDatum['params']['lastName'] : '',
					'STATUS_ID'      => 'NEW',
					'OPENED'         => 'Y',
					'FM'             => array(
						'EMAIL' => array(
							'n1' => array(
								'VALUE_TYPE' => 'WORK',
								'VALUE'      => mb_strtolower(trim($commValue))
							)
						)
					),
				);

				if ('' != $sourceId)
				{
					$leadFields['SOURCE_ID'] = $sourceId;
				}

				$leadEntity = new \CCrmLead(false);
				$leadId = $leadEntity->add(
					$leadFields, true,
					array(
						'DISABLE_USER_FIELD_CHECK' => true,
						'REGISTER_SONET_EVENT'     => true,
						'CURRENT_USER'             => $userID,
					)
				);

				if ($leadId > 0)
				{
					$commEntityType = \CCrmOwnerType::LeadName;
					$commEntityID   = $leadId;

					$bizprocErrors = array();
					\CCrmBizProcHelper::autostartWorkflows(
						\CCrmOwnerType::Lead, $leadId,
						\CCrmBizProcEventType::Create,
						$bizprocErrors
					);

					$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $leadId);
					$starter->setUserIdFromCurrent()->runOnAdd();
				}
			}
		}

		$key = md5(sprintf(
			'%s_%u_%s_%s',
			$commEntityType,
			$commEntityID,
			$commType,
			mb_strtolower(trim($commValue))
		));
		$arComms[$key] = array(
			'ID' => $commID,
			'TYPE' => $commType,
			'VALUE' => $commValue,
			'ENTITY_ID' => $commEntityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
		);

		if($commEntityType !== '')
		{
			$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
			if(!isset($arBindings[$bindingKey]))
			{
				$arBindings[$bindingKey] = array(
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
					'OWNER_ID' => $commEntityID
				);
			}
		}
	}
	unset($commDatum);

	$to  = array_unique($to);
	$cc  = array_unique($cc);
	$bcc = array_unique($bcc);

	$blackListed =
		Mail\Internal\BlacklistTable::query()
		->setSelect(["CODE"])
		->whereIn("CODE",$array = array_merge_recursive($to,$cc,$bcc))
		->exec()
		->fetchAll()
	;

	if (!empty($blackListed = array_column($blackListed,"CODE")))
	{
		__CrmActivityEditorEndResponse(
			array(
				"ERROR_HTML" => \Bitrix\Main\Localization\Loc::getMessage(
					"CRM_ACTIVITY_EMAIL_BLACKLISTED",
					array(
						"%link_start%" => "<a href=\"/settings/configs/mail_blacklist.php\">",
						"%link_end%" => "</a>",
						"%emails%" => implode("; ",$blackListed),
					)
				)
			)
		);
	}
	elseif (empty($to))
	{
		__CrmActivityEditorEndResponse(
			array('ERROR' => getMessage('CRM_ACTIVITY_EMAIL_EMPTY_TO_FIELD'))
		);
	}
	elseif (!empty($arErrors))
	{
		__CrmActivityEditorEndResponse(
			array('ERROR' => $arErrors)
		);
	}

	$ownerTypeName = isset($data['ownerType'])? mb_strtoupper(strval($data['ownerType'])) : '';
	$ownerTypeID = !empty($ownerTypeName) ? \CCrmOwnerType::resolveId($ownerTypeName) : 0;
	$ownerID = isset($data['ownerID']) ? intval($data['ownerID']) : 0;

	$bindData = isset($data['bindings']) ? $data['bindings'] : array();
	if (!empty($rawData['docs']) && is_array($rawData['docs']))
	{
		foreach ($rawData['docs'] as $item)
		{
			try
			{
				$item = \Bitrix\Main\Web\Json::decode($item);
				$item['entityType'] = array_search($item['entityType'], $socNetLogDestTypes);

				$bindData[] = $item;
			}
			catch (\Exception $e)
			{
			}
		}
	}

	foreach ($bindData as $item)
	{
		$item['entityTypeId'] = \CCrmOwnerType::resolveID($item['entityType']);
		if ($item['entityTypeId'] > 0 && $item['entityId'] > 0)
		{
			$key = sprintf('%u_%u', $item['entityType'], $item['entityId']);
			if (\CCrmOwnerType::Deal == $item['entityTypeId'] && !isset($arBindings[$key]))
			{
				$arBindings[sprintf('%u_%u', $ownerTypeID, $ownerID)] = [
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID'      => $ownerID,
				];

				$ownerTypeName = \CCrmOwnerType::resolveName($item['entityTypeId']);
				$ownerTypeID = $item['entityTypeId'];
				$ownerID = $item['entityId'];

				$arBindings[$key] = array(
					'OWNER_TYPE_ID' => $item['entityTypeId'],
					'OWNER_ID'      => $item['entityId']
				);
			}
		}
	}

	$nonRcptOwnerTypes = array(
		\CCrmOwnerType::Lead,
		\CCrmOwnerType::Order,
		\CCrmOwnerType::Deal,
		\CCrmOwnerType::DealRecurring,
		\CCrmOwnerType::Quote,
	);
	if (
		'Y' !== ($data['ownerRcpt'] ?? null)
		&& (in_array($ownerTypeID, $nonRcptOwnerTypes) || CCrmOwnerType::isUseDynamicTypeBasedApproach($ownerTypeID))
		&& $ownerID > 0
	)
	{
		$key = sprintf('%s_%u', $ownerTypeName, $ownerID);
		if (!isset($arBindings[$key]))
		{
			$arBindings[$key] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID'      => $ownerID,
			);
		}
	}

	$ownerBinded = false;
	if ($ownerTypeID > 0 && $ownerID > 0)
	{
		foreach ($arBindings as $item)
		{
			if ($ownerTypeID == $item['OWNER_TYPE_ID'] && $ownerID == $item['OWNER_ID'])
			{
				$ownerBinded = true;
				break;
			}
		}
	}

	if ($ownerBinded)
	{
		$checkedOwnerType = $ownerTypeID;
		if ($ownerTypeID == \CCrmOwnerType::DealRecurring)
		{
			$checkedOwnerType = \CCrmOwnerType::Deal;
		}
		if (!\CCrmActivity::checkUpdatePermission($checkedOwnerType, $ownerID))
		{
			$errorMsg = getMessage('CRM_PERMISSION_DENIED');
			$entityTitle = \CCrmOwnerType::getCaption($ownerTypeID, $ownerID, false);

			if (\CCrmOwnerType::Contact == $ownerTypeID)
				$errorMsg = getMessage('CRM_CONTACT_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
			else if (\CCrmOwnerType::Company == $ownerTypeID)
				$errorMsg = getMessage('CRM_COMPANY_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
			else if (\CCrmOwnerType::Lead == $ownerTypeID)
				$errorMsg = getMessage('CRM_LEAD_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));
			else if (\CCrmOwnerType::Deal == $ownerTypeID || \CCrmOwnerType::DealRecurring == $ownerTypeID)
				$errorMsg = getMessage('CRM_DEAL_UPDATE_PERMISSION_DENIED', array('#TITLE#' => $entityTitle));

			__CrmActivityEditorEndResponse(array('ERROR' => $errorMsg));
		}
	}
	else
	{
		$ownerTypeID = 0;
		$ownerID     = 0;

		$typesPriority = array(
			\CCrmOwnerType::Deal    => 1,
			\CCrmOwnerType::Order   => 2,
			\CCrmOwnerType::Contact => 3,
			\CCrmOwnerType::Company => 4,
			\CCrmOwnerType::Lead    => 5,
		);

		foreach ($arBindings as $item)
		{
			if ($ownerTypeID <= 0 || $typesPriority[$item['OWNER_TYPE_ID']] < $typesPriority[$ownerTypeID])
			{
				if (\CCrmActivity::checkUpdatePermission($item['OWNER_TYPE_ID'], $item['OWNER_ID']))
				{
					$ownerTypeID = $item['OWNER_TYPE_ID'];
					$ownerID     = $item['OWNER_ID'];
					$ownerBinded = true;
				}
			}
		}

		if (!$ownerBinded)
		{
			__CrmActivityEditorEndResponse(array(
				'ERROR' => getMessage(
					empty($arBindings)
						? 'CRM_ACTIVITY_EMAIL_EMPTY_TO_FIELD'
						: 'CRM_PERMISSION_DENIED'
				),
			));
		}
	}

	// single deal binding
	$dealBinded = \CCrmOwnerType::Deal == $ownerTypeID;
	foreach ($arBindings as $key => $item)
	{
		if (\CCrmOwnerType::Deal == $item['OWNER_TYPE_ID'])
		{
			if ($dealBinded)
				unset($arBindings[$key]);

			$dealBinded = true;
		}
	}

	$crmEmail = \CCrmMailHelper::extractEmail(\COption::getOptionString('crm', 'mail', ''));

	$from  = '';
	$reply = '';
	$rawCc = $cc;

	if (isset($decodedData['from']))
		$from = trim(strval($decodedData['from']));

	if ($from == '')
	{
		__CrmActivityEditorEndResponse(array('ERROR' => getMessage('CRM_ACTIVITY_EMAIL_EMPTY_FROM_FIELD')));
	}
	else
	{
		$fromEmail = $from;
		$fromAddress = new \Bitrix\Main\Mail\Address($fromEmail);

		if ($fromAddress->validate())
		{
			$fromEmail = $fromAddress->getEmail();

			\CBitrixComponent::includeComponentClass('bitrix:main.mail.confirm');
			if (!in_array($fromEmail, array_column(\MainMailConfirmComponent::prepareMailboxes(), 'email')))
			{
				__CrmActivityEditorEndResponse(array('ERROR' => getMessage('CRM_ACTIVITY_EMAIL_EMPTY_FROM_FIELD')));
			}

			if ($fromAddress->getName())
			{
				$fromEncoded = sprintf(
					'%s <%s>',
					sprintf('=?%s?B?%s?=', SITE_CHARSET, base64_encode($fromAddress->getName())),
					$fromEmail
				);
			}
		}
		else
		{
			__CrmActivityEditorEndResponse(array('ERROR' => getMessage('CRM_ACTIVITY_INVALID_EMAIL', array('#VALUE#' => $from))));
		}

		if (\CModule::includeModule('mail'))
		{
			foreach (\Bitrix\Mail\MailboxTable::getUserMailboxes() as $mailbox)
			{
				if ($fromEmail == $mailbox['EMAIL'])
				{
					$userImap = $mailbox;
				}
			}
		}

		if (empty($userImap))
		{
			if ($crmEmail != '' && $crmEmail != $fromEmail)
			{
				$reply = $fromEmail . ', ' . $crmEmail;
			}

			$injectUrn = true;
		}

		if ('Y' === ($data['from_copy'] ?? null))
		{
			$cc[] = $fromEmail;
		}
	}

	$messageBody = '';
	$contentType = isset($data['content_type']) && \CCrmContentType::isDefined($data['content_type'])
		? (int) $data['content_type'] : \CCrmContentType::BBCode;

	if (\CCrmContentType::Html == $contentType)
	{
		if (isset($decodedData['message']))
		{
			$messageBody = (string) $decodedData['message'];

			$messageBody = preg_replace('/<!--.*?-->/is', '', $messageBody);
			$messageBody = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $messageBody);
			$messageBody = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $messageBody);

			$sanitizer = new \CBXSanitizer();
			$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
			$sanitizer->applyDoubleEncode(false);
			$sanitizer->addTags(array('style' => array()));

			$messageBody = $sanitizer->sanitizeHtml($messageBody);
			$messageBody = preg_replace('/https?:\/\/bxacid:(n?\d+)/i', 'bxacid:\1', $messageBody);
		}
	}
	else
	{
		if (isset($data['message']))
		{
			$messageBody = (string) $data['message'];

			if (\CCrmContentType::PlainText == $contentType)
			{
				$messageBody = sprintf(
					'<html><body>%s</body></html>',
					preg_replace('/[\r\n]+/'.BX_UTF_PCRE_MODIFIER, '<br>', htmlspecialcharsbx($messageBody))
				);
			}
			else if (\CCrmContentType::BBCode == $contentType)
			{
				//Convert BBCODE to HTML
				$parser = new CTextParser();
				$parser->allow['SMILES'] = 'N';
				$messageBody = '<html><body>'.$parser->convertText($messageBody).'</body></html>';
			}
		}
	}

	if (($messageHtml = $messageBody) != '')
		\CCrmActivity::addEmailSignature($messageHtml, \CCrmContentType::Html);

	$parentId = isset($data['REPLIED_ID']) ? (int) $data['REPLIED_ID'] : 0;
	if ($parentId > 0 && !$dealBinded)
	{
		$parentBindings = \CCrmActivity::getBindings($parentId);
		foreach ($parentBindings as $item)
		{
			$key = sprintf('%u_%u', \CCrmOwnerType::resolveName($item['OWNER_TYPE_ID']), $item['OWNER_ID']);
			if (\CCrmOwnerType::Deal == $item['OWNER_TYPE_ID'] && !isset($arBindings[$key]))
			{
				$arBindings[$key] = array(
					'OWNER_TYPE_ID' => $item['OWNER_TYPE_ID'],
					'OWNER_ID'      => $item['OWNER_ID'],
				);

				break;
			}
		}
	}

	$arBindings = array_merge(
		array(
			sprintf('%u_%u', \CCrmOwnerType::resolveName($ownerTypeID), $ownerID) => array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID,
			),
		),
		$arBindings
	);

	$arFields = array(
		'OWNER_ID' => $ownerID,
		'OWNER_TYPE_ID' => $ownerTypeID,
		'TYPE_ID' =>  CCrmActivityType::Email,
		'SUBJECT' => $subject,
		'START_TIME' => $now,
		'END_TIME' => $now,
		'COMPLETED' => 'Y',
		'RESPONSIBLE_ID' => $userID,
		'PRIORITY' => !empty($data['important']) ? \CCrmActivityPriority::High : \CCrmActivityPriority::Medium,
		'DESCRIPTION' => ($description = $messageHtml),
		'DESCRIPTION_TYPE' => \CCrmContentType::Html,
		'DIRECTION' => CCrmActivityDirection::Outgoing,
		'LOCATION' => '',
		'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
		'BINDINGS' => array_values($arBindings),
		'COMMUNICATIONS' => $arComms,
		'PARENT_ID' => $parentId,
	);

	$storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : CCrmActivityStorageType::Undefined;
	if($storageTypeID === CCrmActivityStorageType::Undefined
		|| !CCrmActivityStorageType::IsDefined($storageTypeID))
	{
		if($isNew)
		{
			$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
		}
		else
		{
			$storageTypeID = CCrmActivity::GetStorageTypeID($ID);
			if($storageTypeID === CCrmActivityStorageType::Undefined)
			{
				$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
			}
		}
	}

	$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
	if($storageTypeID === CCrmActivityStorageType::File)
	{
		$arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
		if(!empty($arUserFiles) || !$isNew)
		{
			$arPermittedFiles = array();
			$arPreviousFiles = array();
			if(!$isNew)
			{
				$arPreviousFields = $ID > 0 ? CCrmActivity::GetByID($ID) : array();
				CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
				$arPreviousFiles = $arPreviousFiles['STORAGE_ELEMENT_IDS'];
				if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
				{
					$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
				}
			}

			$forwardedID = isset($data['FORWARDED_ID']) ? intval($data['FORWARDED_ID']) : 0;
			if($forwardedID > 0)
			{
				$arForwardedFields = CCrmActivity::GetByID($forwardedID);
				if($arForwardedFields)
				{
					CCrmActivity::PrepareStorageElementIDs($arForwardedFields);
					$arForwardedFiles = $arForwardedFields['STORAGE_ELEMENT_IDS'];
					if(!empty($arForwardedFiles))
					{
						$arForwardedFiles = array_intersect($arUserFiles, $arForwardedFiles);
					}


					if(!empty($arForwardedFiles))
					{
						foreach($arForwardedFiles as $fileID)
						$arRawFile = CFile::MakeFileArray($fileID);
						if(is_array($arRawFile))
						{
							$fileID = intval(CFile::SaveFile($arRawFile, 'crm'));
							if($fileID > 0)
							{
								$arPermittedFiles[] = $fileID;
							}
						}
					}
				}
			}

			$uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
			if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
			{
				$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
				if(!empty($uploadedFiles))
				{
					$arPermittedFiles = array_merge(
						array_intersect($arUserFiles, $uploadedFiles),
						$arPermittedFiles
					);
				}
			}

			$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
		}
	}
	elseif($storageTypeID === CCrmActivityStorageType::WebDav || $storageTypeID === CCrmActivityStorageType::Disk)
	{
		if ($storageTypeID === CCrmActivityStorageType::Disk)
		{
			$arFileIDs = array_merge(
				isset($data['diskfiles']) && is_array($data['diskfiles']) ? $data['diskfiles'] : array(),
				isset($data['__diskfiles']) && is_array($data['__diskfiles'])
					? array_map(
						function ($item)
						{
							if (!is_scalar($item))
								return $item;
							return ltrim($item, join(array(
								'n', // \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX
							)));
						},
						$data['__diskfiles']
					) : array()
			);
		}
		else
		{
			$arFileIDs = isset($data['webdavelements']) && is_array($data['webdavelements']) ? $data['webdavelements'] : array();
		}

		$arFileIDs = array_filter($arFileIDs);
		if(!empty($arFileIDs) || !$isNew)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = Bitrix\Crm\Integration\StorageManager::filterFiles($arFileIDs, $storageTypeID, $userID);

			if (!is_array($arFileIDs) || !is_array($arFields['STORAGE_ELEMENT_IDS']))
			{
				addMessage2Log(
					sprintf(
						"crm.activity.editor\ajax.php: Invalid email attachments list\r\n(%s) -> (%s)",
						$arFileIDs,
						$arFields['STORAGE_ELEMENT_IDS']
					),
					'crm',
					0
				);
			}
			else if (count($arFileIDs) > count($arFields['STORAGE_ELEMENT_IDS']))
			{
				addMessage2Log(
					sprintf(
						"crm.activity.editor\ajax.php: Email attachments list had been filtered\r\n(%s) -> (%s)",
						join(',', $arFileIDs),
						join(',', $arFields['STORAGE_ELEMENT_IDS'])
					),
					'crm',
					0
				);
			}
		}
	}

	$totalSize = 0;

	$arRawFiles = array();
	if (isset($arFields['STORAGE_ELEMENT_IDS']) && !empty($arFields['STORAGE_ELEMENT_IDS']))
	{
		foreach ((array) $arFields['STORAGE_ELEMENT_IDS'] as $item)
		{
			$arRawFiles[$item] = \Bitrix\Crm\Integration\StorageManager::makeFileArray($item, $storageTypeID);

			$totalSize += $arRawFiles[$item]['size'];

			if (\CCrmContentType::Html == $contentType)
			{
				$fileInfo = \Bitrix\Crm\Integration\StorageManager::getFileInfo(
					$item, $storageTypeID, false,
					array('OWNER_TYPE_ID' => \CCrmOwnerType::Activity, 'OWNER_ID' => $ID)
				);

				$description = preg_replace(
					sprintf('/(https?:\/\/)?bxacid:n?%u/i', $item),
					htmlspecialcharsbx($fileInfo['VIEW_URL']),
					$description
				);
			}
		}
	}

	$maxSize = Helper\Message::getMaxAttachedFilesSize();
	if ($maxSize > 0 && $maxSize <= ceil($totalSize / 3) * 4) // base64 coef.
	{
		__CrmActivityEditorEndResponse(array('ERROR' => getMessage(
			'CRM_ACTIVITY_EMAIL_MAX_SIZE_EXCEED',
			['#SIZE#' => \CFile::formatSize(Helper\Message::getMaxAttachedFilesSizeAfterEncoding())]
		)));
	}

	if ($isNew)
	{
		if(!($ID = CCrmActivity::Add($arFields, false, false, array('REGISTER_SONET_EVENT' => true))))
		{
			__CrmActivityEditorEndResponse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
	}
	else
	{
		if(!CCrmActivity::Update($ID, $arFields, false, false))
		{
			__CrmActivityEditorEndResponse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
		}
	}

	$hostname = \COption::getOptionString('main', 'server_name', '') ?: 'localhost';
	if (defined('BX24_HOST_NAME') && BX24_HOST_NAME != '')
		$hostname = BX24_HOST_NAME;
	else if (defined('SITE_SERVER_NAME') && SITE_SERVER_NAME != '')
		$hostname = SITE_SERVER_NAME;

	$urn = \CCrmActivity::prepareUrn($arFields);
	$messageId = sprintf('<crm.activity.%s@%s>', $urn, $hostname);

	\CCrmActivity::update($ID, array(
		'DESCRIPTION' => $description,
		'URN'         => $urn,
		'SETTINGS'    => array(
			'IS_BATCH_EMAIL'  => Config\Option::get('main', 'track_outgoing_emails_read', 'Y') == 'Y' ? false : null,
			'MESSAGE_HEADERS' => array(
				'Message-Id' => $messageId,
				'Reply-To'   => $reply ?: $fromEmail,
			),
			'EMAIL_META' => array(
				'__email' => $fromEmail,
				'from'    => $from,
				'replyTo' => $reply,
				'to'      => join(', ', $to),
				'cc'      => join(', ', $rawCc),
				'bcc'     => join(', ', $bcc),
			),
		),
	), false, false, array('REGISTER_SONET_EVENT' => true));

	if (!empty($_REQUEST['save_as_template']))
	{
		$templateFields = array(
			'TITLE'          => $subject,
			'IS_ACTIVE'      => 'Y',
			'OWNER_ID'       => $userID,
			'SCOPE'          => \CCrmMailTemplateScope::Personal,
			'ENTITY_TYPE_ID' => 0,
			'EMAIL_FROM'     => $from,
			'SUBJECT'        => $subject,
			'BODY_TYPE'      => \CCrmContentType::Html,
			'BODY'           => $messageBody,
			'UF_ATTACHMENT' => array_map(
				function ($item)
				{
					return is_scalar($item) ? sprintf('n%u', $item) : $item;
				},
				$arFields['STORAGE_ELEMENT_IDS']
			),
			'SORT'           => 100,
		);
		\CCrmMailTemplate::add($templateFields);
	}

	//Save user email in settings -->
	if($from !== CUserOptions::GetOption('crm', 'activity_email_addresser', ''))
	{
		CUserOptions::SetOption('crm', 'activity_email_addresser', $from);
	}
	//<-- Save user email in settings
	if(!empty($arErrors))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => $arErrors));
	}

	// sending email
	$rcpt    = array();
	$rcptCc  = array();
	$rcptBcc = array();
	foreach ($to as $item)
		$rcpt[] = Mail\Mail::encodeHeaderFrom($item, SITE_CHARSET);
	foreach ($cc as $item)
		$rcptCc[] = Mail\Mail::encodeHeaderFrom($item, SITE_CHARSET);
	foreach ($bcc as $item)
		$rcptBcc[] = Mail\Mail::encodeHeaderFrom($item, SITE_CHARSET);

	$outgoingSubject = $subject;
	$outgoingBody = $messageHtml ?: getMessage('CRM_EMAIL_ACTION_DEFAULT_DESCRIPTION');

	if (!empty($injectUrn)/* && $dealBinded*/)
	{
		switch (\CCrmEMailCodeAllocation::getCurrent())
		{
			case \CCrmEMailCodeAllocation::Subject:
				$outgoingSubject = \CCrmActivity::injectUrnInSubject($urn, $outgoingSubject);
				break;
			case \CCrmEMailCodeAllocation::Body:
				$outgoingBody = \CCrmActivity::injectUrnInBody($urn, $outgoingBody, 'html');
				break;
		}
	}

	$attachments = array();
	foreach ($arRawFiles as $key => $item)
	{
		$contentId = sprintf(
			'bxacid.%s@%s.crm',
			hash('crc32b', $item['external_id'].$item['size'].$item['name']),
			hash('crc32b', $hostname)
		);

		$attachments[] = array(
			'ID'           => $contentId,
			'NAME'         => $item['ORIGINAL_NAME'] ?: $item['name'],
			'PATH'         => $item['tmp_name'],
			'CONTENT_TYPE' => $item['type'],
		);

		$outgoingBody = preg_replace(
			sprintf('/(https?:\/\/)?bxacid:n?%u/i', $key),
			sprintf('cid:%s', $contentId),
			$outgoingBody
		);
	}

	$outgoingParams = array(
		'CHARSET'      => SITE_CHARSET,
		'CONTENT_TYPE' => 'html',
		'ATTACHMENT'   => $attachments,
		'TO'           => join(', ', $rcpt),
		'SUBJECT'      => $outgoingSubject,
		'BODY'         => $outgoingBody,
		'HEADER'       => array(
			'From'       => $fromEncoded ?: $fromEmail,
			'Reply-To'   => $reply ?: $fromEmail,
			//'To'         => join(', ', $rcpt),
			'Cc'         => join(', ', $rcptCc),
			'Bcc'        => join(', ', $rcptBcc),
			//'Subject'    => $outgoingSubject,
			'Message-Id' => $messageId,
		),
	);

	$context = new Mail\Context();
	$context->setCategory(Mail\Context::CAT_EXTERNAL);
	$context->setPriority(count($commData) > 2 ? Mail\Context::PRIORITY_LOW : Mail\Context::PRIORITY_NORMAL);
	$context->setCallback(
		(new Mail\Callback\Config())
			->setModuleId('crm')
			->setEntityType('act')
			->setEntityId($urn)
	);

	$sendResult = Mail\Mail::send(array_merge(
		$outgoingParams,
		array(
			'TRACK_READ' => array(
				'MODULE_ID' => 'crm',
				'FIELDS'    => array('urn' => $urn),
				'URL_PAGE' => '/pub/mail/read.php',
			),
			'TRACK_CLICK' => array(
				'MODULE_ID' => 'crm',
				'FIELDS'    => array('urn' => $urn),
				'URL_PAGE' => '/pub/mail/click.php',
			),
			'CONTEXT' => $context,
		)
	));

	if (!$sendResult)
	{
		if ($isNew)
		{
			if (\CModule::includeModule('bitrix24'))
			{
				if (
					method_exists(\Bitrix\Bitrix24\MailCounter::class, 'isLimited')
					&& \Bitrix\Bitrix24\MailCounter::isLimited()
				)
				{
					$arErrors[] = getMessage('CRM_ACTIVITY_EMAIL_CREATION_LIMITED',
						[
							'%link_start%' => "<a href=\"javascript:top.BX.Helper.show('redirect=detail&code=9252877')\">",
							'%link_end%' => '</a>',
						]);
					\CCrmActivity::delete($ID);
					__CrmActivityEditorEndResponse(array('ERROR_HTML' => $arErrors));
				}
				elseif (
					method_exists(\Bitrix\Bitrix24\MailCounter::class, 'isCustomLimited')
					&& \Bitrix\Bitrix24\MailCounter::isCustomLimited())
				{
					$arErrors[] = getMessage('CRM_ACTIVITY_EMAIL_CUSTOM_LIMITED',
						[
							'%link_start%' => "<a href=\"javascript:top.BX.Helper.show('redirect=detail&code=2099711')\">",
							'%link_end%' => '</a>',
						]);
					\CCrmActivity::delete($ID);
					__CrmActivityEditorEndResponse(array('ERROR_HTML' => $arErrors));
				}
			}
			$arErrors[] = getMessage('CRM_ACTIVITY_EMAIL_CREATION_CANCELED');
		}

		\CCrmActivity::delete($ID);
		__CrmActivityEditorEndResponse(array('ERROR' => $arErrors));
	}

	addEventToStatFile('crm', 'send_email_message', $_REQUEST['context'], trim(trim($messageId), '<>'));

	$needUpload = !empty($userImap);

	if ($context->getSmtp() && in_array(mb_strtolower($context->getSmtp()->getHost()), array('smtp.gmail.com', 'smtp.office365.com')))
	{
		$needUpload = false;
	}

	if ($needUpload)
	{
		class_exists('Bitrix\Mail\Helper');

		$outgoing = new \Bitrix\Mail\DummyMail(array_merge(
			$outgoingParams,
			array(
				'HEADER' => array_merge(
					$outgoingParams['HEADER'],
					array(
						'To'      => $outgoingParams['TO'],
						'Subject' => $outgoingParams['SUBJECT'],
					)
				),
			)
		));

		$mailboxHelper = Bitrix\Mail\Helper\Mailbox::createInstance($userImap['ID']);
		$mailboxHelper->uploadMessage($outgoing);
	}

	// Try add event to entity
	$CCrmEvent = new CCrmEvent();

	$eventText  = '';
	$eventText .= GetMessage('CRM_TITLE_EMAIL_SUBJECT').': '.$subject."\n\r";
	$eventText .= GetMessage('CRM_TITLE_EMAIL_FROM').': '.$from."\n\r";
	if (!empty($to))
		$eventText .= getMessage('CRM_TITLE_EMAIL_TO').': '.implode(',', $to)."\n\r";
	if (!empty($rawCc))
		$eventText .= 'Cc: '.implode(',', $rawCc)."\n\r";
	if (!empty($bcc))
		$eventText .= 'Bcc: '.implode(',', $bcc)."\n\r";
	$eventText .= "\n\r";
	$eventText .= $description;

	$eventBindings = array();
	foreach($arBindings as $item)
	{
		$bindingEntityID = $item['OWNER_ID'];
		$bindingEntityTypeID = $item['OWNER_TYPE_ID'];
		$bindingEntityTypeName = \CCrmOwnerType::resolveName($bindingEntityTypeID);

		$eventBindings["{$bindingEntityTypeName}_{$bindingEntityID}"] = array(
			'ENTITY_TYPE' => $bindingEntityTypeName,
			'ENTITY_ID' => $bindingEntityID
		);
	}
	$CCrmEvent->Add(
		array(
			'ENTITY' => $eventBindings,
			'EVENT_ID' => 'MESSAGE',
			'EVENT_TEXT_1' => $eventText,
			'FILES' => array_values($arRawFiles),
		)
	);
	// <-- Sending Email

	$commData = array();
	$communications = CCrmActivity::GetCommunications($ID);
	foreach($communications as &$arComm)
	{
		CCrmActivity::PrepareCommunicationInfo($arComm);
		$commData[] = array(
			'type' => $arComm['TYPE'],
			'value' => $arComm['VALUE'],
			'entityId' => $arComm['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
			'entityTitle' => $arComm['TITLE'],
			'entityUrl' => CCrmOwnerType::GetEntityShowPath($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
		);
	}
	unset($arComm);

	$userName = '';
	if($userID > 0)
	{
		$dbResUser = CUser::GetByID($userID);
		$userName = is_array(($user = $dbResUser->Fetch()))
			? CUser::FormatName(CSite::GetNameFormat(false), $user, true, false) : '';
	}

	$nowStr = ConvertTimeStamp(MakeTimeStamp($now), 'FULL', $siteID);

	CCrmActivity::PrepareStorageElementIDs($arFields);
	CCrmActivity::PrepareStorageElementInfo($arFields);

	$jsonFields = array(
		'ID' => $ID,
		'typeID' => CCrmActivityType::Email,
		'ownerID' => $arFields['OWNER_ID'],
		'ownerType' => CCrmOwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
		'ownerTitle' => CCrmOwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'ownerUrl' => CCrmOwnerType::GetEntityShowPath($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
		'subject' => $subject,
		'description' => $description,
		'descriptionHtml' => $description,
		'location' => '',
		'start' => $nowStr,
		'end' => $nowStr,
		'deadline' => $nowStr,
		'completed' => true,
		'notifyType' => CCrmActivityNotifyType::None,
		'notifyValue' => 0,
		'priority' => CCrmActivityPriority::Medium,
		'responsibleName' => $userName,
		'responsibleUrl' =>
			CComponentEngine::MakePathFromTemplate(
				'/company/personal/user/#user_id#/',
				array('user_id' => $userID)
			),
		'storageTypeID' => $storageTypeID,
		'files' => isset($arFields['FILES']) ? $arFields['FILES'] : array(),
		'webdavelements' => isset($arFields['WEBDAV_ELEMENTS']) ? $arFields['WEBDAV_ELEMENTS'] : array(),
		'diskfiles' => isset($arFields['DISK_FILES']) ? $arFields['DISK_FILES'] : array(),
		'communications' => $commData
	);

	$responseData = array('ACTIVITY' => $jsonFields);
	__CrmActivityEditorEndResponse($responseData);
}
elseif($action == 'GET_ACTIVITY')
{
	$ID = isset($_POST['ID']) ? (int)$_POST['ID'] : 0;

	$arFields = CCrmActivity::GetByID($ID);
	if(!is_array($arFields))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'NOT FOUND'));
	}
	$provider = \CCrmActivity::getActivityProvider($arFields);

	$commData = array();
	$communications = CCrmActivity::GetCommunications($ID);
	foreach($communications as &$arComm)
	{
		CCrmActivity::PrepareCommunicationInfo($arComm);
		$commData[] = array(
			'type' => $arComm['TYPE'],
			'value' => $arComm['VALUE'],
			'entityId' => $arComm['ENTITY_ID'],
			'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
			'entityTitle' => $arComm['TITLE'],
		);
	}
	unset($arComm);

	$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
		? intval($arFields['STORAGE_TYPE_ID']) : CCrmActivityStorageType::Undefined;

	CCrmActivity::PrepareStorageElementIDs($arFields);
	CCrmActivity::PrepareStorageElementInfo($arFields);

	$associatedEntityId = ($arFields['ASSOCIATED_ENTITY_ID'] ?? '0');
	if (
		$arFields['PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::getId()
		&& ($arFields['PROVIDER_PARAMS']['clientId'] ?? null)
		&& \Bitrix\Main\Loader::includeModule('rest')
	)
	{
		$app = \Bitrix\Rest\AppTable::getByClientId($arFields['PROVIDER_PARAMS']['clientId']);
		$associatedEntityId = (string)($app['ID'] ?? 0);
	}

	__CrmActivityEditorEndResponse(
		array(
			'ACTIVITY' => array(
				'ID' => $ID,
				'typeID' => $arFields['TYPE_ID'],
				'providerID' => $arFields['PROVIDER_ID'],
				'associatedEntityID' => $associatedEntityId,
				'ownerID' => $arFields['OWNER_ID'],
				'ownerType' => CCrmOwnerType::ResolveName($arFields['OWNER_TYPE_ID']),
				'ownerTitle' => CCrmOwnerType::GetCaption($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
				'ownerUrl' => CCrmOwnerType::GetEntityShowPath($arFields['OWNER_TYPE_ID'], $arFields['OWNER_ID']),
				'subject' => $arFields['SUBJECT'],
				'description' => $arFields['DESCRIPTION'],
				'location' => $arFields['LOCATION'],
				'direction' => (int)$arFields['DIRECTION'],
				'start' => $arFields['START_TIME'],
				'end' => $arFields['END_TIME'],
				'completed' => isset($arFields['COMPLETED']) && $arFields['COMPLETED'] === 'Y',
				'notifyType' => (int)$arFields['NOTIFY_TYPE'],
				'notifyValue' => (int)$arFields['NOTIFY_VALUE'],
				'priority' => (int)$arFields['PRIORITY'],
				'responsibleName' => CCrmViewHelper::GetFormattedUserName(
					isset($arFields['RESPONSIBLE_ID']) ? (int)$arFields['RESPONSIBLE_ID'] : 0
				),
				'storageTypeID' => $storageTypeID,
				'files' => $arFields['FILES'] ?? array(),
				'webdavelements' => $arFields['WEBDAV_ELEMENTS'] ?? array(),
				'diskfiles' => $arFields['DISK_FILES'] ?? array(),
				'communications' => $commData,
				'customViewLink' => (($provider && !is_null($provider::getCustomViewLink($arFields))) ? $provider::getCustomViewLink($arFields) : ''),
				'calendarEventId' => $arFields['CALENDAR_EVENT_ID'] ?? null,
			)
		)
	);
}
elseif($action == 'GET_ENTITY_COMMUNICATIONS')
{
	$entityType = isset($_POST['ENTITY_TYPE'])? mb_strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	$communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';

	if($entityType === '' || $entityID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
	}

	__CrmActivityEditorEndResponse(GetCrmEntityCommunications($entityType, $entityID, $communicationType));
}
elseif($action == 'SEARCH_COMMUNICATIONS')
{
	$entityType = isset($_POST['ENTITY_TYPE'])? mb_strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	$communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';
	$needle = isset($_POST['NEEDLE']) ? strval($_POST['NEEDLE']) : '';

	$results = CCrmActivity::FindContactCommunications($needle, $communicationType, 10);

	//if($communicationType !== '')
	{
		//If communication type defined add companies communications
		$results = array_merge(
			$results,
			CCrmActivity::FindCompanyCommunications($needle, $communicationType, 10)
		);
	}

	$results = array_merge(
		$results,
		CCrmActivity::FindLeadCommunications($needle, $communicationType, 10)
	);

	$data = array();
	$images = array();

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	foreach($results as &$result)
	{
		if(!\Bitrix\Crm\Security\EntityAuthorization::checkReadPermission(
			$result['ENTITY_TYPE_ID'],
			$result['ENTITY_ID'],
			$userPermissions)
		)
		{
			continue;
		}

		$key = "{$result['ENTITY_TYPE_ID']}_{$result['ENTITY_ID']}";
		if(!isset($data[$key]))
		{
			$data[$key] = array(
				'ownerEntityType' => $entityType !== '' ? $entityType : CCrmOwnerType::ResolveName($result['ENTITY_TYPE_ID']),
				'ownerEntityId' => $entityID > 0 ? $entityID : intval($result['ENTITY_ID']),
				'entityType' => CCrmOwnerType::ResolveName($result['ENTITY_TYPE_ID']),
				'entityId' => $result['ENTITY_ID'],
				'entityTitle' => $result['TITLE'],
				'entityDescription' => $result['DESCRIPTION'],
				'tabId' => 'search',
				'communications' => array()
			);
			$images[$key] = $result['ENTITY_SETTINGS']['IMAGE_FILE_ID'];
		}

		if($result['TYPE'] !== '' && $result['VALUE'] !== '')
		{
			$comm = array(
				'type' => $result['TYPE'],
				'value' => $result['VALUE']
			);

			$data[$key]['communications'][] = $comm;
		}
	}
	unset($result);

	if ($_REQUEST['soc_net_log_dest'] == 'search_email_comms')
	{
		$socNetLogDestTypes = array(
			\CCrmOwnerType::LeadName    => 'leads',
			\CCrmOwnerType::DealName    => 'deals',
			\CCrmOwnerType::ContactName => 'contacts',
			\CCrmOwnerType::CompanyName => 'companies',
		);
		$response = array(
			'CONTACTS'  => array(),
			'COMPANIES' => array(),
			'LEADS'     => array(),
			'DEALS'     => array(),
			'USERS'     => array(),
		);
		foreach ($data as $key => $item)
		{
			$itemType = $socNetLogDestTypes[$item['entityType']];
			$itemImage = $images[$key] > 0 ? \CFile::resizeImageGet(
				$images[$key],
				array('width' => 38, 'height' => 38),
				BX_RESIZE_IMAGE_EXACT, false
			) : null;

			foreach ($item['communications'] as $subItem)
			{
				$itemId = 'CRM'.$item['entityType'].$item['entityId'].':'.hash('crc32b', $subItem['type'].':'.$subItem['value']);
				$response[mb_strtoupper($itemType)][$itemId] = array(
					'id'         => $itemId,
					'entityId'   => $item['entityId'],
					'entityType' => $itemType,
					'name'       => htmlspecialcharsbx(
						\CCrmOwnerType::LeadName == $item['entityType'] && $item['entityDescription']
							? $item['entityDescription']
							: $item['entityTitle']
					),
					'desc'       => htmlspecialcharsbx($subItem['value']),
					'email'      => htmlspecialcharsbx($subItem['value']),
					'avatar'     => !empty($itemImage['src']) ? $itemImage['src'] : '',
				);
			}
		}

		$words = preg_split('/\s+/', trim($needle));
		if (count($words) > 0)
		{
			$words = array_map(function ($word)
				{
					return str_replace('%', '', $word) . '%';
				},
				$words
			);

			if (count($words) == 2)
			{
				$filter = array(
					'LOGIC' => 'OR',
					array('NAME' => $words[0], 'LAST_NAME' => $words[1]),
					array('NAME' => $words[1], 'LAST_NAME' => $words[0]),
				);
			}
			else
			{
				$filter = array(
					'LOGIC'       => 'OR',
					'NAME'      => $words,
					'LAST_NAME' => $words,
				);

				if (count($words) == 1 && mb_strlen($words[0]) > 2)
					$filter['LOGIN'] = $words[0];
			}

			if (\Bitrix\Main\Config\Option::get('socialnetwork', 'email_users_all', 'N') != 'Y')
			{
				$res = \Bitrix\Main\FinderDestTable::getList(array(
					'order'  => array(),
					'filter' => array(
						'=USER_ID'                    => $curUser->getId(),
						'=CODE_TYPE'                  => 'U',
						'=CODE_USER.EXTERNAL_AUTH_ID' => 'email',
					),
					'select' => array('CODE_USER_ID'),
					'group'  => array('CODE_USER_ID'),
				));

				$emailUsersIds = array();
				while ($item = $res->fetch())
					$emailUsersIds[] = $item['CODE_USER_ID'];

				$filter = array(
					'@ID' => $emailUsersIds,
					$filter,
				);
				if (empty($emailUsersIds))
					$filter = null;
			}

			if (check_email($needle))
			{
				$email = $needle;
				if (preg_match('/(.*?)[<\[\(](.+?)[>\]\)].*/i', $email, $matches))
					$email = $matches[2];

				$filter = array(
					'LOGIC' => 'OR',
					'EMAIL' => trim($email),
					$filter,
				);
			}

			if (!empty($filter))
			{
				$res = \Bitrix\Main\UserTable::getList(array(
					'runtime' => array(
						new \Bitrix\Main\Entity\ReferenceField(
							'FINDER_DEST', 'Bitrix\Main\FinderDest',
							array(
								'=this.ID'     => 'ref.CODE_USER_ID',
								'=ref.USER_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $curUser->getId()),
							)
						),
						new \Bitrix\Main\Entity\ExpressionField(
							'MAX_LAST_USE_DATE',
							'MAX(%s)', 'FINDER_DEST.LAST_USE_DATE'
						),
					),
					'select' => array(
						'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'LOGIN', 'PERSONAL_PHOTO',
					),
					'filter' => array(
						'=ACTIVE'             => 'Y',
						'=EXTERNAL_AUTH_ID'   => 'email',
						'=UF_USER_CRM_ENTITY' => false,
						$filter,
					),
					'order'  => array(
						'MAX_LAST_USE_DATE' => 'DESC',
						'LAST_NAME'         => 'ASC',
					),
					'limit'  => 10,
				));

				while ($item = $res->fetch())
				{
					$itemImage = $item['PERSONAL_PHOTO'] > 0 ? \CFile::resizeImageGet(
						$item['PERSONAL_PHOTO'],
						array('width' => 38, 'height' => 38),
						BX_RESIZE_IMAGE_EXACT, false
					) : null;
					$response['USERS']['U'.$item['ID']] = array(
						'id'         => 'U'.$item['ID'],
						'entityId'   => $item['ID'],
						'name'       => \CUser::formatName(\CSite::getNameFormat(), $item, true, true),
						'login'      => $item['LOGIN'],
						'email'      => $item['EMAIL'],
						'desc'       => $item['EMAIL'],
						'avatar'     => !empty($itemImage['src']) ? $itemImage['src'] : '',
						'active'     => 'Y',
						'isEmail'    => 'Y',
						'isExtranet' => 'N',
					);
				}
			}
		}

		__CrmActivityEditorEndResponse($response);
	}
	else
	{
		__CrmActivityEditorEndResponse(array('DATA' => array('ITEMS' => array_values($data))));
	}
}
elseif($action == 'GET_TASK')
{
	$ID = isset($_POST['ITEM_ID']) ? intval($_POST['ITEM_ID']) : 0;
	$ownerTypeName = isset($_POST['OWNER_TYPE'])? mb_strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;
	$taskID = isset($_POST['TASK_ID']) ? intval($_POST['TASK_ID']) : 0;

	$arFilter = array();

	if($ID > 0)
	{
		$arFilter['=ID'] = $ID;
	}
	else
	{
		if($taskID <= 0)
		{
			__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
		}

		$arFilter['=TYPE_ID'] = CCrmActivityType::Task;
		$arFilter['=ASSOCIATED_ENTITY_ID'] = $taskID;

		if($ownerTypeName !== '')
		{
			$arFilter['=OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($ownerTypeName);
		}

		if($ownerID > 0)
		{
			$arFilter['=OWNER_ID'] = $ownerID;
		}
	}

	$dbActivities = CCrmActivity::GetList(array(), $arFilter);
	$arActivity = $dbActivities->Fetch();
	if(!$arActivity)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Not found'));
	}

	$userName = '';
	if($arActivity['RESPONSIBLE_ID'] > 0)
	{
		$dbResUser = CUser::GetByID($arActivity['RESPONSIBLE_ID']);
		$userName = is_array(($user = $dbResUser->Fetch()))
			? CUser::FormatName(CSite::GetNameFormat(false), $user, true, false) : '';
	}

	__CrmActivityEditorEndResponse(
		array(
			'ACTIVITY' => array(
				'ID' => $arActivity['ID'],
				'typeID' => CCrmActivityType::Task,
				'associatedEntityID' => $taskID,
				'subject' => $arActivity['SUBJECT'],
				'description' => $arActivity['DESCRIPTION'],
				'start' => !empty($arActivity['START_TIME']) ? $arActivity['START_TIME'] : '',
				'end' => !empty($arActivity['END_TIME']) ? $arActivity['END_TIME'] : '',
				'completed' => $arActivity['COMPLETED'] === 'Y',
				'notifyType' => CCrmActivityNotifyType::None,
				'notifyValue' => 0,
				'priority' => $arActivity['PRIORITY'],
				'responsibleName' => $userName
			)
		)
	);
}
elseif($action == 'GET_ACTIVITIES')
{
	$ownerTypeName = isset($_POST['OWNER_TYPE'])? mb_strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

	if($ownerTypeName === '' || $ownerID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
	}

	$completed = isset($_POST['COMPLETED']) ? intval($_POST['COMPLETED']) : 0;

	if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($ownerTypeName), $ownerID))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}

	$dbRes = CCrmActivity::GetList(
		array('deadline' => 'asc'),
		array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($ownerTypeName),
			'COMPLETED' => $completed > 0 ? 'Y' : 'N'
		)
	);

	$arItems = array();
	while($arRes = $dbRes->GetNext())
	{
		$responsibleID = isset($arRes['~RESPONSIBLE_ID'])
			? intval($arRes['~RESPONSIBLE_ID']) : 0;
		if($responsibleID > 0)
		{
			$dbResUser = CUser::GetByID($responsibleID);
			$arRes['RESPONSIBLE'] = $dbResUser->Fetch();
			$arRes['RESPONSIBLE_FULL_NAME'] = is_array($arRes['RESPONSIBLE'])
				? CUser::FormatName(CSite::GetNameFormat(false), $arRes['RESPONSIBLE'], true, false) : '';
		}
		else
		{
			$arRes['RESPONSIBLE'] = false;
			$arRes['RESPONSIBLE_FULL_NAME'] = '';
			$arRes['PATH_TO_RESPONSIBLE'] = '';
		}

		$arRes['FILES'] = array();
		CCrmActivity::PrepareStorageElementIDs($arRes);
		$arFileID = $arRes['STORAGE_ELEMENT_IDS'];
		if(is_array($arFileID))
		{
			$fileCount = count($arFileID);
			for($i = 0; $i < $fileCount; $i++)
			{
				if(is_array($arData = CFile::GetFileArray($arFileID[$i])))
				{
					$arRes['FILES'][] = array(
						'fileID' => $arData['ID'],
						'fileName' => $arData['FILE_NAME'],
						'fileURL' =>  CCrmUrlUtil::UrnEncode($arData['SRC']),
						'fileSize' => $arData['FILE_SIZE']
					);
				}
			}
		}

		$arRes['SETTINGS'] = isset($arRes['~SETTINGS']) ? unserialize($arRes['~SETTINGS'], ['allowed_classes' => false]) : array();
		$arRes['COMMUNICATIONS'] = CCrmActivity::GetCommunications($arRes['~ID']);

		$commData = array();
		if(is_array($arRes['COMMUNICATIONS']))
		{
			foreach($arRes['COMMUNICATIONS'] as &$arComm)
			{
				CCrmActivity::PrepareCommunicationInfo($arComm);
				$commData[] = array(
					'id' => $arComm['ID'],
					'type' => $arComm['TYPE'],
					'value' => $arComm['VALUE'],
					'entityId' => $arComm['ENTITY_ID'],
					'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
					'entityTitle' => $arComm['TITLE'],
				);
			}
			unset($arComm);
		}

		$item = array(
			'ID' => $arRes['~ID'],
			'typeID' => $arRes['~TYPE_ID'],
			'subject' => strval($arRes['~SUBJECT']),
			'description' => strval($arRes['~DESCRIPTION']),
			'direction' => intval($arRes['~DIRECTION']),
			'location' => strval($arRes['~LOCATION']),
			'start' => isset($arRes['~START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arRes['~START_TIME']), 'FULL', SITE_ID) : '',
			'end' => isset($arRes['~START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($arRes['~END_TIME']), 'FULL', SITE_ID) : '',
			'deadline' => isset($arRes['~DEADLINE']) ? ConvertTimeStamp(MakeTimeStamp($arRes['~DEADLINE']), 'FULL', SITE_ID) : '',
			'completed' => strval($arRes['~COMPLETED']) == 'Y',
			'notifyType' => intval($arRes['~NOTIFY_TYPE']),
			'notifyValue' => intval($arRes['~NOTIFY_VALUE']),
			'priority' => intval($arRes['~PRIORITY']),
			'responsibleName' => isset($arRes['RESPONSIBLE_FULL_NAME'][0]) ? $arRes['RESPONSIBLE_FULL_NAME'] : GetMessage('CRM_UNDEFINED_VALUE'),
			'files' => $arRes['FILES'],
			'associatedEntityID' => isset($arRes['~ASSOCIATED_ENTITY_ID']) ? intval($arRes['~ASSOCIATED_ENTITY_ID']) : 0,
			'communications' => $commData
		);

		$arItems[] = $item;
	}

	__CrmActivityEditorEndResponse(array('DATA' => array('ITEMS' => $arItems)));
}
elseif($action == 'GET_ENTITIES_DEFAULT_COMMUNICATIONS')
{
	$communicationType = isset($_POST['COMMUNICATION_TYPE']) ? strval($_POST['COMMUNICATION_TYPE']) : '';
	$entityType = isset($_POST['ENTITY_TYPE'])? mb_strtoupper(strval($_POST['ENTITY_TYPE'])) : '';
	$arEntityID = isset($_POST['ENTITY_IDS']) ? $_POST['ENTITY_IDS'] : array();
	$gridID = isset($_POST['GRID_ID']) ? $_POST['GRID_ID'] : array();

	if($entityType === '' || $communicationType === '')
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
	}

	// PERMISSIONS CHECK -->
	$isPermitted = true;
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if(empty($arEntityID))
	{
		$isPermitted = CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), 0, $userPermissions);
	}
	else
	{
		foreach($arEntityID as $entityID)
		{
			$isPermitted = CCrmActivity::CheckReadPermission(CCrmOwnerType::ResolveID($entityType), $entityID, $userPermissions);
			if(!$isPermitted)
			{
				break;
			}
		}
	}

	if(!$isPermitted)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => GetMessage('CRM_PERMISSION_DENIED')));
	}
	// <--PERMISSIONS CHECK

	if(empty($arEntityID) && $gridID !== '')
	{
		//Apply grid filter if ids is not defined
		$gridOptions = new CCrmGridOptions($gridID);
		$gridFilter = $gridOptions->GetFilter(array());

		//Clear service fields
		if(isset($gridFilter['GRID_FILTER_APPLIED']))
		{
			unset($gridFilter['GRID_FILTER_APPLIED']);
		}

		if(isset($gridFilter['GRID_FILTER_ID']))
		{
			unset($gridFilter['GRID_FILTER_ID']);
		}

		if(is_array($gridFilter) && !empty($gridFilter))
		{
			$dbEntities = null;
			if($entityType === 'LEAD')
			{
				CCrmLead::PrepareFilter($gridFilter);
				$dbEntities = CCrmLead::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}
			elseif($entityType === 'DEAL')
			{
				CCrmDeal::PrepareFilter($gridFilter);
				$dbEntities = CCrmDeal::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}
			elseif($entityType === 'COMPANY')
			{
				CCrmCompany::PrepareFilter($gridFilter);
				$dbEntities = CCrmCompany::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}
			elseif($entityType === 'CONTACT')
			{
				CCrmContact::PrepareFilter($gridFilter);
				$dbEntities = CCrmContact::GetListEx(array(), $gridFilter, false, false, array('ID'));
			}

			if($dbEntities)
			{
				while($arEntity = $dbEntities->Fetch())
				{
					$arEntityID[] = $arEntity['ID'];
				}
			}
		}
	}

	$arFilter = array(
		'ENTITY_ID' => $entityType,
		'TYPE_ID' =>  $communicationType,
		'@VALUE_TYPE' => array('WORK', 'HOME', 'OTHER')
	);

	if(!empty($arEntityID))
	{
		$arFilter['@ELEMENT_ID'] = $arEntityID;
	}

	$dbResFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		$arFilter
	);

	$data = array();
	while($arField = $dbResFields->Fetch())
	{
		$value = isset($arField['VALUE']) ? $arField['VALUE'] : '';
		if($value === '')
		{
			continue;
		}

		$entityID = isset($arField['ELEMENT_ID']) ? intval($arField['ELEMENT_ID']) : 0;
		$valueType = isset($arField['VALUE_TYPE']) ? $arField['VALUE_TYPE'] : '';
		if($entityID <= 0
			|| $valueType === ''
			|| (isset($data[$entityID]) && isset($data[$entityID][$valueType])))
		{
			continue;
		}

		$data[$entityID][$valueType] = $value;
	}

	$result = array();
	foreach($data as $entityID => &$values)
	{
		if(isset($values['WORK']))
		{
			$result[] = array(
				'entityId' => $entityID,
				'value' => $values['WORK']
			);
		}
		elseif(isset($values['HOME']))
		{
			$result[] = array(
				'entityId' => $entityID,
				'value' => $values['HOME']
			);
		}
		elseif(isset($values['OTHER']))
		{
			$result[] = array(
				'entityId' => $entityID,
				'value' => $values['OTHER']
			);
		}
	}
	unset($values);

	__CrmActivityEditorEndResponse(array('DATA' => array('ENTITY_TYPE' => $entityType, 'ITEMS' => $result)));
}
elseif($action == 'GET_WEBDAV_ELEMENT_INFO')
{
	$elementID = isset($_POST['ELEMENT_ID']) ? intval($_POST['ELEMENT_ID']) : 0;

	if($elementID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
	}

	__CrmActivityEditorEndResponse(
		array(
			'DATA' => array(
				'ELEMENT_ID' => $elementID,
				'INFO' => \Bitrix\Crm\Integration\StorageManager::getFileInfo(
					$elementID,
					\Bitrix\Crm\Integration\StorageType::WebDav
				)
			)
		)
	);

}
elseif($action == 'GET_COMMUNICATION_HTML')
{
	$typeName = isset($_POST['TYPE_NAME']) ? strval($_POST['TYPE_NAME']) : '';
	$value = isset($_POST['VALUE']) ? strval($_POST['VALUE']) : '';

	__CrmActivityEditorEndResponse(
		array(
			'DATA' => array(
				'HTML' => CCrmViewHelper::PrepareMultiFieldHtml(
					$typeName,
					array(
						'VALUE_TYPE_ID' => 'WORK',
						'VALUE' => $value
					)
				)
			)
		)
	);
}
elseif($action == 'PREPARE_MAIL_TEMPLATE')
{
	$templateID = isset($_POST['TEMPLATE_ID']) ? intval($_POST['TEMPLATE_ID']) : 0;
	$ownerTypeName = isset($_POST['OWNER_TYPE'])? mb_strtoupper(strval($_POST['OWNER_TYPE'])) : '';
	$ownerTypeId = \CCrmOwnerType::resolveId($ownerTypeName);
	$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

	if($templateID <= 0)
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
	}

	$contentTypeID = isset($_POST['CONTENT_TYPE']) ? \CCrmContentType::resolveTypeId($_POST['CONTENT_TYPE']) : \CCrmContentType::Undefined;
	if (!\CCrmContentType::isDefined($contentTypeID))
		$contentTypeID = \CCrmContentType::PlainText;

	$filter = array(
		'=ID' => $templateID,
	);
	if (\CCrmContentType::Html != $contentTypeID)
		$filter['!BODY_TYPE'] = \CCrmContentType::Html;

	$dbResult = CCrmMailTemplate::GetList(
		array(),
		$filter,
		false,
		false,
		array('OWNER_ID', 'ENTITY_TYPE_ID', 'SCOPE', 'EMAIL_FROM', 'SUBJECT', 'BODY', 'BODY_TYPE')
	);
	$fields = $dbResult->Fetch();
	if(!is_array($fields))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
	}

	$templateOwnerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
	$templateScope = isset($fields['SCOPE']) ? intval($fields['SCOPE']) : CCrmMailTemplateScope::Undefined;

	if($templateScope !== CCrmMailTemplateScope::Common
		&& $templateOwnerID !== intval($curUser->GetID()))
	{
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid data'));
	}

	$body = isset($fields['BODY']) ? $fields['BODY'] : '';
	if ($body != '')
	{
		if (\CCrmContentType::Html == $contentTypeID && \CCrmContentType::Html != $fields['BODY_TYPE'])
		{
			$bbCodeParser = new \CTextParser();
			$body = $bbCodeParser->convertText($body);
		}

		$body = \CCrmTemplateManager::prepareTemplate(
			$body,
			$ownerTypeId, $ownerID,
			$contentTypeID,
			(int) $curUser->getId()
		);
	}

	$subject = isset($fields['SUBJECT']) ? $fields['SUBJECT'] : '';
	if ($subject != '')
	{
		$subject = \CCrmTemplateManager::prepareTemplate(
			$subject,
			$ownerTypeId, $ownerID,
			\CCrmContentType::PlainText,
			(int) $curUser->getId()
		);
	}

	$files = $USER_FIELD_MANAGER->getUserFieldValue('CRM_MAIL_TEMPLATE', 'UF_ATTACHMENT', $templateID);
	$files = !empty($files) && is_array($files) && \CModule::includeModule('disk')
		? \Bitrix\Disk\Uf\FileUserType::getItemsInfo($files) : array();

	foreach ($files as $k => $item)
	{
		\Bitrix\Crm\Integration\StorageManager::registerInterRequestFile($item['fileId'], \Bitrix\Crm\Integration\StorageType::Disk);

		if (mb_strpos($item['id'], 'n') !== 0)
		{
			$body = str_replace(sprintf('bxacid:%u', $item['id']), sprintf('bxacid:n%u', $item['fileId']), $body);
			$files[$k]['id'] = $files[$k]['attachId'] = sprintf('n%u', $item['fileId']);
		}
	}

	__CrmActivityEditorEndResponse(
		array(
			'DATA' => array(
				'ID'         => $templateID,
				'OWNER_TYPE' => $ownerTypeName,
				'OWNER_ID'   => $ownerID,
				'FROM'       => isset($fields['EMAIL_FROM']) ? $fields['EMAIL_FROM'] : '',
				'SUBJECT'    => $subject,
				'BODY'       => $body,
				'FILES'      => $files,
			)
		)
	);
}
elseif($action == 'GET_ACTIVITY_COMMUNICATIONS')
{
	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	__CrmActivityEditorEndResponse(array('ACTIVITY_COMMUNICATIONS' => GetCrmActivityCommunications($ID)));
}
elseif($action == 'GET_ACTIVITY_COMMUNICATIONS_PAGE')
{
	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	$pageSize = isset($_POST['PAGE_SIZE']) ? intval($_POST['PAGE_SIZE']) : 20;
	$pageNumber = isset($_POST['PAGE_NUMBER']) ? intval($_POST['PAGE_NUMBER']) : 1;

	__CrmActivityEditorEndResponse(array('ACTIVITY_COMMUNICATIONS_PAGE' => GetCrmActivityCommunicationsPage($ID, $pageSize, $pageNumber)));
}
elseif($action == 'GET_ACTIVITY_VIEW_DATA')
{
	$result = array();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$comm = isset($params['ACTIVITY_COMMUNICATIONS']) ? $params['ACTIVITY_COMMUNICATIONS'] : null;
	if(is_array($comm))
	{
		$ID = isset($comm['ID']) ? (int)$comm['ID'] : 0;
		$result['ACTIVITY_COMMUNICATIONS'] = GetCrmActivityCommunications($ID);
	}

	$commPage = isset($params['ACTIVITY_COMMUNICATIONS_PAGE']) ? $params['ACTIVITY_COMMUNICATIONS_PAGE'] : null;
	if(is_array($commPage))
	{
		$ID = isset($commPage['ID']) ? (int)$commPage['ID'] : 0;
		$pageSize = isset($commPage['PAGE_SIZE']) ? (int)$commPage['PAGE_SIZE'] : 20;
		$pageNumber = isset($commPage['PAGE_NUMBER']) ? (int)$commPage['PAGE_NUMBER'] : 1;
		$result['ACTIVITY_COMMUNICATIONS_PAGE'] = GetCrmActivityCommunicationsPage($ID, $pageSize, $pageNumber);
	}

	$entityComm = isset($params['ENTITY_COMMUNICATIONS']) ? $params['ENTITY_COMMUNICATIONS'] : null;
	if(is_array($entityComm))
	{
		$entityType = isset($entityComm['ENTITY_TYPE'])? mb_strtoupper($entityComm['ENTITY_TYPE']) : '';
		$entityID = isset($entityComm['ENTITY_ID']) ? (int)$entityComm['ENTITY_ID'] : 0;
		$communicationType = isset($entityComm['COMMUNICATION_TYPE']) ? $entityComm['COMMUNICATION_TYPE'] : '';

		if($entityType === '' || $entityID <= 0)
		{
			$result['ENTITY_COMMUNICATIONS'] = array('ERROR' => 'Invalid data');
		}
		else
		{
			$result['ENTITY_COMMUNICATIONS'] = GetCrmEntityCommunications($entityType, $entityID, $communicationType);
		}
	}

	__CrmActivityEditorEndResponse($result);
}
elseif($action == 'UPDATE_DOCS')
{
	$result = array();

	$ID = isset($_REQUEST['ITEM_ID']) ? (int) $_REQUEST['ITEM_ID'] : 0;
	$docsItems = isset($_REQUEST['DOCS_ITEMS']) ? (array) $_REQUEST['DOCS_ITEMS'] : 0;

	if ($ID <= 0)
		__CrmActivityEditorEndResponse(array('ERROR' => 'Invalid parameters!'));

	$errors = array();

	$docsTypes = array(
		\CCrmOwnerType::Deal,
		//\CCrmOwnerType::Invoice,
		//\CCrmOwnerType::Quote,
	);

	$docsBindings = array();
	foreach ($docsItems as $item)
	{
		$itemOwnerId = isset($item['entityId']) ? $item['entityId'] : 0;
		$itemOwnerTypeId = isset($item['entityType']) ? \CCrmOwnerType::resolveID($item['entityType']) : 0;

		if (!in_array($itemOwnerTypeId, $docsTypes) || $itemOwnerId <= 0)
		{
			$errors[] = __CrmActivityEditorEndResponse(array('ERROR' => 'Invalid parameters!'));
			continue;
		}

		$docsBindings[sprintf('%u_%u', $itemOwnerTypeId, $itemOwnerId)] = array(
			'OWNER_TYPE_ID' => $itemOwnerTypeId,
			'OWNER_ID'      => $itemOwnerId,
		);
	}

	if (!empty($errors))
		__CrmActivityEditorEndResponse(array('ERROR' => $errors));

	$activity = \CCrmActivity::getById($ID);
	if (empty($activity))
		__CrmActivityEditorEndResponse(array('ERROR' => 'Activity not found!'));

	$provider = \CCrmActivity::getActivityProvider($activity);
	if (!$provider)
		__CrmActivityEditorEndResponse(array('ERROR' => 'Provider not found!'));

	if ($provider::checkOwner() && !\CCrmActivity::checkUpdatePermission($activity['OWNER_TYPE_ID'], $activity['OWNER_ID']))
		__CrmActivityEditorEndResponse(array('ERROR' => getMessage('CRM_PERMISSION_DENIED')));

	$activity['BINDINGS'] = \CCrmActivity::getBindings($ID);
	foreach ($activity['BINDINGS'] as $k => $item)
	{
		if (!in_array($item['OWNER_TYPE_ID'], $docsTypes))
			continue;

		$key = sprintf('%u_%u', $item['OWNER_TYPE_ID'], $item['OWNER_ID']);
		if (array_key_exists($key, $docsBindings))
		{
			unset($docsBindings[$key]);
			continue;
		}

		if ($item['OWNER_TYPE_ID'] == $activity['OWNER_TYPE_ID'] && $item['OWNER_ID'] == $activity['OWNER_ID'])
		{
			$activity['OWNER_TYPE_ID'] = 0;
			$activity['OWNER_ID'] = 0;
		}
		unset($activity['BINDINGS'][$k]);
	}

	$activity['BINDINGS'] = array_values(array_merge($docsBindings, $activity['BINDINGS']));

	if ($activity['OWNER_TYPE_ID'] <= 0 || $activity['OWNER_ID'] <= 0)
	{
		$typesPriority = array(
			\CCrmOwnerType::Deal => 1,
			\CCrmOwnerType::Order => 2,
			\CCrmOwnerType::Contact => 3,
			\CCrmOwnerType::Company => 4,
			\CCrmOwnerType::Lead => 5,
		);

		foreach ($activity['BINDINGS'] as $item)
		{
			if ($activity['OWNER_TYPE_ID'] <= 0 || $typesPriority[$item['OWNER_TYPE_ID']] < $typesPriority[$activity['OWNER_TYPE_ID']])
			{
				if (\CCrmActivity::checkUpdatePermission($item['OWNER_TYPE_ID'], $item['OWNER_ID']))
				{
					$activity['OWNER_TYPE_ID'] = $item['OWNER_TYPE_ID'];
					$activity['OWNER_ID'] = $item['OWNER_ID'];
				}
			}
		}
	}

	if ($activity['OWNER_TYPE_ID'] > 0 && $activity['OWNER_ID'] > 0)
	{
		$result = \CCrmActivity::update(
			$ID,
			array(
				'OWNER_TYPE_ID' => $activity['OWNER_TYPE_ID'],
				'OWNER_ID' => $activity['OWNER_ID'],
				'BINDINGS' => $activity['BINDINGS'],
			),
			false,
			false
		);

		if (!$result)
		{
			__CrmActivityEditorEndResponse(array('ERROR' => \CCrmActivity::getLastErrorMessage()));
		}
	}
	else
	{
		__CrmActivityEditorEndResponse(array('ERROR' => getMessage('CRM_PERMISSION_DENIED')));
	}

	__CrmActivityEditorEndResponse($result);
}
else
{
	__CrmActivityEditorEndResponse(array('ERROR' => 'Unknown action'));
}
?>

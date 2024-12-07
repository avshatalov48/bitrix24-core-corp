<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_LANG_FILES', true);
define('DisableEventsCheck', true);
define('BX_STATISTIC_BUFFER_USED', false);
define('BX_PUBLIC_TOOLS', true);
define('PUBLIC_AJAX_MODE', true);

use Bitrix\Crm\Integration\StorageManager;

if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteID = $_REQUEST['site_id'];
	//Prevent LFI in prolog_before.php
	if($siteID !== '' && preg_match('/^[a-z0-9_]{2}$/i', $siteID) === 1)
	{
		define('SITE_ID', $siteID);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!defined('LANGUAGE_ID') )
{
	$dbSite = CSite::GetByID(SITE_ID);
	$arSite = $dbSite ? $dbSite->Fetch() : null;
	define('LANGUAGE_ID', $arSite ? $arSite['LANGUAGE_ID'] : 'en');
}

//session_write_close();

if (!CModule::IncludeModule('crm'))
{
	die();
}

global $APPLICATION, $DB;
$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

//$langID = isset($_REQUEST['lang_id'])? $_REQUEST['lang_id']: LANGUAGE_ID;
//__IncludeLang(dirname(__FILE__).'/lang/'.$langID.'/'.basename(__FILE__));

if(!function_exists('__CrmMobileActivityEditEndResponse'))
{
	function __CrmMobileActivityEditEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

$curUserPrems = CCrmPerms::GetCurrentUserPermissions();
$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if($action === 'SAVE_ENTITY')
{
	__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'ACTIVITY')
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$commData = (isset($data['COMMUNICATIONS']) && is_array($data['COMMUNICATIONS']))
		? $data['COMMUNICATIONS'] : array();

	if(empty($commData))
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_SELECT_COMMUNICATION')));
	}

	$ownerTypeName = isset($data['OWNER_TYPE'])? mb_strtoupper(strval($data['OWNER_TYPE'])) : '';
	$ownerID = isset($data['OWNER_ID']) ? intval($data['OWNER_ID']) : 0;

	if($ownerTypeName === '' || $ownerID <= 0)
	{
		$comm = $commData[0];
		$ownerTypeName = isset($comm['OWNER_TYPE']) ? $comm['OWNER_TYPE'] : '';
		$ownerID = isset($comm['ENTITY_ID']) ? intval($comm['ENTITY_ID']) : 0;
	}

	if($ownerTypeName === '' || $ownerID <= 0)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_OWNER_NOT_FOUND')));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if(!CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}
	//if($ownerTypeID !== CCrmOwnerType::Invoice)

	$now = time() + CTimeZone::GetOffset();

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	$isNew = $ID <= 0;
	$typeID = isset($data['TYPE_ID']) ? intval($data['TYPE_ID']) : CCrmActivityType::Activity;
	if($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting)
	{
		$commData = (is_array($commData) && count($commData) > 0)
		? $commData[0] : array();

		if(empty($commData))
		{
			__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_SELECT_COMMUNICATION')));
		}

		$responsibleID = isset($data['RESPONSIBLE_ID']) ? intval($data['RESPONSIBLE_ID']) : 0;
		$start = isset($data['START']) ? intval($data['START']) : 0;
		if($start <= 0)
		{
			$start =  $now;
		}
		$end = $start; //by default

		$completed = 'N';
		$descr = isset($data['DESCRIPTION']) ? strval($data['DESCRIPTION']) : '';
		$location = isset($data['LOCATION']) ? strval($data['LOCATION']) : '';
		$priority = CCrmActivityPriority::Medium;

		$direction = $typeID === CCrmActivityType::Call
			? CCrmActivityDirection::Outgoing
			: CCrmActivityDirection::Undefined;

		$commID = isset($commData['ID']) ? intval($commData['ID']) : 0;
		$commEntityType = isset($commData['ENTITY_TYPE'])? mb_strtoupper(strval($commData['ENTITY_TYPE'])) : '';
		$commEntityID = isset($commData['ENTITY_ID']) ? intval($commData['ENTITY_ID']) : 0;
		$commType = isset($commData['TYPE'])? mb_strtoupper(strval($commData['TYPE'])) : '';
		$commValue = isset($commData['VALUE']) ? strval($commData['VALUE']) : '';

		$subject = isset($data['SUBJECT']) ? strval($data['SUBJECT']) : '';
		if($subject === '')
		{
			$msgID = 'CRM_ACTIVITY_EDIT_ACTION_DEFAULT_SUBJECT';
			if($typeID === CCrmActivityType::Call)
			{
				if($direction === CCrmActivityDirection::Incoming)
				{
					$msgID = 'CRM_ACTIVITY_EDIT_INCOMING_CALL_ACTION_DEFAULT_SUBJECT_EXT';
				}
				elseif($direction === CCrmActivityDirection::Outgoing)
				{
					$msgID = 'CRM_ACTIVITY_EDIT_OUTGOING_CALL_ACTION_DEFAULT_SUBJECT_EXT';
				}
			}
			elseif($typeID === CCrmActivityType::Meeting)
			{
				$msgID = 'CRM_ACTIVITY_EDIT_MEETING_ACTION_DEFAULT_SUBJECT_EXT';
			}

			$commInfo = array(
				'ENTITY_ID' => $commEntityID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
			);
			CCrmActivity::PrepareCommunicationInfo($commInfo);

			$subject = GetMessage(
				$msgID,
				array(
					'#DATE#'=> ConvertTimeStamp($now, 'FULL', SITE_ID),
					'#TITLE#' => $commInfo['TITLE'],
					'#COMMUNICATION#' => $commValue
				)
			);
		}

		$fields = array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => $ownerTypeID,
			'TYPE_ID' =>  $typeID,
			'SUBJECT' => $subject,
			'START_TIME' => ConvertTimeStamp($start, 'FULL', SITE_ID),
			'END_TIME' => ConvertTimeStamp($end, 'FULL', SITE_ID),
			'COMPLETED' => $completed,
			'PRIORITY' => $priority,
			'DESCRIPTION' => $descr,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'LOCATION' => $location,
			'DIRECTION' => $direction,
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'SETTINGS' => array()
		);

		$notify = isset($data['NOTIFY']) ? $data['NOTIFY'] : null;
		if(is_array($notify))
		{
			$fields['NOTIFY_TYPE'] = isset($notify['TYPE']) ? intval($notify['TYPE']) : CCrmActivityNotifyType::Min;
			$fields['NOTIFY_VALUE'] = isset($notify['VALUE']) ? intval($notify['VALUE']) : 15;
		}

		$bindings = array();
		if($ownerTypeID === CCrmOwnerType::Deal)
		{
			$bindings["{$ownerTypeName}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		$comms = array();
		if($commEntityType !== '')
		{
			$comms[] = array(
				'ID' => $commID,
				'TYPE' => $commType,
				'VALUE' => $commValue,
				'ENTITY_ID' => $commEntityID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
			);

			$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
			if(!isset($bindings[$bindingKey]))
			{
				$bindings[$bindingKey] = array(
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
					'OWNER_ID' => $commEntityID
				);
			}
		}

		if(empty($bindings))
		{
			$bindings["{$ownerTypeName}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		//$DB->StartTransaction();
		$successed = false;
		$errorMessage = '';
		if($isNew)
		{
			$fields['RESPONSIBLE_ID'] = $responsibleID > 0 ? $responsibleID : intval($curUser->GetID());
			$fields['BINDINGS'] = array_values($bindings);

			$ID = CCrmActivity::Add($fields, false, true, array('REGISTER_SONET_EVENT' => true));
			$successed = is_int($ID) && $ID > 0;
			if(!$successed)
			{
				$errorMessage = CCrmActivity::GetLastErrorMessage();
			}
		}
		else
		{
			$dbPresent = CCrmActivity::GetList(array(), array('ID'=>$ID), false, false, array('OWNER_ID', 'OWNER_TYPE_ID'));
			$presentFields = $dbPresent->Fetch();
			if(!is_array($presentFields))
			{
				__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_NOT_FOUND', array('#ID#' => $ID))));
			}

			if($responsibleID > 0)
			{
				$fields['RESPONSIBLE_ID'] = $responsibleID;
			}
			$fields['BINDINGS'] = array_values($bindings);

			$successed = CCrmActivity::Update($ID, $fields, false, true, array('REGISTER_SONET_EVENT' => true));
			if(!$successed)
			{
				$errorMessage = CCrmActivity::GetLastErrorMessage();
			}
		}

		if($successed)
		{
			CCrmActivity::SaveCommunications($ID, $comms, $fields, !$isNew, false);
			//$DB->Commit();
			CCrmActivity::SaveRecentlyUsedCommunication($comms[0]);

			$dbRes = CCrmActivity::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
			$currentItem = $dbRes->Fetch();
			$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

			CCrmMobileHelper::PrepareActivityItem($currentItem, $formatParams, array('ENABLE_COMMUNICATIONS' => true));
			__CrmMobileActivityEditEndResponse(
				array(
					'SAVED_ENTITY_ID' => $ID,
					'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareActivityData($currentItem)
				)
			);
		}
		else
		{
			//$DB->Rollback();
			__CrmMobileActivityEditEndResponse(array('ERROR' => $errorMessage));
		}
	}
	elseif($typeID === CCrmActivityType::Email)
	{
		if(empty($commData))
		{
			__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_SELECT_COMMUNICATION')));
		}

		$crmEmail = CCrmMailHelper::ExtractEmail(COption::GetOptionString('crm', 'mail', ''));
		$from = isset($data['FROM']) ? trim(strval($data['FROM'])) : '';
		if($from === '')
		{
			if($crmEmail !== '')
			{
				$from = $crmEmail;
			}
			else
			{
				__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_EDIT_EMAIL_EMPTY_FROM_FIELD')));
			}
		}
		elseif(!check_email($from))
		{
			__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_EDIT_INVALID_EMAIL', array('#VALUE#' => $from))));
		}

		$to = array();

		// Bindings & Communications -->
		$bindings = array();
		if($ownerTypeID === CCrmOwnerType::Deal)
		{
			$bindings["{$ownerTypeName}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}
		
		$comms = array();
		foreach($commData as &$comm)
		{
			$commID = isset($comm['ID']) ? intval($comm['ID']) : 0;
			$commEntityType = isset($comm['ENTITY_TYPE'])? mb_strtoupper(strval($comm['ENTITY_TYPE'])) : '';
			$commEntityID = isset($comm['ENTITY_ID']) ? intval($comm['ENTITY_ID']) : 0;

			$commType = isset($comm['TYPE'])? mb_strtoupper(strval($comm['TYPE'])) : '';
			if($commType === '')
			{
				$commType = 'EMAIL';
			}
			$commValue = isset($comm['VALUE']) ? strval($comm['VALUE']) : '';

			if($commType === 'EMAIL' && $commValue !== '')
			{
				if(!check_email($commValue))
				{
					__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_EDIT_INVALID_EMAIL', array('#VALUE#' => $commValue))));
				}
				$to[] = mb_strtolower(trim($commValue));
			}

			$comms[] = array(
				'ID' => $commID,
				'TYPE' => $commType,
				'VALUE' => $commValue,
				'ENTITY_ID' => $commEntityID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
			);

			if($commEntityType !== '')
			{
				$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
				if(!isset($bindings[$bindingKey]))
				{
					$bindings[$bindingKey] = array(
						'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
						'OWNER_ID' => $commEntityID
					);
				}
			}
		}
		unset($comm);

		if(empty($bindings))
		{
			$bindings["{$ownerTypeName}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}
		// <-- Bindings & Communications
		if(empty($to))
		{
			__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_EDIT_EMAIL_EMPTY_TO_FIELD')));
		}

		$nowStr = ConvertTimeStamp($now, 'FULL', SITE_ID);

		$subject = isset($data['SUBJECT']) ? strval($data['SUBJECT']) : '';
		if($subject === '')
		{
			$subject = GetMessage(
				'CRM_ACTIVITY_EDIT_EMAIL_ACTION_DEFAULT_SUBJECT',
				array('#DATE#'=> $nowStr)
			);
		}

		$descr = isset($data['DESCRIPTION']) ? strval($data['DESCRIPTION']) : '';
		if($descr === '')
		{
			$descr = $subject;
		}

		$fields = array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => $ownerTypeID,
			'TYPE_ID' =>  CCrmActivityType::Email,
			'SUBJECT' => $subject,
			'START_TIME' => $nowStr,
			'END_TIME' => $nowStr,
			'COMPLETED' => 'Y',
			'RESPONSIBLE_ID' => $curUser->GetID(),
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => $descr,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'DIRECTION' => CCrmActivityDirection::Outgoing,
			'LOCATION' => '',
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None
		);

		$storageTypeID = $fields['STORAGE_TYPE_ID'] = isset($data['STORAGE_TYPE_ID']) ? (int)$data['STORAGE_TYPE_ID'] : CCrmActivity::GetDefaultStorageTypeID();
		$storageElements = isset($data['STORAGE_ELEMENT_IDS']) ? $data['STORAGE_ELEMENT_IDS'] : null;
		if(is_array($storageElements) && (!empty($storageElements) || !$isNew))
		{
			$fields['STORAGE_ELEMENT_IDS'] = StorageManager::filterFiles($storageElements, $storageTypeID, $curUser->GetID());
		}

		if ($storageTypeID === \Bitrix\Crm\Integration\StorageType::File)
		{
			$fields['STORAGE_ELEMENT_IDS'] = [];
		}
		$responsibleID = isset($data['RESPONSIBLE_ID']) ? intval($data['RESPONSIBLE_ID']) : 0;

		//$DB->StartTransaction();
		$successed = false;
		$errorMessage = "";
		if($isNew)
		{
			$fields['BINDINGS'] = array_values($bindings);
			$fields['RESPONSIBLE_ID'] = $responsibleID > 0 ? $responsibleID : (int)$curUser->GetID();

			$ID = CCrmActivity::Add($fields, false, false, array('REGISTER_SONET_EVENT' => true));
			$successed = is_int($ID) && $ID > 0;
			if(!$successed)
			{
				$errorMessage = CCrmActivity::GetLastErrorMessage();
			}
		}
		else
		{
			$dbPresent = CCrmActivity::GetList(array(), array('ID'=>$ID), false, false, array('OWNER_ID', 'OWNER_TYPE_ID'));
			$presentFields = $dbPresent->Fetch();
			if(!is_array($presentFields))
			{
				__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_NOT_FOUND', array('#ID#' => $ID))));
			}

			$fields['BINDINGS'] = array_values($bindings);
			if($responsibleID > 0)
			{
				$fields['RESPONSIBLE_ID'] = $responsibleID;
			}

			$successed = CCrmActivity::Update($ID, $fields, false, false, array('REGISTER_SONET_EVENT' => true));
			if(!$successed)
			{
				$errorMessage = CCrmActivity::GetLastErrorMessage();
			}
		}

		if($successed)
		{
			$urn = CCrmActivity::PrepareUrn($arFields);
			if($urn !== '')
			{
				CCrmActivity::Update($ID, array('URN' => $urn), false, false);
			}
			CCrmActivity::SaveCommunications($ID, $comms, $fields, false, false);
			CCrmActivity::SaveRecentlyUsedCommunication($comms[0]);
			//Save user email in settings -->
			if($from !== CUserOptions::GetOption('crm', 'activity_email_addresser', ''))
			{
				CUserOptions::SetOption('crm', 'activity_email_addresser', $from);
			}
			//<-- Save user email in settings

			if (CModule::IncludeModule('subscribe'))
			{
				// Try to resolve posting charset -->
				$postingCharset = '';
				$siteCharset = defined('LANG_CHARSET') ? LANG_CHARSET : (defined('SITE_CHARSET') ? SITE_CHARSET : 'windows-1251');
				$arSupportedCharset = explode(',', COption::GetOptionString('subscribe', 'posting_charset'));
				if(count($arSupportedCharset) === 0)
				{
					$postingCharset = $siteCharset;
				}
				else
				{
					foreach($arSupportedCharset as $curCharset)
					{
						if(strcasecmp($curCharset, $siteCharset) === 0)
						{
							$postingCharset = $curCharset;
							break;
						}
					}

					if($postingCharset === '')
					{
						$postingCharset = $arSupportedCharset[0];
					}
				}
				//<-- Try to resolve posting charset

				//Creating Email -->
				$postingData = array(
					'STATUS' => 'D',
					'FROM_FIELD' => $from,
					'TO_FIELD' => $from,
					'BCC_FIELD' => implode(',', $to),
					'SUBJECT' => $subject,
					'BODY_TYPE' => 'html',
					'BODY' => htmlspecialcharsbx($descr),
					'DIRECT_SEND' => 'Y',
					'SUBSCR_FORMAT' => 'html',
					'CHARSET' => $postingCharset
				);
				CCrmActivity::InjectUrnInMessage(
					$postingData,
					$urn,
					CCrmEMailCodeAllocation::GetCurrent()
				);

				$posting = new CPosting();
				$postingID = $posting->Add($postingData);
				if($postingID > 0)
				{
					$updateFields = array('ASSOCIATED_ENTITY_ID'=> $postingID);

					$fromEmail = mb_strtolower(trim(CCrmMailHelper::ExtractEmail($from)));
					if($crmEmail !== '' && $fromEmail !== $crmEmail)
					{
						$updateFields['SETTINGS'] = array('MESSAGE_HEADERS' => array('Reply-To' => "<{$fromEmail}>, <$crmEmail>"));
					}
					CCrmActivity::Update($ID, $updateFields, false, false);
				}
				// <-- Creating Email

				// Attaching files -->
				$rawFiles = isset($fields['STORAGE_ELEMENT_IDS'])
					? StorageManager::makeFileArray($fields['STORAGE_ELEMENT_IDS'], $storageTypeID)
					: array();

				foreach($rawFiles as &$rawFile)
				{
					$posting->SaveFile($postingID, $rawFile);
				}
				unset($rawFile);
				// <-- Attaching files

				// Sending Email -->
				$posting->ChangeStatus($postingID, 'P');
				if(($e = $APPLICATION->GetException()) == false)
				{
					$rsAgents = CAgent::GetList(
						array('ID'=>'DESC'),
						array(
							'MODULE_ID' => 'subscribe',
							'NAME' => 'CPosting::AutoSend('.$postingID.',%',
						)
					);

					if(!$rsAgents->Fetch())
					{
						CAgent::AddAgent('CPosting::AutoSend('.$postingID.',true);', 'subscribe', 'N', 0);
					}
				}
				//<-- Sending Email
			}

			//Try add event to entity -->
			//Invoices have another event model
			if($ownerTypeID !== CCrmOwnerType::Invoice)
			{
				$eventText  = '';
				$eventText .= GetMessage('CRM_ACTIVITY_EDIT_TITLE_EMAIL_SUBJECT').': '.$subject."\n\r";
				$eventText .= GetMessage('CRM_ACTIVITY_EDIT_TITLE_EMAIL_FROM').': '.$from."\n\r";
				$eventText .= GetMessage('CRM_ACTIVITY_EDIT_TITLE_EMAIL_TO').': '.implode(',', $to)."\n\r\n\r";
				$eventText .= $descr;
				// Register event only for owner
				$CCrmEvent = new CCrmEvent();
				$CCrmEvent->Add(
					array(
						'ENTITY' => array(
							array(
								'ENTITY_TYPE' => $ownerTypeName,
								'ENTITY_ID' => $ownerID
							)
						),
						'EVENT_ID' => 'MESSAGE',
						'EVENT_TEXT_1' => $eventText,
						'FILES' => $rawFiles
					)
				);
			}
			//<-- Try add event to entity
			//$DB->Commit();


			$dbRes = CCrmActivity::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
			$currentItem = $dbRes->Fetch();
			$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

			CCrmMobileHelper::PrepareActivityItem($currentItem, $formatParams, array('ENABLE_COMMUNICATIONS' => true));
			__CrmMobileActivityEditEndResponse(
				array(
					'SAVED_ENTITY_ID' => $ID,
					'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareActivityData($currentItem)
				)
			);
		}
		else
		{
			//$DB->Rollback();
			__CrmMobileActivityEditEndResponse(array('ERROR' => $errorMessage));
		}
	}
}
elseif($action === 'DELETE_ENTITY')
{
	__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'ACTIVITY')
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	$item = CCrmActivity::GetByID($ID, false);
	if(!$item)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_NOT_FOUND', array('#ID#' => $ID))));
	}

	$ownerTypeID = isset($item['OWNER_TYPE_ID']) ? intval($item['OWNER_TYPE_ID']) : 0;
	$ownerID = isset($item['OWNER_ID']) ? intval($item['OWNER_ID']) : 0;
	if(!CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACCESS_DENIED')));
	}

	//$DB->StartTransaction();
	$successed = CCrmActivity::Delete($ID);
	if($successed)
	{
		//$DB->Commit();
		__CrmMobileActivityEditEndResponse(array('DELETED_ENTITY_ID' => $ID));
	}
	else
	{
		//$DB->Rollback();
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_COULD_NOT_DELETE')));
	}
}
elseif($action === 'GET_ENTITY')
{
	__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'ACTIVITY')
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$ID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	$item = CCrmActivity::GetByID($ID, true);
	if(!$item)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_NOT_FOUND', array('#ID#' => $ID))));
	}

	$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();
	CCrmMobileHelper::PrepareActivityItem($item, $formatParams, array('ENABLE_COMMUNICATIONS' => true));

	__CrmMobileActivityEditEndResponse(
		array('ENTITY' => CCrmMobileHelper::PrepareActivityData($item))
	);
}
elseif($action === 'COMPLETE')
{
	__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$typeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($typeName !== 'ACTIVITY')
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_TYPE_NOT_SUPPORTED', array('#ENTITY_TYPE#' => $typeName))));
	}

	$data = isset($_REQUEST['ENTITY_DATA']) && is_array($_REQUEST['ENTITY_DATA']) ? $_REQUEST['ENTITY_DATA'] : array();
	if(count($data) == 0)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_DATA_NOT_FOUND')));
	}

	$ID = isset($data['ID']) ? intval($data['ID']) : 0;
	if($ID <= 0)
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ENTITY_ID_NOT_FOUND')));
	}

	$dbItem = CCrmActivity::GetList(array(), array('=ID' => $ID));
	$item = $dbItem->Fetch();
	if(!is_array($item))
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => GetMessage('CRM_ACTIVITY_NOT_FOUND', array('#ID#' => $ID))));
	}

	$ownerTypeID = isset($item['OWNER_TYPE_ID']) ? intval($item['OWNER_TYPE_ID']) : 0;
	$ownerID = isset($item['OWNER_ID']) ? intval($item['OWNER_ID']) : 0;

	$provider = CCrmActivity::GetActivityProvider($item);
	if (!$provider || !$provider::checkReadPermission($item))
	{
		__CrmMobileActivityEditEndResponse(['ERROR' => 'Access denied.']);
	}

	$completed = (isset($data['COMPLETED']) ? intval($data['COMPLETED']) : 0) > 0;
	if(CCrmActivity::Complete($ID, $completed, array('REGISTER_SONET_EVENT' => true)))
	{
		$dbRes = CCrmActivity::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'));
		$currentItem = $dbRes->Fetch();
		$formatParams = isset($_REQUEST['FORMAT_PARAMS']) ? $_REQUEST['FORMAT_PARAMS'] : array();

		CCrmMobileHelper::PrepareActivityItem($currentItem, $formatParams, array('ENABLE_COMMUNICATIONS' => true));
		__CrmMobileActivityEditEndResponse(
			array(
				'SAVED_ENTITY_ID' => $ID,
				'SAVED_ENTITY_DATA' => CCrmMobileHelper::PrepareActivityData($currentItem)
			)
		);
	}
	else
	{
		__CrmMobileActivityEditEndResponse(array('ERROR' => CCrmActivity::GetLastErrorMessage()));
	}
}
else
{
	__CrmMobileActivityEditEndResponse(array('ERROR' => 'Action is not supported in current context.'));
}





<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES:
 * UPDATE - update lead field
 * GET_USER_INFO
 * CONVERT
 */
global $APPLICATION;

use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\LeadConversionConfig;
use Bitrix\Crm\Conversion\LeadConversionWizard;

$currentUser = CCrmSecurityHelper::GetCurrentUser();
$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if(!function_exists('__CrmLeadShowEndJsonResonse'))
{
	function __CrmLeadShowEndJsonResonse($result)
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
if(!function_exists('__CrmLeadShowEndHtmlResonse'))
{
	function __CrmLeadShowEndHtmlResonse()
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}
if(!function_exists('__CrmLeadShowErrorText'))
{
	function __CrmLeadShowConversionErrorText(\Bitrix\Crm\Conversion\EntityConversionException $e)
	{
		\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

		$entityTypeID =  $e->getTargetEntityTypeID();
		$entityTypeName =  CCrmOwnerType::ResolveName($entityTypeID);
		$code = $e->getCode();

		if($code === \Bitrix\Crm\Conversion\EntityConversionException::NOT_FOUND)
		{
			$msg = GetMessage("CRM_LEAD_CONVERSION_{$entityTypeName}_NOT_FOUND");
		}
		elseif($code === \Bitrix\Crm\Conversion\EntityConversionException::EMPTY_FIELDS)
		{
			$msg = GetMessage("CRM_LEAD_CONVERSION_{$entityTypeName}_EMPTY_FIELDS");
		}
		elseif($code === \Bitrix\Crm\Conversion\EntityConversionException::INVALID_FIELDS)
		{
			$msg = GetMessage("CRM_LEAD_CONVERSION_{$entityTypeName}_INVALID_FIELDS").preg_replace('/<br\s*\/?>/i', "\r\n", $e->getExtendedMessage());
		}
		elseif($code === \Bitrix\Crm\Conversion\EntityConversionException::CREATE_FAILED)
		{
			$msg = GetMessage("CRM_LEAD_CONVERSION_{$entityTypeName}_CREATE_FAILED").preg_replace('/<br\s*\/?>/i', "\r\n", $e->getExtendedMessage());
		}
		elseif($code === \Bitrix\Crm\Conversion\EntityConversionException::UPDATE_DENIED)
		{
			$msg = GetMessage("CRM_LEAD_CONVERSION_{$entityTypeName}_UPDATE_DENIED");
		}
		elseif($code === \Bitrix\Crm\Conversion\EntityConversionException::NOT_SUPPORTED)
		{
			$msg = GetMessage(
				'CRM_LEAD_CONVERSION_ENTITY_NOT_SUPPORTED',
				array('#ENTITY_TYPE_NAME#' => CCrmOwnerType::GetDescription($entityTypeID))
			);
		}
		else
		{
			$msg = $e->getMessage();
		}
		return $msg;
	}
}

$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if($mode === '' && isset($_POST['ACTION']))
{
	$mode = $_POST['ACTION'];
}

if($mode === '')
{
	__CrmLeadShowEndJsonResonse(array('ERROR'=>'MODE IS NOT DEFINED!'));
}

if($mode === 'GET_USER_INFO')
{
	$result = array();

	$userProfileUrlTemplate = isset($_POST['USER_PROFILE_URL_TEMPLATE']) ? $_POST['USER_PROFILE_URL_TEMPLATE'] : '';
	if(!CCrmInstantEditorHelper::PrepareUserInfo(
		isset($_POST['USER_ID']) ? intval($_POST['USER_ID']) : 0,
		$result,
		array('USER_PROFILE_URL_TEMPLATE' => $userProfileUrlTemplate)))
	{
		__CrmLeadShowEndJsonResonse(array('ERROR'=>'COULD NOT PREPARE USER INFO!'));
	}
	else
	{
		__CrmLeadShowEndJsonResonse(array('USER_INFO' => $result));
	}
}
if($mode === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmLeadShowEndJsonResonse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
if($mode === 'GET_USER_SELECTOR')
{
	if(!CCrmLead::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmLeadShowEndHtmlResonse();
	}

	$name = isset($_POST['NAME']) ? $_POST['NAME'] : '';

	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: text/html; charset='.LANG_CHARSET);
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.user.selector.new', '.default',
		array(
			'MULTIPLE' => 'N',
			'NAME' => $name,
			'POPUP' => 'Y',
			'SITE_ID' => SITE_ID
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	__CrmLeadShowEndHtmlResonse();
}
if($mode === 'GET_VISUAL_EDITOR')
{
	if(!CCrmLead::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmLeadShowEndHtmlResonse();
	}

	$lheEditorID = isset($_POST['EDITOR_ID']) ? $_POST['EDITOR_ID'] : '';
	$lheEditorName = isset($_POST['EDITOR_NAME']) ? $_POST['EDITOR_NAME'] : '';

	CModule::IncludeModule('fileman');
	$GLOBALS['APPLICATION']->RestartBuffer();
	Header('Content-Type: text/html; charset='.LANG_CHARSET);

	$emailEditor = new CLightHTMLEditor();
	$emailEditor->Show(
		array(
			'id' => $lheEditorID,
			'height' => '250',
			'BBCode' => false,
			'bUseFileDialogs' => false,
			'bFloatingToolbar' => false,
			'bArisingToolbar' => false,
			'bResizable' => false,
			'autoResizeOffset' => 20,
			'jsObjName' => $lheEditorName,
			'bInitByJS' => false,
			'bSaveOnBlur' => false,
			'toolbarConfig' => array(
				'Bold', 'Italic', 'Underline', 'Strike',
				'BackColor', 'ForeColor',
				'CreateLink', 'DeleteLink',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
			)
		)
	);
	__CrmLeadShowEndHtmlResonse();
}
if($mode === 'GET_ENTITY_INFO')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmLeadShowEndJsonResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID !== CCrmOwnerType::Lead)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR' => 'Entity type is not supported in current context.'));
	}


	$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR' => 'Entity ID is not specified.'));
	}

	if(!CCrmLead::CheckReadPermission($entityID, $userPermissions))
	{
		__CrmLeadShowEndJsonResonse(array('ERROR' => 'Access denied.'));
	}

	$data = CCrmEntitySelectorHelper::PrepareEntityInfo(
		CCrmOwnerType::LeadName,
		$entityID,
		array(
			'ENTITY_EDITOR_FORMAT' => true,
			'REQUIRE_REQUISITE_DATA' => false,
			'REQUIRE_MULTIFIELDS' => true
		)
	);

	__CrmLeadShowEndJsonResonse(array('DATA' => $data));
}
if($mode === 'GET_ENTITY_SIP_INFO')
{
	$entityType = isset($_POST['ENITY_TYPE']) ? $_POST['ENITY_TYPE'] : '';
	$m = null;
	if($entityType === '' || preg_match('/^CRM_([A-Z]+)$/i', $entityType, $m) !== 1)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR'=>'ENITY TYPE IS NOT DEFINED!'));
	}

	$entityTypeName = isset($m[1])? mb_strtoupper($m[1]) : '';
	if($entityTypeName !== CCrmOwnerType::LeadName)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR'=>'ENITY TYPE IS NOT DEFINED IS NOT SUPPORTED IN CURRENT CONTEXT!'));
	}

	$entityID = isset($_POST['ENITY_ID']) ? intval($_POST['ENITY_ID']) : 0;
	if($entityID <= 0)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR'=>'ENITY ID IS INVALID OR NOT DEFINED!'));
	}

	$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'Y'), false, false, array('TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE'));
	$arRes = $dbRes ? $dbRes->Fetch() : null;
	if(!$arRes)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR'=>'ENITY IS NOT FOUND!'));
	}
	else
	{
		$title = isset($arRes['TITLE']) ? $arRes['TITLE'] : '';
		if($title === '')
		{
			$title = CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
					'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
					'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
					'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
				)
			);
		}

		__CrmLeadShowEndJsonResonse(
			array('DATA' =>
				array(
					'TITLE' => $title,
					'LEGEND' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
					'IMAGE_URL' => '',
					'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Lead, $entityID, false),
				)
			)
		);
	}
}
if($mode === 'CONVERT')
{
	$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_LEAD_CONVERSION_ID_NOT_DEFINED'))));
	}

	if(!CCrmLead::Exists($entityID))
	{
		__CrmLeadShowEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_LEAD_CONVERSION_NOT_FOUND'))));
	}

	if(!CCrmLead::CheckReadPermission($entityID, $currentUserPermissions))
	{
		__CrmLeadShowEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_LEAD_CONVERSION_ACCESS_DENIED'))));
	}

	$entity = new \CCrmLead(false);
	$fieldsForCheck = array('STATUS_ID' => 'CONVERTED');
	if(!$entity->CheckFields($fieldsForCheck, $entityID))
	{
		$checkExceptions = $entity->GetCheckExceptions();
		$checkErrors = array();
		foreach($checkExceptions as $exception)
		{
			if($exception instanceof \CAdminException)
			{
				foreach($exception->GetMessages() as $message)
				{
					$checkErrors[$message['id']] = $message['text'];
				}
			}
		}
		__CrmLeadShowEndJsonResonse(
			array(
				'REQUIRED_ACTION' => array(
					'NAME' => 'CORRECT',
					'DATA' => array(
						'CONTEXT' => array('TARGET_STATUS_ID' => 'CONVERTED'),
						'CHECK_ERRORS' => $checkErrors
					)
				)
			)
		);
	}

	$configParams = isset($_POST['CONFIG']) && is_array($_POST['CONFIG']) ? $_POST['CONFIG'] : null;
	if(is_array($configParams))
	{
		$config = new LeadConversionConfig();
		$config->fromJavaScript($configParams);
		$config->save();
	}
	else
	{
		$config = LeadConversionConfig::load();
		if($config === null)
		{
			$config = LeadConversionConfig::getDefault();
		}
	}

	if(!isset($_POST['ENABLE_SYNCHRONIZATION']) || $_POST['ENABLE_SYNCHRONIZATION'] !== 'Y')
	{
		$needForSync = false;
		$entityConfigs = $config->getItems();
		$syncFieldNames = array();
		foreach($entityConfigs as $entityTypeID => $entityConfig)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			if(!CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName, $currentUserPermissions)
				&& !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, 0, $currentUserPermissions))
			{
				continue;
			}

			$enableSync = $entityConfig->isActive();
			if($enableSync)
			{
				$syncFields = UserFieldSynchronizer::getSynchronizationFields(CCrmOwnerType::Lead, $entityTypeID);
				$enableSync = !empty($syncFields);
				foreach($syncFields as $field)
				{
					$syncFieldNames[$field['ID']] = UserFieldSynchronizer::getFieldLabel($field);
				}
			}

			if($enableSync && !$needForSync)
			{
				$needForSync = true;
			}
			$entityConfig->enableSynchronization($enableSync);
		}

		if($needForSync)
		{
			__CrmLeadShowEndJsonResonse(
				array(
					'REQUIRED_ACTION' => array(
						'NAME' => 'SYNCHRONIZE',
						'DATA' => array(
							'CONFIG' => $config->toJavaScript(),
							'FIELD_NAMES' => array_values($syncFieldNames)
						)
					)
				)
			);
		}
	}
	else
	{
		$entityConfigs = $config->getItems();
		foreach($entityConfigs as $entityTypeID => $entityConfig)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			if(!CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName, $currentUserPermissions)
				&& !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, 0, $currentUserPermissions))
			{
				continue;
			}

			if(!$entityConfig->isActive())
			{
				continue;
			}

			if(!UserFieldSynchronizer::needForSynchronization(CCrmOwnerType::Lead, $entityTypeID))
			{
				continue;
			}

			if($entityConfig->isSynchronizationEnabled())
			{
				UserFieldSynchronizer::synchronize(\CCrmOwnerType::Lead, $entityTypeID);
			}
			else
			{
				UserFieldSynchronizer::markAsSynchronized(\CCrmOwnerType::Lead, $entityTypeID);
			}
		}
	}

	LeadConversionWizard::remove($entityID);
	$wizard = new LeadConversionWizard($entityID, $config);
	$wizard->setOriginUrl(isset($_POST['ORIGIN_URL']) ? $_POST['ORIGIN_URL'] : '');

	if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
	{
		$wizard->setSliderEnabled(true);
	}

	if(isset($_POST['ENABLE_REDIRECT_TO_SHOW']))
	{
		$wizard->setRedirectToShowEnabled(mb_strtoupper($_POST['ENABLE_REDIRECT_TO_SHOW']) === 'Y');
	}
	//region Preparation of context data
	$contextData = null;
	if(isset($_POST['CONTEXT']) && is_array($_POST['CONTEXT']))
	{
		$contextData = array();
		foreach($_POST['CONTEXT'] as $k => $v)
		{
			$entityTypeID = CCrmOwnerType::ResolveID($k);
			if($entityTypeID !== CCrmOwnerType::Undefined)
			{
				$contextData[CCrmOwnerType::ResolveName($entityTypeID)] = (int)$v;
			}
		}

		if(!empty($contextData))
		{
			$contextData['ENABLE_MERGE'] = true;
			$contextData['USER_ID'] = $currentUser->GetID();
			$contextData['MODE'] = 'LINK';
		}
	}
	//endregion
	if($wizard->execute($contextData))
	{
		__CrmLeadShowEndJsonResonse(
			array(
				'DATA' => array(
					'URL' => $wizard->getRedirectUrl(),
					'IS_FINISHED' => $wizard->isFinished() ? 'Y' : 'N',
					'RESULT' => $wizard->getResultData(),
				)
			)
		);
	}
	else
	{
		$url = $wizard->getRedirectUrl();
		if($url !== '')
		{
			__CrmLeadShowEndJsonResonse(
				array(
					'DATA' => array(
						'URL' => $url,
						'IS_FINISHED' => $wizard->isFinished() ? 'Y' : 'N',
						'RESULT' => $wizard->getResultData(),
					)
				)
			);
		}
		else
		{
			__CrmLeadShowEndJsonResonse(array('ERROR' => array('MESSAGE' => $wizard->getErrorText())));
		}
	}
}
$type = isset($_POST['OWNER_TYPE'])? mb_strtoupper($_POST['OWNER_TYPE']) : '';
if($type !== 'L')
{
	__CrmLeadShowEndJsonResonse(array('ERROR'=>'OWNER_TYPE IS NOT SUPPORTED!'));
}

$CCrmLead = new CCrmLead();
if ($CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE'))
{
	__CrmLeadShowEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
}

if($mode === 'UPDATE')
{
	$ID = isset($_POST['OWNER_ID']) ? $_POST['OWNER_ID'] : 0;
	if($ID <= 0)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR'=>'ID IS INVALID OR NOT DEFINED!'));
	}

	$fieldNames = array();
	$hasUserFields = false;
	if(isset($_POST['FIELD_NAME']))
	{
		if(is_array($_POST['FIELD_NAME']))
		{
			$fieldNames = $_POST['FIELD_NAME'];
			foreach($fieldNames as $fieldName)
			{
				if(strncmp($fieldName, 'UF_', 3) === 0)
				{
					$hasUserFields = true;
					break;
				}
			}
		}
		else
		{
			$fieldNames[] = $_POST['FIELD_NAME'];
			if(!$hasUserFields)
			{
				$hasUserFields = strncmp($_POST['FIELD_NAME'], 'UF_', 3) === 0;
			}
		}
	}

	if(count($fieldNames) == 0)
	{
		__CrmLeadShowEndJsonResonse(array('ERROR'=>'FIELD_NAME IS NOT DEFINED!'));
	}

	$fieldValues = array();
	if(isset($_POST['FIELD_VALUE']))
	{
		if(is_array($_POST['FIELD_VALUE']))
		{
			$fieldValues = $_POST['FIELD_VALUE'];
		}
		else
		{
			$fieldValues[] = $_POST['FIELD_VALUE'];
		}
	}

	$dbResult = CCrmLead::GetListEx(
		array(),
		array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
		false,
		false,
		array('*', 'UF_*')
	);
	$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
	if(is_array($arFields))
	{
		$prevFields = $arFields;
		CCrmInstantEditorHelper::PrepareUpdate(CCrmOwnerType::Lead, $arFields, $fieldNames, $fieldValues);
		$disableUserFieldCheck = !$hasUserFields
			&& isset($_POST['DISABLE_USER_FIELD_CHECK'])
			&& mb_strtoupper($_POST['DISABLE_USER_FIELD_CHECK']) === 'Y';
		if($CCrmLead->Update($ID, $arFields, true, true, array('REGISTER_SONET_EVENT' => true, 'DISABLE_USER_FIELD_CHECK' => $disableUserFieldCheck)))
		{
			$arErrors = array();
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Lead,
				$ID,
				CCrmBizProcEventType::Edit,
				$arErrors
			);

			//Region automation
			$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $ID);
			$starter->setUserIdFromCurrent();
			$starter->runOnUpdate($arFields, $prevFields);
			//end region

			$result = array();
			$count = count($fieldNames);
			for($i = 0; $i < $count; $i++)
			{
				$fieldName = $fieldNames[$i];
				if(mb_strpos($fieldName, 'FM.') === 0)
				{
					//Filed name like 'FM.PHONE.WORK.1279'
					$fieldParams = explode('.', $fieldName);
					if(count($fieldParams) >= 3)
					{
						$result[$fieldName] = array(
							'VIEW_HTML' =>
								CCrmViewHelper::PrepareMultiFieldHtml(
									$fieldParams[1],
									array(
										'VALUE_TYPE_ID' => $fieldParams[2],
										'VALUE' => isset($fieldValues[$i]) ? $fieldValues[$i] : ''
									)
								)
						);
					}
				}
			}

			__CrmLeadShowEndJsonResonse(array('DATA' => $result));
		}
	}
}
die();
?>

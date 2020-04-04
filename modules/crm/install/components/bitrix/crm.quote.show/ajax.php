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
 * 'UPDATE' - update quote field
 * 'GET_BINGINGS' - get entity bindings
 * 'SAVE_SELECTED_BINDING' - save selected binding
 */
global $APPLICATION;

use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\QuoteConversionConfig;
use Bitrix\Crm\Conversion\QuoteConversionWizard;

$currentUser = CCrmSecurityHelper::GetCurrentUser();
$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();
if (!$currentUser || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if(!function_exists('__CrmQuoteShowEndJsonResonse'))
{
	function __CrmQuoteShowEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
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
if(!function_exists('__CrmQuoteShowEndHtmlResonse'))
{
	function __CrmQuoteShowEndHtmlResonse()
	{
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}
CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if($mode === '' && isset($_POST['ACTION']))
{
	$mode = $_POST['ACTION'];
}
if($mode === '')
{
	__CrmQuoteShowEndJsonResonse(array('ERROR'=>'MODE IS NOT DEFINED!'));
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if($mode === 'SAVE_PDF')
{
	if (!($fileID = \CCrmQuote::savePdf($_POST['QUOTE_ID'], $_POST['PAY_SYSTEM_ID'], $err)))
		__CrmQuoteShowEndJsonResonse(array('ERROR' => $err));

	$fileArray = CFile::GetFileArray($fileID);

	$storageTypeID = \Bitrix\Crm\Integration\StorageType::getDefaultTypeID();
	if($storageTypeID !== \Bitrix\Crm\Integration\StorageType::File)
	{
		$storageFileID = \Bitrix\Crm\Integration\StorageManager::saveEmailAttachment($fileArray, $storageTypeID);
		$fileInfo = $storageFileID > 0 ? \Bitrix\Crm\Integration\StorageManager::getFileInfo($storageFileID, $storageTypeID) : null;
		if(is_array($fileInfo))
		{
			\Bitrix\Crm\Integration\StorageManager::registerInterRequestFile($storageFileID, $storageTypeID);
			if($storageTypeID === \Bitrix\Crm\Integration\StorageType::WebDav)
			{
				__CrmQuoteShowEndJsonResonse(array('webdavelement' => $fileInfo));
			}
			elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk)
			{
				__CrmQuoteShowEndJsonResonse(array('diskfile' => $fileInfo));
			}
		}
	}
	else
	{
		__CrmQuoteShowEndJsonResonse(
			array('file' =>
				array(
					"fileName" => $fileArray['FILE_NAME'],
					"fileID" => $fileID,
					"fileSize" => CFile::FormatSize($fileArray['FILE_SIZE']),
					"src" => $fileArray['SRC']
				)
			)
		);
	}
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
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'COULD NOT PREPARE USER INFO!'));
	}
	else
	{
		__CrmQuoteShowEndJsonResonse(array('USER_INFO' => $result));
	}
	__CrmQuoteShowEndJsonResonse(array());
}
if($mode === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmQuoteShowEndJsonResonse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}
if($mode === 'UPDATE')
{
	$type = isset($_POST['OWNER_TYPE']) ? strtoupper($_POST['OWNER_TYPE']) : '';
	if($type !== CCrmQuote::OWNER_TYPE)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'OWNER_TYPE IS NOT SUPPORTED!'));
	}

	$ID = isset($_POST['OWNER_ID']) ? $_POST['OWNER_ID'] : 0;
	if($ID <= 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ID IS INVALID OR NOT DEFINED!'));
	}

	if(!CCrmQuote::CheckUpdatePermission($ID, $userPermissions))
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'PERMISSION DENIED!'));
	}

	$fieldNames = array();
	if(isset($_POST['FIELD_NAME']))
	{
		if(is_array($_POST['FIELD_NAME']))
		{
			$fieldNames = $_POST['FIELD_NAME'];
		}
		else
		{
			$fieldNames[] = $_POST['FIELD_NAME'];
		}
	}

	if(count($fieldNames) == 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'FIELD_NAME IS NOT DEFINED!'));
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

	$dbResult = CCrmQuote::GetList(
		array(),
		array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
		false,
		false,
		array('*', 'UF_*')
	);
	$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;

	if(is_array($arFields))
	{
		CCrmInstantEditorHelper::PrepareUpdate(CCrmOwnerType::Quote, $arFields, $fieldNames, $fieldValues);
		$CCrmQuote = new CCrmQuote();
		$CCrmQuote->Update($ID, $arFields, true, true, array('REGISTER_SONET_EVENT' => true));
	}
	__CrmQuoteShowEndJsonResonse(array());
}
if($mode === 'GET_USER_SELECTOR')
{
	if(!CCrmQuote::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmQuoteShowEndJsonResonse(array());
	}

	$name = isset($_POST['NAME']) ? $_POST['NAME'] : '';

	$GLOBALS['APPLICATION']->RestartBuffer();
	header('Content-Type: text/html; charset='.LANG_CHARSET);
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
	__CrmQuoteShowEndHtmlResonse();
}
if($mode === 'GET_VISUAL_EDITOR')
{
	if(!CCrmQuote::CheckUpdatePermission(0, $currentUserPermissions))
	{
		__CrmQuoteShowEndJsonResonse(array());
	}

	$lheEditorID = isset($_POST['EDITOR_ID']) ? $_POST['EDITOR_ID'] : '';
	$lheEditorName = isset($_POST['EDITOR_NAME']) ? $_POST['EDITOR_NAME'] : '';

	CModule::IncludeModule('fileman');
	$GLOBALS['APPLICATION']->RestartBuffer();
	header('Content-Type: text/html; charset='.LANG_CHARSET);

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
	__CrmQuoteShowEndHtmlResonse();
}
if($mode === 'GET_ENTITY_SIP_INFO')
{
	$entityType = isset($_POST['ENITY_TYPE']) ? $_POST['ENITY_TYPE'] : '';
	$m = null;
	if($entityType === '' || preg_match('/^CRM_([A-Z]+)$/i', $entityType, $m) !== 1)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY TYPE IS NOT DEFINED!'));
	}

	$entityTypeName = isset($m[1]) ? strtoupper($m[1]) : '';
	if($entityTypeName !== CCrmOwnerType::QuoteName)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY TYPE IS NOT DEFINED IS NOT SUPPORTED IN CURRENT CONTEXT!'));
	}

	$entityID = isset($_POST['ENITY_ID']) ? intval($_POST['ENITY_ID']) : 0;
	if($entityID <= 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY ID IS INVALID OR NOT DEFINED!'));
	}

	$dbRes = CCrmQuote::GetList(array(), array('=ID' => $entityID, 'CHECK_PERMISSIONS' => 'Y'), false, false, array('TITLE'));
	$arRes = $dbRes ? $dbRes->Fetch() : null;
	if(!$arRes)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR'=>'ENITY IS NOT FOUND!'));
	}
	else
	{
		__CrmQuoteShowEndJsonResonse(
			array('DATA' =>
				array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'IMAGE_URL' => '',
					'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $entityID, false),
				)
			)
		);
	}
}
if($mode === 'CONVERT')
{
	if(!\Bitrix\Crm\Restriction\RestrictionManager::isConversionPermitted())
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_ACCESS_DENIED'))));
	}

	$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_ID_NOT_DEFINED'))));
	}

	if(!CCrmQuote::Exists($entityID))
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_NOT_FOUND'))));
	}

	if(!CCrmQuote::CheckReadPermission($entityID, $currentUserPermissions))
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => array('MESSAGE' => GetMessage('CRM_QUOTE_CONVERSION_ACCESS_DENIED'))));
	}

	$configParams = isset($_POST['CONFIG']) && is_array($_POST['CONFIG']) ? $_POST['CONFIG'] : null;
	if(is_array($configParams))
	{
		$config = new QuoteConversionConfig();
		$config->fromJavaScript($configParams);
		$config->save();
	}
	else
	{
		$config = QuoteConversionConfig::load();
		if($config === null)
		{
			$config = QuoteConversionConfig::getDefault();
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
				$syncFields = UserFieldSynchronizer::getSynchronizationFields(CCrmOwnerType::Quote, $entityTypeID);
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
			__CrmQuoteShowEndJsonResonse(
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

			if(!UserFieldSynchronizer::needForSynchronization(CCrmOwnerType::Quote, $entityTypeID))
			{
				continue;
			}

			if($entityConfig->isSynchronizationEnabled())
			{
				UserFieldSynchronizer::synchronize(\CCrmOwnerType::Quote, $entityTypeID);
			}
			else
			{
				UserFieldSynchronizer::markAsSynchronized(\CCrmOwnerType::Quote, $entityTypeID);
			}
		}
	}

	QuoteConversionWizard::remove($entityID);
	$wizard = new QuoteConversionWizard($entityID, $config);
	$wizard->setOriginUrl(isset($_POST['ORIGIN_URL']) ? $_POST['ORIGIN_URL'] : '');

	if(\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
	{
		$wizard->setSliderEnabled(true);
	}

	if($wizard->execute())
	{
		__CrmQuoteShowEndJsonResonse(
			array(
				'DATA' => array(
					'URL' => $wizard->getRedirectUrl(),
					'IS_FINISHED' => $wizard->isFinished() ? 'Y' : 'N'
				)
			)
		);
	}
	else
	{
		$url = $wizard->getRedirectUrl();
		if($url !== '')
		{
			__CrmQuoteShowEndJsonResonse(
				array(
					'DATA' => array(
						'URL' => $url,
						'IS_FINISHED' => $wizard->isFinished() ? 'Y' : 'N'
					)
				)
			);
		}
		else
		{
			__CrmQuoteShowEndJsonResonse(array('ERROR' => array('MESSAGE' => $wizard->getErrorText())));
		}
	}
}
if($mode === 'GET_BINGINGS')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	if($ownerTypeID !== CCrmOwnerType::Quote)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Entity type is not supported in current context.'));
	}

	$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
	if($ownerID <= 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Owner ID is not specified.'));
	}

	if(!CCrmQuote::CheckReadPermission($ownerID, $userPermissions))
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Access denied.'));
	}

	$formID = isset($params['FORM_ID']) ? $params['FORM_ID'] : '';

	__CrmQuoteShowEndJsonResonse(
		array(
			'DATA' => Bitrix\Crm\Binding\BindingHelper::prepareBindingInfos(
				$ownerTypeID,
				$ownerID,
				$entityTypeID,
				$formID
			)
		)
	);
}
if($mode === 'SAVE_SELECTED_BINDING')
{
	$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Entity type is not supported in current context.'));
	}

	$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
	if($entityID <= 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Entity ID is not specified.'));
	}

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	if($ownerTypeID !== CCrmOwnerType::Quote)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Entity type is not supported in current context.'));
	}

	$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
	if($ownerID <= 0)
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Owner ID is not specified.'));
	}

	if(!CCrmQuote::CheckUpdatePermission($ownerID, $userPermissions))
	{
		__CrmQuoteShowEndJsonResonse(array('ERROR' => 'Access denied.'));
	}

	if($currentUserID > 0)
	{
		\Bitrix\Crm\Config\EntityConfig::set(
			$ownerTypeID,
			$ownerID,
			$currentUserID,
			array('CONTACT_ID' => $entityID)
		);
	}

	__CrmQuoteShowEndJsonResonse(
		array(
			'DATA' => array(
				'OWNER_TYPE_NAME' => $ownerTypeName,
				'OWNER_ID' => $ownerID,
				'ENTITY_TYPE_NAME' => $entityTypeName,
				'ENTITY_ID' => $entityID
			)
		)
	);
}
__CrmQuoteShowEndHtmlResonse();

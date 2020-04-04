<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('crm'))
{
	return;
}

use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\QuoteConversionConfig;
use Bitrix\Crm\Conversion\QuoteConversionWizard;

$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();

if(!function_exists('__CrmShowEndJsonResonse'))
{
	function __CrmShowEndJsonResonse($result)
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

if($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["action"])>0 && check_bitrix_sessid())
{
	$action = $_POST["action"];

	switch ($action)
	{
		case "delete":
			$entityID = $_POST["itemId"];

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ID_NOT_DEFINED')));
			}
			if(!CCrmQuote::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_NOT_FOUND')));
			}
			if(!CCrmQuote::CheckDeletePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$obj = new CCrmQuote();
				$res = $obj->Delete($entityID);

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage("CRM_QUOTE_DELETE_ERROR")));
			}
			break;

		case "changeStatus":
			$entityID = isset($_POST['itemId']) ? (int)$_POST['itemId'] : 0;

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ID_NOT_DEFINED')));
			}
			if(!CCrmQuote::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_NOT_FOUND')));
			}
			if(!CCrmQuote::CheckUpdatePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ACCESS_DENIED')));
			}
			$statusId = $_POST["statusId"];

			if (intval($entityID))
			{
				$fields = array("STATUS_ID" => $statusId);

				$obj = new CCrmQuote();
				$res = $obj->Update($entityID, $fields);

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage("CRM_QUOTE_ERROR_CHANGE_STATUS")));
			}
			break;

		case "convert":
			$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ID_NOT_DEFINED')));
			}
			if(!CCrmQuote::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_NOT_FOUND')));
			}
			if(!\Bitrix\Crm\Restriction\RestrictionManager::isConversionPermitted())
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ACCESS_DENIED')));
			}

			if(!CCrmQuote::CheckReadPermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_QUOTE_ACCESS_DENIED')));
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
					__CrmShowEndJsonResonse(
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
			if($wizard->execute())
			{
				__CrmShowEndJsonResonse(array('DATA' => array('URL' => $wizard->getRedirectUrl())));
			}
			else
			{
				$url = $wizard->getRedirectUrl();
				if($url !== '')
				{
					__CrmShowEndJsonResonse(array('DATA' => array('URL' => $url, 'MODAL_SCREEN' => "Y")));
				}
				else
				{
					__CrmShowEndJsonResonse(array('ERROR' => $wizard->getErrorText()));
				}
			}

			break;
	}
}
?>
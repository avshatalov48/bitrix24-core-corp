<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('crm'))
{
	return;
}

use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\LeadConversionConfig;
use Bitrix\Crm\Conversion\LeadConversionWizard;

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
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_ID_NOT_DEFINED')));
			}
			if(!CCrmLead::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_NOT_FOUND')));
			}
			if(!CCrmLead::CheckDeletePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$arEntityAttr = $entityID > 0
					? $currentUserPermissions->GetEntityAttr('LEAD', array($entityID))
					: array();

				$CCrmBizProc = new CCrmBizProc('LEAD');
				if (!CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::LeadName, $entityID, $currentUserPermissions, $arEntityAttr))
				{
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_ACCESS_DENIED')));
				}
				elseif (!$CCrmBizProc->Delete($entityID, $arEntityAttr))
				{
					__CrmShowEndJsonResonse(array('ERROR' => $CCrmBizProc->LAST_ERROR));
				}

				$obj = new CCrmLead();
				$res = $obj->Delete($entityID, array('CHECK_DEPENDENCIES' => true, 'PROCESS_BIZPROC' => false));

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => $obj->LAST_ERROR));
			}
			break;
		case "changeStatus":
			$entityID = isset($_POST['itemId']) ? (int)$_POST['itemId'] : 0;

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_ID_NOT_DEFINED')));
			}
			if(!CCrmLead::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_NOT_FOUND')));
			}
			if(!CCrmLead::CheckUpdatePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_ACCESS_DENIED')));
			}

			$statusId = $_POST["statusId"];
			$fields = array("STATUS_ID" => $statusId);

			if (intval($entityID))
			{
				$obj = new CCrmLead();
				$res = $obj->Update($entityID, $fields);

				if ($res)
				{
					$arErrors = array();
					CCrmBizProcHelper::AutoStartWorkflows(
						CCrmOwnerType::Lead,
						$entityID,
						CCrmBizProcEventType::Edit,
						$arErrors
					);

					//Region automation
					if (class_exists('\Bitrix\Crm\Automation\Factory'))
					{
						\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $entityID);
					}
					//end region

					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				}
				else
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage("CRM_LEAD_ERROR_CHANGE_STATUS")));
			}
			break;
		case "convert":
			$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_ID_NOT_DEFINED')));
			}
			if(!CCrmLead::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_NOT_FOUND')));
			}
			if(!CCrmLead::CheckReadPermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_LEAD_ACCESS_DENIED')));
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
				}
			}

			//endregion
			if($wizard->execute($contextData))
			{
				__CrmShowEndJsonResonse(array('DATA' => array('URL' => $wizard->getRedirectUrl())));
			}
			else
			{
				$url = $wizard->getRedirectUrl();
				if($url !== '')//need to fill the form
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
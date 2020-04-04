<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('crm'))
{
	return;
}

use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Crm\Conversion\DealConversionConfig;
use Bitrix\Crm\Conversion\DealConversionWizard;

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
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_ID_NOT_DEFINED')));
			}
			if(!CCrmDeal::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_NOT_FOUND')));
			}
			if(!CCrmDeal::CheckDeletePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$arEntityAttr = $entityID > 0
					? $currentUserPermissions->GetEntityAttr('DEAL', array($entityID))
					: array();

				$CCrmBizProc = new CCrmBizProc('DEAL');
				if (!CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::DealName, $entityID, $currentUserPermissions, $arEntityAttr))
				{
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_ACCESS_DENIED')));
				}
				elseif (!$CCrmBizProc->Delete($entityID, $arEntityAttr))
				{
					__CrmShowEndJsonResonse(array('ERROR' => $CCrmBizProc->LAST_ERROR));
				}

				$obj = new CCrmDeal();
				$res = $obj->Delete($entityID, array('PROCESS_BIZPROC' => false));

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage("CRM_DEAL_DELETE_ERROR")));
			}
			break;
		case "changeStatus":
			$entityID = $_POST["itemId"];
			$stageId = $_POST["statusId"];

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_ID_NOT_DEFINED')));
			}
			if(!CCrmDeal::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_NOT_FOUND')));
			}
			if(!CCrmDeal::CheckUpdatePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$fields = array("STAGE_ID" => $stageId);

				$obj = new CCrmDeal();
				$res = $obj->Update($entityID, $fields);

				if ($res)
				{
					$arErrors = array();
					CCrmBizProcHelper::AutoStartWorkflows(
						CCrmOwnerType::Deal,
						$entityID,
						CCrmBizProcEventType::Edit,
						$arErrors
					);

					//Region automation
					if (class_exists('\Bitrix\Crm\Automation\Factory'))
					{
						\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Deal, $entityID);
					}
					//end region

					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				}
				else
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage("CRM_DEAL_ERROR_CHANGE_STATUS")));
			}
			break;
		case "convert":
			$entityID = isset($_POST['ENTITY_ID']) ? (int)$_POST['ENTITY_ID'] : 0;

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_ID_NOT_DEFINED')));
			}
			if(!CCrmDeal::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_NOT_FOUND')));
			}
			if(!CCrmDeal::CheckReadPermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_DEAL_ACCESS_DENIED')));
			}

			$configParams = isset($_POST['CONFIG']) && is_array($_POST['CONFIG']) ? $_POST['CONFIG'] : null;
			if(is_array($configParams))
			{
				$config = new DealConversionConfig();
				$config->fromJavaScript($configParams);
				$config->save();
			}
			else
			{
				$config = DealConversionConfig::load();
				if($config === null)
				{
					$config = DealConversionConfig::getDefault();
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
						$syncFields = UserFieldSynchronizer::getSynchronizationFields(CCrmOwnerType::Deal, $entityTypeID);
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

					if(!UserFieldSynchronizer::needForSynchronization(CCrmOwnerType::Deal, $entityTypeID))
					{
						continue;
					}

					if($entityConfig->isSynchronizationEnabled())
					{
						UserFieldSynchronizer::synchronize(\CCrmOwnerType::Deal, $entityTypeID);
					}
					else
					{
						UserFieldSynchronizer::markAsSynchronized(\CCrmOwnerType::Deal, $entityTypeID);
					}
				}
			}

			DealConversionWizard::remove($entityID);
			$wizard = new DealConversionWizard($entityID, $config);
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


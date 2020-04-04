<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

/** @global CMain $APPLICATION */

if (!CModule::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}
if (!CModule::includeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['BITRIX24'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');
$arResult['IS_EXCLUSION_ACCESSIBLE'] = \Bitrix\Crm\Exclusion\Access::current()->canRead();
$arResult['IS_BIZPRPOC_ENABLED'] = CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled();
$arResult['IS_AUTOMATION_LEAD_ENABLED'] = \Bitrix\Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Lead);
$arResult['IS_AUTOMATION_DEAL_ENABLED'] = \Bitrix\Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Deal);
$arResult['IS_AUTOMATION_ORDER_ENABLED'] = false;
//TODO: remove later
if (Bitrix\Main\Config\Option::get("crm", "crm_shop_enabled", "N") === 'Y')
{
	$arResult['IS_AUTOMATION_ORDER_ENABLED'] = \Bitrix\Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Order);
}

$arResult['IS_AUTOMATION_INVOICE_ENABLED'] = \Bitrix\Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Invoice);
$arResult['IS_APP_CONFIGURATION_ENABLED'] = \Bitrix\Main\Loader::includeModule('rest') && is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl');
$arResult['SMS_SENDERS'] = array();
$smsSenders = \Bitrix\Crm\Integration\SmsManager::getSenderInfoList();
foreach ($smsSenders as $sender)
{
	if ($sender['isConfigurable'])
	{
		$arResult['SMS_SENDERS'][] = $sender;
	}
}

$arResult['PERM_CONFIG'] = false;
$arResult['IS_ACCESS_ENABLED'] = false;
/** @var \CCrmPerms $crmPerms */
$crmPerms = CCrmPerms::getCurrentUserPermissions();
if(!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE))
	$arResult['PERM_CONFIG'] = true;
if($crmPerms->IsAccessEnabled())
	$arResult['IS_ACCESS_ENABLED'] = true;

$arResult['RAND_STRING'] = $this->randString();

$arResult['NUMERATOR_INVOICE_ID'] = '';
$numeratorInvoice = \Bitrix\Main\Numerator\Numerator::getOneByType(REGISTRY_TYPE_CRM_INVOICE);
if ($numeratorInvoice)
{
	$arResult['NUMERATOR_INVOICE_ID'] = $numeratorInvoice['id'];
}
$arResult['NUMERATOR_QUOTE_ID'] = '';
$numeratorQuote = \Bitrix\Main\Numerator\Numerator::getOneByType(REGISTRY_TYPE_CRM_QUOTE);
if ($numeratorQuote)
{
	$arResult['NUMERATOR_QUOTE_ID'] = $numeratorQuote['id'];
}

$title = GetMessage(GetMessage('CRM_TITLE1'));
if (!is_string($title) || empty($title))
	$title = GetMessage('CRM_TITLE');
$APPLICATION->SetTitle($title);
$this->includeComponentTemplate();
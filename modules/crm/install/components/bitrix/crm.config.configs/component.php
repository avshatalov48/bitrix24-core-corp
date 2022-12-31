<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

use \Bitrix\Crm\Settings;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

$arParams['PATH_TO_SM_CONFIG'] = CrmCheckPath('PATH_TO_SM_CONFIG', $arParams['PATH_TO_SM_CONFIG'], $APPLICATION->GetCurPage());
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;

CUtil::InitJSCore();
$bVarsFromForm = false;
$sMailFrom = COption::GetOptionString('crm', 'email_from');

if (empty($sMailFrom))
{
	$sMailFrom = COption::GetOptionString('crm', 'mail', '');
}

//Disable fake address generation for Bitrix24
if (empty($sMailFrom) && !IsModuleInstalled('bitrix24'))
{
	$sHost = $_SERVER['HTTP_HOST'];
	if (mb_strpos($sHost, ':') !== false)
		$sHost = mb_substr($sHost, 0, mb_strpos($sHost, ':'));

	$sMailFrom = 'crm@'.$sHost;
}

$dupControl = \Bitrix\Crm\Integrity\DuplicateControl::getCurrent();
$arResult['FORM_ID'] = 'CRM_SM_CONFIG';
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$activeTabKey = "{$arResult['FORM_ID']}_active_tab";
	$activeTabID = isset($_POST[$activeTabKey]) ? $_POST[$activeTabKey] : '';

	$bVarsFromForm = true;
	if(isset($_POST['save']) || isset($_POST['apply']))
	{
		$sError = '';

		/*Account number template settings*/
		$APPLICATION->ResetException();
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/crm.config.invoice.number/post_proc.php");
		if ($ex = $APPLICATION->GetException())
			$sError = $ex->GetString();

		$APPLICATION->ResetException();
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/crm.config.number/post_proc.php");
		if ($ex = $APPLICATION->GetException())
			$sError = $ex->GetString();

		$APPLICATION->ResetException();

		if ($sError <> '')
			ShowError($sError.'<br>');
		else
		{
			if(isset($_POST['CALENDAR_DISPLAY_COMPLETED_CALLS']))
			{
				Settings\ActivitySettings::setValue(
					Settings\ActivitySettings::KEEP_COMPLETED_CALLS,
					mb_strtoupper($_POST['CALENDAR_DISPLAY_COMPLETED_CALLS']) === 'Y'
				);
			}

			if(isset($_POST['CALENDAR_DISPLAY_COMPLETED_MEETINGS']))
			{
				Settings\ActivitySettings::setValue(
					Settings\ActivitySettings::KEEP_COMPLETED_MEETINGS,
					mb_strtoupper($_POST['CALENDAR_DISPLAY_COMPLETED_MEETINGS']) === 'Y'
				);
			}

			if(isset($_POST['CALENDAR_KEEP_REASSIGNED_CALLS']))
			{
				Settings\ActivitySettings::setValue(
					Settings\ActivitySettings::KEEP_REASSIGNED_CALLS,
					mb_strtoupper($_POST['CALENDAR_KEEP_REASSIGNED_CALLS']) === 'Y'
				);
			}

			if(isset($_POST['CALENDAR_KEEP_REASSIGNED_MEETINGS']))
			{
				Settings\ActivitySettings::setValue(
					Settings\ActivitySettings::KEEP_REASSIGNED_MEETINGS,
					mb_strtoupper($_POST['CALENDAR_KEEP_REASSIGNED_MEETINGS']) === 'Y'
				);
			}

			if(isset($_POST['KEEP_UNBOUND_TASKS']))
			{
				Settings\ActivitySettings::setValue(
					Settings\ActivitySettings::KEEP_UNBOUND_TASKS,
					mb_strtoupper($_POST['KEEP_UNBOUND_TASKS']) === 'Y'
				);
			}

			if(isset($_POST['MARK_FORWARDED_EMAIL_AS_OUTGOING']))
			{
				Settings\ActivitySettings::setValue(
					Settings\ActivitySettings::MARK_FORWARDED_EMAIL_AS_OUTGOING,
					mb_strtoupper($_POST['MARK_FORWARDED_EMAIL_AS_OUTGOING']) === 'Y'
				);
			}

			CCrmUserCounterSettings::SetValue(
				CCrmUserCounterSettings::ReckonActivitylessItems,
				isset($_POST['RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS']) && mb_strtoupper($_POST['RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS']) !== 'N'
			);

			CCrmEMailCodeAllocation::SetCurrent(
				isset($_POST['SERVICE_CODE_ALLOCATION'])
					? intval($_POST['SERVICE_CODE_ALLOCATION'])
					: CCrmEMailCodeAllocation::Body
			);

			Settings\ActivitySettings::getCurrent()->setOutgoingEmailOwnerTypeId(
				isset($_POST['OUTGOING_EMAIL_OWNER_TYPE'])
					? intval($_POST['OUTGOING_EMAIL_OWNER_TYPE'])
					: \CCrmOwnerType::Contact
			);

			if(Bitrix\Crm\Integration\Bitrix24Email::isEnabled()
				&& Bitrix\Crm\Integration\Bitrix24Email::allowDisableSignature())
			{
				Bitrix\Crm\Integration\Bitrix24Email::enableSignature(
					isset($_POST['ENABLE_B24_EMAIL_SIGNATURE']) && mb_strtoupper($_POST['ENABLE_B24_EMAIL_SIGNATURE']) !== 'N'
				);
			}

			$isCallSettingsChanged = false;

			$oldCalltoFormat = CCrmCallToUrl::GetFormat(0);
			$newCalltoFormat = isset($_POST['CALLTO_FORMAT']) ? intval($_POST['CALLTO_FORMAT']) : CCrmCallToUrl::Slashless;
			if ($oldCalltoFormat != $newCalltoFormat)
			{
				CCrmCallToUrl::SetFormat($newCalltoFormat);
				$isCallSettingsChanged = true;
			}

			$oldCalltoSettings = $newCalltoSettings = CCrmCallToUrl::GetCustomSettings();
			if($newCalltoFormat === CCrmCallToUrl::Custom)
			{
				$newCalltoSettings['URL_TEMPLATE'] = isset($_POST['CALLTO_URL_TEMPLATE']) ? $_POST['CALLTO_URL_TEMPLATE'] : '';
				$newCalltoSettings['CLICK_HANDLER'] = isset($_POST['CALLTO_CLICK_HANDLER']) ? $_POST['CALLTO_CLICK_HANDLER'] : '';
			}
			$newCalltoSettings['NORMALIZE_NUMBER'] = isset($_POST['CALLTO_NORMALIZE_NUMBER']) && mb_strtoupper($_POST['CALLTO_NORMALIZE_NUMBER']) === 'N' ? 'N' : 'Y';

			if (
				$oldCalltoSettings['URL_TEMPLATE'] != $newCalltoSettings['URL_TEMPLATE']
				|| $oldCalltoSettings['CLICK_HANDLER'] != $newCalltoSettings['CLICK_HANDLER']
				|| $oldCalltoSettings['NORMALIZE_NUMBER'] != $newCalltoSettings['NORMALIZE_NUMBER']
			)
			{
				CCrmCallToUrl::SetCustomSettings($newCalltoSettings);
				$isCallSettingsChanged = true;
			}

			if (defined('BX_COMP_MANAGED_CACHE') && $isCallSettingsChanged)
			{
				$GLOBALS['CACHE_MANAGER']->ClearByTag('CRM_CALLTO_SETTINGS');
			}

			if(isset($_POST['ENABLE_SIMPLE_TIME_FORMAT']))
			{
				\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->enableSimpleTimeFormat(
					mb_strtoupper($_POST['ENABLE_SIMPLE_TIME_FORMAT']) === 'Y'
				);
			}

			$entityAddressFormatID = isset($_POST['ENTITY_ADDRESS_FORMAT_ID'])
				? (int)$_POST['ENTITY_ADDRESS_FORMAT_ID'] : \Bitrix\Crm\Format\EntityAddressFormatter::Dflt;
			\Bitrix\Crm\Format\EntityAddressFormatter::setFormatID($entityAddressFormatID);

			$personFormatID = isset($_POST['PERSON_NAME_FORMAT_ID'])
				? (int)$_POST['PERSON_NAME_FORMAT_ID'] : \Bitrix\Crm\Format\PersonNameFormatter::Dflt;
			\Bitrix\Crm\Format\PersonNameFormatter::setFormatID($personFormatID);

			$dupControl->enabledFor(
				CCrmOwnerType::Lead,
				isset($_POST['ENABLE_LEAD_DUP_CONTROL']) && mb_strtoupper($_POST['ENABLE_LEAD_DUP_CONTROL']) === 'Y'
			);
			$dupControl->enabledFor(
				CCrmOwnerType::Contact,
				isset($_POST['ENABLE_CONTACT_DUP_CONTROL']) && mb_strtoupper($_POST['ENABLE_CONTACT_DUP_CONTROL']) === 'Y'
			);
			$dupControl->enabledFor(
				CCrmOwnerType::Company,
				isset($_POST['ENABLE_COMPANY_DUP_CONTROL']) && mb_strtoupper($_POST['ENABLE_COMPANY_DUP_CONTROL']) === 'Y'
			);
			$dupControl->save();

			CCrmStatus::EnableDepricatedTypes(
				isset($_POST['ENABLE_DEPRECATED_STATUSES']) && mb_strtoupper($_POST['ENABLE_DEPRECATED_STATUSES']) === 'Y'
			);

			\Bitrix\Crm\Settings\LeadSettings::getCurrent()->enableRecycleBin(
				isset($_POST['ENABLE_LEAD_RECYCLE_BIN']) && mb_strtoupper($_POST['ENABLE_LEAD_RECYCLE_BIN']) === 'Y'
			);

			\Bitrix\Crm\Settings\ContactSettings::getCurrent()->enableRecycleBin(
				isset($_POST['ENABLE_CONTACT_RECYCLE_BIN']) && mb_strtoupper($_POST['ENABLE_CONTACT_RECYCLE_BIN']) === 'Y'
			);

			\Bitrix\Crm\Settings\CompanySettings::getCurrent()->enableRecycleBin(
				isset($_POST['ENABLE_COMPANY_RECYCLE_BIN']) && mb_strtoupper($_POST['ENABLE_COMPANY_RECYCLE_BIN']) === 'Y'
			);

			\Bitrix\Crm\Settings\DealSettings::getCurrent()->enableRecycleBin(
				isset($_POST['ENABLE_DEAL_RECYCLE_BIN']) && mb_strtoupper($_POST['ENABLE_DEAL_RECYCLE_BIN']) === 'Y'
			);

			if(isset($_POST['LEAD_OPENED']))
			{
				\Bitrix\Crm\Settings\LeadSettings::getCurrent()->setOpenedFlag(
					mb_strtoupper($_POST['LEAD_OPENED']) === 'Y'
				);
			}

			if(isset($_POST['EXPORT_LEAD_PRODUCT_ROWS']))
			{
				\Bitrix\Crm\Settings\LeadSettings::getCurrent()->enableProductRowExport(
					mb_strtoupper($_POST['EXPORT_LEAD_PRODUCT_ROWS']) === 'Y'
				);
			}

			if(isset($_POST['AUTO_GEN_RC']))
			{
				\Bitrix\Crm\Settings\LeadSettings::getCurrent()->enableAutoGenRc(
					mb_strtoupper($_POST['AUTO_GEN_RC']) === 'Y'
				);
			}

			if(isset($_POST['AUTO_USING_FINISHED_LEAD']))
			{
				\Bitrix\Crm\Settings\LeadSettings::getCurrent()->enableAutoUsingFinishedLead(
					mb_strtoupper($_POST['AUTO_USING_FINISHED_LEAD']) === 'Y'
				);
			}

			if(isset($_POST['CONTACT_OPENED']))
			{
				\Bitrix\Crm\Settings\ContactSettings::getCurrent()->setOpenedFlag(
					mb_strtoupper($_POST['CONTACT_OPENED']) === 'Y'
				);
			}

			if($_POST['LEAD_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\LeadSettings::getCurrent()->setDefaultListViewID($_POST['LEAD_DEFAULT_LIST_VIEW']);
			}

			if(isset($_POST['COMPANY_OPENED']))
			{
				\Bitrix\Crm\Settings\CompanySettings::getCurrent()->setOpenedFlag(
					mb_strtoupper($_POST['COMPANY_OPENED']) === 'Y'
				);
			}

			if(isset($_POST['DEAL_OPENED']))
			{
				\Bitrix\Crm\Settings\DealSettings::getCurrent()->setOpenedFlag(
					mb_strtoupper($_POST['DEAL_OPENED']) === 'Y'
				);
			}

			if(isset($_POST['REFRESH_DEAL_CLOSEDATE']))
			{
				\Bitrix\Crm\Settings\DealSettings::getCurrent()->enableCloseDateSync(
					mb_strtoupper($_POST['REFRESH_DEAL_CLOSEDATE']) === 'Y'
				);
			}

			if(isset($_POST['EXPORT_DEAL_PRODUCT_ROWS']))
			{
				\Bitrix\Crm\Settings\DealSettings::getCurrent()->enableProductRowExport(
					mb_strtoupper($_POST['EXPORT_DEAL_PRODUCT_ROWS']) === 'Y'
				);
			}

			if($_POST['DEAL_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\DealSettings::getCurrent()->setDefaultListViewID($_POST['DEAL_DEFAULT_LIST_VIEW']);
			}

			if($_POST['INVOICE_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->setDefaultListViewID($_POST['INVOICE_DEFAULT_LIST_VIEW']);
			}

			if($_POST['ORDER_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\OrderSettings::getCurrent()->setDefaultListViewID($_POST['ORDER_DEFAULT_LIST_VIEW']);
			}

			if($_POST['ORDER_DEFAULT_RESPONSIBLE_ID'])
			{
				\Bitrix\Crm\Settings\OrderSettings::getCurrent()->setDefaultResponsibleId($_POST['ORDER_DEFAULT_RESPONSIBLE_ID']);
			}

			if($_POST['ENABLE_ENABLED_PUBLIC_B24_SIGN'])
			{
				\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->setEnableSignFlag(
					mb_strtoupper($_POST['ENABLE_ENABLED_PUBLIC_B24_SIGN']) === 'Y'
				);
			}

			if($_POST['INVOICE_OLD_ENABLED'])
			{
				\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->setOldInvoicesEnabled(
					mb_strtoupper($_POST['INVOICE_OLD_ENABLED']) === 'Y'
				);
			}

			if($_POST['COMPANY_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\CompanySettings::getCurrent()->setDefaultListViewID($_POST['COMPANY_DEFAULT_LIST_VIEW']);
			}

			if($_POST['CONTACT_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\ContactSettings::getCurrent()->setDefaultListViewID($_POST['CONTACT_DEFAULT_LIST_VIEW']);
			}

			if($_POST['ACTIVITY_DEFAULT_LIST_VIEW'])
			{
				\Bitrix\Crm\Settings\ActivitySettings::getCurrent()->setDefaultListViewID($_POST['ACTIVITY_DEFAULT_LIST_VIEW']);
			}

			if(isset($_POST['QUOTE_OPENED']))
			{
				\Bitrix\Crm\Settings\QuoteSettings::getCurrent()->setOpenedFlag(
					mb_strtoupper($_POST['QUOTE_OPENED']) === 'Y'
				);
			}

			if(isset($_POST['CONVERSION_ENABLE_AUTOCREATION']))
			{
				\Bitrix\Crm\Settings\ConversionSettings::getCurrent()->enableAutocreation(
					mb_strtoupper($_POST['CONVERSION_ENABLE_AUTOCREATION']) === 'Y'
				);
			}

			if(isset($_POST['WEBFORM_EDITOR']))
			{
				\Bitrix\Crm\Settings\WebFormSettings::getCurrent()->setEditorId(
					(int) $_POST['WEBFORM_EDITOR'] ?? 0
				);
			}

			if(isset($_POST['NOTIFICATIONS_SENDER']))
			{
				\Bitrix\Crm\MessageSender\SettingsManager::setValue($_POST['NOTIFICATIONS_SENDER']);
			}

			if(isset($_POST['ENABLE_EXPORT_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableExportEvent(
					mb_strtoupper($_POST['ENABLE_EXPORT_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_VIEW_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableViewEvent(
					mb_strtoupper($_POST['ENABLE_VIEW_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['VIEW_EVENT_GROUPING_INTERVAL']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->setViewEventGroupingInterval(
					(int)$_POST['VIEW_EVENT_GROUPING_INTERVAL']
				);
			}

			if(isset($_POST['ENABLE_LEAD_DELETION_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableLeadDeletionEvent(
					mb_strtoupper($_POST['ENABLE_LEAD_DELETION_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_DEAL_DELETION_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableDealDeletionEvent(
					mb_strtoupper($_POST['ENABLE_DEAL_DELETION_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_QUOTE_DELETION_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableQuoteDeletionEvent(
					mb_strtoupper($_POST['ENABLE_QUOTE_DELETION_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_DEAL_DELETION_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableDealDeletionEvent(
					mb_strtoupper($_POST['ENABLE_DEAL_DELETION_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_CONTACT_DELETION_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableContactDeletionEvent(
					mb_strtoupper($_POST['ENABLE_CONTACT_DELETION_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_COMPANY_DELETION_EVENT']))
			{
				\Bitrix\Crm\Settings\HistorySettings::getCurrent()->enableCompanyDeletionEvent(
					mb_strtoupper($_POST['ENABLE_COMPANY_DELETION_EVENT']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_LIVEFEED_MERGE']))
			{
				\Bitrix\Crm\Settings\LiveFeedSettings::getCurrent()->enableLiveFeedMerge(
					mb_strtoupper($_POST['ENABLE_LIVEFEED_MERGE']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_REST_REQ_USER_FIELD_CHECK']))
			{
				\Bitrix\Crm\Settings\RestSettings::getCurrent()->enableRequiredUserFieldCheck(
					mb_strtoupper($_POST['ENABLE_REST_REQ_USER_FIELD_CHECK']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_SLIDER']))
			{
				\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->enableSlider(
					mb_strtoupper($_POST['ENABLE_SLIDER']) === 'Y'
				);
			}

			if (isset($_POST['ENABLE_FULL_CATALOG']))
			{
				\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->enableFullCatalog(
					mb_strtoupper($_POST['ENABLE_FULL_CATALOG']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_CREATION_ENTITY_COMMODITY_ITEM']))
			{
				\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->enableEntityCommodityItemCreation(
					mb_strtoupper($_POST['ENABLE_CREATION_ENTITY_COMMODITY_ITEM']) === 'Y'
				);
			}

			if(isset($_POST['ENABLE_USER_NAME_SORTING']))
			{
				\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->enableUserNameSorting(
					mb_strtoupper($_POST['ENABLE_USER_NAME_SORTING']) === 'Y'
				);
			}

			if(isset($_POST['RECYCLEBIN_TTL']))
			{
				\Bitrix\Crm\Settings\RecyclebinSettings::getCurrent()->setTtl(
					(int) $_POST['RECYCLEBIN_TTL']
				);
			}

			$activityCompetionConfig = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getActivityCompletionConfig();
			foreach(\Bitrix\Crm\Activity\Provider\ProviderManager::getCompletableProviderList() as $providerInfo)
			{
				$providerID = $providerInfo['ID'];
				$fieldName = "COMPLETE_ACTIVITY_ON_LEAD_CONVERT_{$providerID}";
				if(isset($_POST[$fieldName]))
				{
					$activityCompetionConfig[$providerID] = mb_strtoupper($_POST[$fieldName]) === 'Y';
				}
			}
			\Bitrix\Crm\Settings\LeadSettings::getCurrent()->setActivityCompletionConfig($activityCompetionConfig);

			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					CCrmUrlUtil::AddUrlParams(
						$arParams['PATH_TO_SM_CONFIG'],
						array($activeTabKey => $activeTabID)
					),
					array()
				)
			);
		}
	}
}

$arResult['FIELDS'] = array();

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'LAYOUT_CONFIG',
	'name' => GetMessage('CRM_SECTION_LAYOUT_CONFIG'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'ENABLE_SLIDER',
	'name' => GetMessage('CRM_FIELD_ENABLE_SLIDER2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled(),
	'required' => false
);

if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
{
	$arResult['FIELDS']['tab_main'][] = array(
		'id' => 'ENABLE_FULL_CATALOG',
		'name' => GetMessage('CRM_FIELD_ENABLE_FULL_CATALOG'),
		'type' => 'checkbox',
		'value' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isFullCatalogEnabled(),
		'required' => false,
	);
}

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'ENABLE_USER_NAME_SORTING',
	'name' => GetMessage('CRM_FIELD_ENABLE_USER_NAME_SORTING2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isUserNameSortingEnabled(),
	'required' => false
);

if (\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isCommonProductProcessingEnabled())
{
	$arResult['FIELDS']['tab_main'][] = array(
		'id' => 'PRODUCT_CONFIG',
		'name' => GetMessage('CRM_SECTION_PRODUCT_CONFIG_MSGVER_1'),
		'type' => 'section'
	);

	$arResult['FIELDS']['tab_main'][] = array(
		'id' => 'ENABLE_CREATION_ENTITY_COMMODITY_ITEM',
		'name' => GetMessage('CRM_FIELD_ENABLE_CREATION_ENTITY_COMMODITY_ITEM'),
		'title' => GetMessage('CRM_FIELD_ENABLE_CREATION_ENTITY_COMMODITY_ITEM_HINT'),
		'type' => 'checkbox',
		'value' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isCreationEntityCommodityItemAllowed(),
		'required' => false
	);
}

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'LEAD_CONFIG',
	'name' => GetMessage('CRM_SECTION_LEAD_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'LEAD_OPENED',
	'name' => GetMessage('CRM_FIELD_LEAD_OPENED2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getOpenedFlag(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'LEAD_DEFAULT_LIST_VIEW',
	'name' => GetMessage('CRM_FIELD_LEAD_DEFAULT_LIST_VIEW'),
	'items' => \Bitrix\Crm\Settings\LeadSettings::getViewDescriptions(),
	'type' => 'list',
	'value' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getDefaultListViewID(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'EXPORT_LEAD_PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_EXPORT_PRODUCT_ROWS'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isProductRowExportEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'AUTO_GEN_RC',
	'name' => GetMessage('CRM_FIELD_AUTO_GEN_RC'),
	'type' => 'checkbox',
	'show' => Settings\LeadSettings::getCurrent()->isEnabled() ? 'Y' : 'N',
	'value' => Settings\LeadSettings::getCurrent()->isAutoGenRcEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'AUTO_USING_FINISHED_LEAD',
	'name' => GetMessage('CRM_FIELD_AUTO_USING_FINISHED_LEAD'),
	'type' => 'checkbox',
	'show' => (
			Settings\LeadSettings::getCurrent()->isAutoGenRcEnabled()
			||
			!Settings\LeadSettings::getCurrent()->isEnabled()
		) ? 'N' : 'Y',
	'value' => Settings\LeadSettings::getCurrent()->isAutoUsingFinishedLeadEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'CONTACT_CONFIG',
	'name' => GetMessage('CRM_SECTION_CONTACT_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'CONTACT_OPENED',
	'name' => GetMessage('CRM_FIELD_CONTACT_OPENED2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getOpenedFlag(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'CONTACT_DEFAULT_LIST_VIEW',
	'name' => GetMessage('CRM_FIELD_DEAL_DEFAULT_LIST_VIEW'),
	'items' => \Bitrix\Crm\Settings\ContactSettings::getViewDescriptions(),
	'type' => 'list',
	'value' => \Bitrix\Crm\Settings\ContactSettings::getCurrent()->getDefaultListViewID(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'COMPANY_CONFIG',
	'name' => GetMessage('CRM_SECTION_COMPANY_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'COMPANY_OPENED',
	'name' => GetMessage('CRM_FIELD_COMPANY_OPENED2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getOpenedFlag(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'COMPANY_DEFAULT_LIST_VIEW',
	'name' => GetMessage('CRM_FIELD_DEAL_DEFAULT_LIST_VIEW'),
	'items' => \Bitrix\Crm\Settings\CompanySettings::getViewDescriptions(),
	'type' => 'list',
	'value' => \Bitrix\Crm\Settings\CompanySettings::getCurrent()->getDefaultListViewID(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'DEAL_CONFIG',
	'name' => GetMessage('CRM_SECTION_DEAL_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'DEAL_OPENED',
	'name' => GetMessage('CRM_FIELD_DEAL_OPENED2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\DealSettings::getCurrent()->getOpenedFlag(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'DEAL_DEFAULT_LIST_VIEW',
	'name' => GetMessage('CRM_FIELD_DEAL_DEFAULT_LIST_VIEW'),
	'items' => \Bitrix\Crm\Settings\DealSettings::getViewDescriptions(),
	'type' => 'list',
	'value' => \Bitrix\Crm\Settings\DealSettings::getCurrent()->getDefaultListViewID(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'REFRESH_DEAL_CLOSEDATE',
	'name' => GetMessage('CRM_FIELD_REFRESH_DEAL_CLOSEDATE2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\DealSettings::getCurrent()->isCloseDateSyncEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'EXPORT_DEAL_PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_EXPORT_PRODUCT_ROWS'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\DealSettings::getCurrent()->isProductRowExportEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'INVOICE_CONFIG',
	'name' => GetMessage('CRM_SECTION_INVOICE_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'INVOICE_DEFAULT_LIST_VIEW',
	'name' => GetMessage('CRM_FIELD_INVOICE_DEFAULT_LIST_VIEW'),
	'items' => \Bitrix\Crm\Settings\InvoiceSettings::getViewDescriptions(),
	'type' => 'list',
	'value' => \Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->getDefaultListViewID(),
	'required' => false
);

if(\Bitrix\Crm\Settings\InvoiceSettings::allowDisableSign())
{
	$arResult['FIELDS']['tab_main'][] = array(
		'id' => 'ENABLE_ENABLED_PUBLIC_B24_SIGN',
		'name' => GetMessage('CRM_FIELD_PUBLIC_INVOICE_B24_SIGN'),
		'type' => 'checkbox',
		'value' => \Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->getEnableSignFlag(),
		'required' => false
	);
}
else
{
	$arResult['FIELDS']['tab_main'][] = array(
		'id' => 'ENABLE_ENABLED_PUBLIC_B24_SIGN',
		'name' => GetMessage('CRM_FIELD_PUBLIC_INVOICE_B24_SIGN'),
		'type' => 'label',
		'value' =>  GetMessage('CRM_FIELD_PUBLIC_INVOICE_B24_SIGN_ENABLED'),
		'required' => false
	);
}

if (Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnablingPossible())
{
	$arResult['FIELDS']['tab_main'][] = array(
		'id' => 'INVOICE_OLD_ENABLED',
		'name' => GetMessage('CRM_FIELD_INVOICE_OLD_ENABLED'),
		'type' => 'checkbox',
		'value' => \Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled(),
		'required' => false,
	);
}

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'QUOTE_CONFIG',
	'name' => GetMessage('CRM_SECTION_QUOTE_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'QUOTE_OPENED',
	'name' => GetMessage('CRM_FIELD_QUOTE_OPENED2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->getOpenedFlag(),
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'CONVERSION_CONFIG',
	'name' => GetMessage('CRM_SECTION_CONVERSION_CONFIG2'),
	'type' => 'section'
);

$conversionEnableAutocreationType = 'checkbox';
$conversionEnableAutocreationValue = Settings\ConversionSettings::getCurrent()->isAutocreationEnabled();

if (!Settings\LeadSettings::isEnabled())
{
	$conversionEnableAutocreationType = 'custom';
	$conversionEnableAutocreationValue = '<span>'.GetMessage('MAIN_YES').'</span>';
}

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'CONVERSION_ENABLE_AUTOCREATION',
	'name' => GetMessage('CRM_FIELD_CONVERSION_ENABLE_AUTOCREATION'),
	'type' => $conversionEnableAutocreationType,
	'value' => $conversionEnableAutocreationValue,
	'required' => false
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'NOTIFICATIONS_CONFIG',
	'name' => GetMessage('CRM_SECTION_NOTIFICATIONS_CONFIG'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_main'][] = array(
	'id' => 'NOTIFICATIONS_SENDER',
	'name' => GetMessage('CRM_SECTION_NOTIFICATIONS_SENDER2'),
	'type' => 'list',
	'items' => \Bitrix\Crm\MessageSender\SettingsManager::getSettingsList(),
	'value' =>  \Bitrix\Crm\MessageSender\SettingsManager::getValue(),
	'required' => false
);

$arResult['FIELDS']['tab_rest'][] = array(
	'id' => 'ENABLE_REST_REQ_USER_FIELD_CHECK',
	'name' => GetMessage('CRM_FIELD_ENABLE_REST_REQ_USER_FIELD_CHECK'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\RestSettings::getCurrent()->isRequiredUserFieldCheckEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'ACTIVITY_GENERAL_CONFIG',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_GENERAL_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_DISPLAY_COMPLETED_CALLS',
	'name' => GetMessage('CRM_FIELD_DISPLAY_COMPLETED_CALLS_IN_CALENDAR'),
	'type' => 'checkbox',
	'value' => Settings\ActivitySettings::getValue(Settings\ActivitySettings::KEEP_COMPLETED_CALLS),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_DISPLAY_COMPLETED_MEETINGS',
	'name' => GetMessage('CRM_FIELD_DISPLAY_COMPLETED_MEETINGS_IN_CALENDAR'),
	'type' => 'checkbox',
	'value' => Settings\ActivitySettings::getValue(Settings\ActivitySettings::KEEP_COMPLETED_MEETINGS),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_KEEP_REASSIGNED_CALLS',
	'name' => GetMessage('CRM_FIELD_KEEP_REASSIGNED_CALLS'),
	'type' => 'checkbox',
	'value' => Settings\ActivitySettings::getValue(Settings\ActivitySettings::KEEP_REASSIGNED_CALLS),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_KEEP_REASSIGNED_MEETINGS',
	'name' => GetMessage('CRM_FIELD_KEEP_REASSIGNED_MEETINGS'),
	'type' => 'checkbox',
	'value' => Settings\ActivitySettings::getValue(Settings\ActivitySettings::KEEP_REASSIGNED_MEETINGS),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'KEEP_UNBOUND_TASKS',
	'name' => GetMessage('CRM_FIELD_KEEP_UNBOUND_TASKS2'),
	'type' => 'checkbox',
	'value' => Settings\ActivitySettings::getValue(Settings\ActivitySettings::KEEP_UNBOUND_TASKS),
	'required' => false
);
if (!Crm::isUniversalActivityScenarioEnabled())
{
	$arResult['FIELDS']['tab_activity_config'][] = [
		'id' => 'RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS',
		'name' => GetMessage('CRM_FIELD_RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS2'),
		'type' => 'checkbox',
		'value' => CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true),
		'required' => false
	];
}

$activityCompetionConfig = \Bitrix\Crm\Settings\LeadSettings::getCurrent()->getActivityCompletionConfig();
$html = '';
foreach(\Bitrix\Crm\Activity\Provider\ProviderManager::getCompletableProviderList() as $providerInfo)
{
	$providerID = $providerInfo['ID'];
	$providerName = htmlspecialcharsbx($providerInfo['NAME']);
	$fieldName = "COMPLETE_ACTIVITY_ON_LEAD_CONVERT_{$providerID}";
	$enabled = !isset($activityCompetionConfig[$providerID]) || $activityCompetionConfig[$providerID];

	$html .= '<div>';
	$html .= '<input name="'.$fieldName.'" type="hidden" value="'.($enabled ? 'Y' : 'N').'"/>';
	$html .= "<input id='{$fieldName}' type='checkbox'";
	if($enabled)
	{
		$html .= " checked";
	}
	$html .= " onclick='document.getElementsByName(this.id)[0].value = this.checked ? \"Y\" : \"N\";'";
	$html .= "/>";
	$html .= "<label>{$providerName}</label>";
	$html .= '</div>';
}

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'COMPLETE_ACTIVITY_ON_LEAD_CONVERT',
	'name' => GetMessage('CRM_FIELD_COMPLETE_ACTIVITY_ON_LEAD_CONVERT_2'),
	'type' => 'custom',
	'value' => $html,
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'ACTIVITY_DEFAULT_LIST_VIEW',
	'name' => GetMessage('CRM_FIELD_DEAL_DEFAULT_LIST_VIEW'),
	'items' => \Bitrix\Crm\Settings\ActivitySettings::getViewDescriptions(),
	'type' => 'list',
	'value' => \Bitrix\Crm\Settings\ActivitySettings::getCurrent()->getDefaultListViewID(),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'ACTIVITY_INCOMING_EMAIL_CONFIG',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_INCOMING_EMAIL_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'MARK_FORWARDED_EMAIL_AS_OUTGOING',
	'name' => GetMessage('CRM_FIELD_MARK_FORWARDED_EMAIL_AS_OUTGOING2'),
	'type' => 'checkbox',
	'value' => Settings\ActivitySettings::getValue(Settings\ActivitySettings::MARK_FORWARDED_EMAIL_AS_OUTGOING),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'ACTIVITY_OUTGOING_EMAIL_CONFIG',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_OUTGOING_EMAIL_CONFIG2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'SERVICE_CODE_ALLOCATION',
	'name' => GetMessage('CRM_FIELD_SERVICE_CODE_ALLOCATION'),
	'items' => CCrmEMailCodeAllocation::GetAllDescriptions(),
	'type' => 'list',
	'value' => CCrmEMailCodeAllocation::GetCurrent(),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'OUTGOING_EMAIL_OWNER_TYPE',
	'name' => GetMessage('CRM_FIELD_OUTGOING_EMAIL_OWNER_TYPE2'),
	'items' => array(
		\CCrmOwnerType::Lead => \CCrmOwnerType::getDescription(\CCrmOwnerType::Lead),
		\CCrmOwnerType::Contact => \CCrmOwnerType::getDescription(\CCrmOwnerType::Contact),
	),
	'type' => 'list',
	'value' => Settings\ActivitySettings::getCurrent()->getOutgoingEmailOwnerTypeId(),
	'required' => false
);

if(Bitrix\Crm\Integration\Bitrix24Email::isEnabled())
{
	if(Bitrix\Crm\Integration\Bitrix24Email::allowDisableSignature())
	{
		$arResult['FIELDS']['tab_activity_config'][] = array(
			'id' => 'ENABLE_B24_EMAIL_SIGNATURE',
			'name' => GetMessage('CRM_FIELD_ENABLE_B24_EMAIL_SIGNATURE'),
			'type' => 'checkbox',
			'value' => Bitrix\Crm\Integration\Bitrix24Email::isSignatureEnabled(),
			'required' => false
		);
	}
	else
	{
		$arResult['FIELDS']['tab_activity_config'][] = array(
			'id' => 'ENABLE_B24_EMAIL_SIGNATURE',
			'name' => GetMessage('CRM_FIELD_ENABLE_B24_EMAIL_SIGNATURE'),
			'type' => 'label',
			'value' =>  Bitrix\Crm\Integration\Bitrix24Email::getSignatureExplanation(),
			'required' => false
		);
	}
}

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'ENABLE_EXPORT_EVENT',
	'name' => GetMessage('CRM_FIELD_ENABLE_EXPORT_EVENT'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isExportEventEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'ENABLE_VIEW_EVENT',
	'name' => GetMessage('CRM_FIELD_ENABLE_VIEW_EVENT'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'VIEW_EVENT_GROUPING_INTERVAL',
	'name' => GetMessage('CRM_FIELD_VIEW_EVENT_GROUPING_INTERVAL'),
	'type' => 'input',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->getViewEventGroupingInterval(),
	'required' => false
);

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'ENABLE_LEAD_DELETION_EVENT',
	'name' => GetMessage('CRM_FIELD_ENABLE_LEAD_DELETION_EVENT2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isLeadDeletionEventEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'ENABLE_CONTACT_DELETION_EVENT',
	'name' => GetMessage('CRM_FIELD_ENABLE_CONTACT_DELETION_EVENT2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isContactDeletionEventEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'ENABLE_COMPANY_DELETION_EVENT',
	'name' => GetMessage('CRM_FIELD_ENABLE_COMPANY_DELETION_EVENT2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isCompanyDeletionEventEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'ENABLE_DEAL_DELETION_EVENT',
	'name' => GetMessage('CRM_FIELD_ENABLE_DEAL_DELETION_EVENT2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isDealDeletionEventEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_history'][] = array(
	'id' => 'ENABLE_QUOTE_DELETION_EVENT',
	'name' => GetMessage('CRM_FIELD_ENABLE_QUOTE_DELETION_EVENT2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isQuoteDeletionEventEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_livefeed'][] = array(
	'id' => 'ENABLE_LIVEFEED_MERGE',
	'name' => GetMessage('CRM_FIELD_ENABLE_LIVEFEED_MERGE2'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\LiveFeedSettings::getCurrent()->isLiveFeedMergeEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'PERSON_NAME_FORMAT_ID',
	'name' => GetMessage('CRM_FIELD_PERSON_NAME_FORMAT2'),
	'type' => 'list',
	'items' => \Bitrix\Crm\Format\PersonNameFormatter::getAllDescriptions(),
	'value' => \Bitrix\Crm\Format\PersonNameFormatter::getFormatID(),
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_FORMAT',
	'name' => GetMessage('CRM_FIELD_CALLTO_FORMAT'),
	'type' => 'list',
	'items' => CCrmCallToUrl::GetAllDescriptions(),
	'value' => CCrmCallToUrl::GetFormat(CCrmCallToUrl::Bitrix),
	'required' => false
);

$calltoSettings = CCrmCallToUrl::GetCustomSettings();
$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_URL_TEMPLATE',
	'name' => GetMessage('CRM_FIELD_CALLTO_URL_TEMPLATE'),
	'type' => 'text',
	'value' => isset($calltoSettings['URL_TEMPLATE']) ? htmlspecialcharsbx($calltoSettings['URL_TEMPLATE']) : 'callto:[phone]',
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_CLICK_HANDLER',
	'name' => GetMessage('CRM_FIELD_CALLTO_CLICK_HANDLER'),
	'type' => 'textarea',
	'value' => isset($calltoSettings['CLICK_HANDLER']) ? htmlspecialcharsbx($calltoSettings['CLICK_HANDLER']) : '',
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_NORMALIZE_NUMBER',
	'name' => GetMessage('CRM_FIELD_CALLTO_NORMALIZE_NUMBER'),
	'type' => 'checkbox',
	'value' => isset($calltoSettings['NORMALIZE_NUMBER']) ? $calltoSettings['NORMALIZE_NUMBER'] === 'Y' : true,
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'ENABLE_SIMPLE_TIME_FORMAT',
	'name' => GetMessage('CRM_FIELD_ENABLE_SIMPLE_TIME_FORMAT'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSimpleTimeFormatEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'section_address_format',
	'name' => GetMessage('CRM_SECTION_ADDRESS_FORMAT'),
	'type' => 'section'
);

$curAddrFormatID = \Bitrix\Crm\Format\EntityAddressFormatter::getFormatID();
$addrFormatDescrs = \Bitrix\Crm\Format\EntityAddressFormatter::getAllDescriptions();
$arResult['ADDR_FORMAT_INFOS'] = \Bitrix\Crm\Format\EntityAddressFormatter::getAllExamples();
$arResult['ADDR_FORMAT_CONTROL_PREFIX'] = 'addr_format_';
$arResult['ADDR_FORMAT_DESCR_ID'] = 'addr_format_descr';

if(\Bitrix\Main\Loader::includeModule('location'))
{
	$arResult['FIELDS']['tab_format'][] = array(
		'id' => 'ENTITY_ADDRESS_FORMAT',
		'name' => GetMessage('CRM_FIELD_ENTITY_ADDRESS_FORMAT2'),
		'type' => 'custom',
		'value' =>
			'<div class="crm-dup-control-type-radio-wrap">'.htmlspecialcharsbx($addrFormatDescrs[$curAddrFormatID]).'</div>'.
			'<div class="crm-dup-control-type-info" id="' . $arResult['ADDR_FORMAT_DESCR_ID'] . '">' . $arResult['ADDR_FORMAT_INFOS'][$curAddrFormatID] . '</div>'.
			'<div class="crm-dup-control-type-info">' . GetMessage('CRM_FIELD_ENTITY_ADDRESS_FORMAT_LINK') . '</div>'.
			'<input type="hidden" name="ENTITY_ADDRESS_FORMAT_ID" value="'.$curAddrFormatID.'">'
	);
}
else
{
	$addrFormatControls = array();
	foreach ($addrFormatDescrs as $addrFormatID => $addrFormatDescr)
	{
		$isChecked = $addrFormatID === $curAddrFormatID;
		$addrFormatControlID = $arResult['ADDR_FORMAT_CONTROL_PREFIX'] . $addrFormatID;
		$addrFormatControls[] = '<input type="radio" class="crm-dup-control-type-radio" id="' . $addrFormatControlID . '" name="ENTITY_ADDRESS_FORMAT_ID" value="' . $addrFormatID . '"' . ($isChecked ? ' checked="checked"' : '') . '/><label class="crm-dup-control-type-label" for="' . $addrFormatControlID . '">' . htmlspecialcharsbx($addrFormatDescr) . '</label>';
	}
	$arResult['FIELDS']['tab_format'][] = array(
		'id' => 'ENTITY_ADDRESS_FORMAT',
		'type' => 'custom',
		'value' =>
			'<div class="crm-dup-control-type-radio-title">' . GetMessage('CRM_FIELD_ENTITY_ADDRESS_FORMAT2') . ':</div>' .
			'<div class="crm-dup-control-type-radio-wrap">' .
			implode('', $addrFormatControls) .
			'</div>',
		'colspan' => true
	);

	$arResult['FIELDS']['tab_format'][] = array(
		'id' => 'ENTITY_ADDRESS_FORMAT_DESCR',
		'type' => 'custom',
		'value' => '<div class="crm-dup-control-type-info" id="' . $arResult['ADDR_FORMAT_DESCR_ID'] . '">' . $arResult['ADDR_FORMAT_INFOS'][$curAddrFormatID] . '</div>',
		'colspan' => true
	);
}

$arResult['FIELDS']['tab_dup_control'][] = array(
	'id' => 'ENABLE_LEAD_DUP_CONTROL',
	'name' => GetMessage('CRM_FIELD_ENABLE_LEAD_DUP_CONTROL'),
	'type' => 'checkbox',
	'value' => $dupControl->isEnabledFor(CCrmOwnerType::Lead),
	'required' => false
);

$arResult['FIELDS']['tab_dup_control'][] = array(
	'id' => 'ENABLE_CONTACT_DUP_CONTROL',
	'name' => GetMessage('CRM_FIELD_ENABLE_CONTACT_DUP_CONTROL'),
	'type' => 'checkbox',
	'value' => $dupControl->isEnabledFor(CCrmOwnerType::Contact),
	'required' => false
);

$arResult['FIELDS']['tab_dup_control'][] = array(
	'id' => 'ENABLE_COMPANY_DUP_CONTROL',
	'name' => GetMessage('CRM_FIELD_ENABLE_COMPANY_DUP_CONTROL'),
	'type' => 'checkbox',
	'value' => $dupControl->isEnabledFor(CCrmOwnerType::Company),
	'required' => false
);

$arResult['FIELDS']['tab_status_config'][] = array(
	'id' => 'ENABLE_DEPRECATED_STATUSES',
	'name' => GetMessage('CRM_FIELD_ENABLE_DEPRECATED_STATUSES'),
	'type' => 'checkbox',
	'value' => CCrmStatus::IsDepricatedTypesEnabled(),
	'required' => false
);


$arResult['FIELDS']['tab_recycle_bin_config'][] = array(
	'id' => 'ENABLE_LEAD_RECYCLE_BIN',
	'name' => GetMessage('CRM_FIELD_ENABLE_LEAD_RECYCLE_BIN'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\LeadSettings::getCurrent()->isRecycleBinEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_recycle_bin_config'][] = array(
	'id' => 'ENABLE_CONTACT_RECYCLE_BIN',
	'name' => GetMessage('CRM_FIELD_ENABLE_CONTACT_RECYCLE_BIN'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\ContactSettings::getCurrent()->isRecycleBinEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_recycle_bin_config'][] = array(
	'id' => 'ENABLE_COMPANY_RECYCLE_BIN',
	'name' => GetMessage('CRM_FIELD_ENABLE_COMPANY_RECYCLE_BIN'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\CompanySettings::getCurrent()->isRecycleBinEnabled(),
	'required' => false
);

$arResult['FIELDS']['tab_recycle_bin_config'][] = array(
	'id' => 'ENABLE_DEAL_RECYCLE_BIN',
	'name' => GetMessage('CRM_FIELD_ENABLE_DEAL_RECYCLE_BIN'),
	'type' => 'checkbox',
	'value' => \Bitrix\Crm\Settings\DealSettings::getCurrent()->isRecycleBinEnabled(),
	'required' => false
);

if (!ModuleManager::isModuleInstalled('bitrix24'))
{
	$arResult['FIELDS']['tab_recycle_bin_config'][] = [
		'id' => 'RECYCLEBIN_TTL',
		'name' => Loc::getMessage('CRM_RECYCLEBIN_TTL_TITLE'),
		'type' => 'list',
		'items' => Settings\RecyclebinSettings::getTtlValues(),
		'value' => Settings\RecyclebinSettings::getCurrent()->getTtl(),
		'required' => false
	];
}

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_SM_LIST'), $arParams['PATH_TO_SM_CONFIG']);
?>

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponent $component
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global CDatabase $DB
 */

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_DETAILS'] = CrmCheckPath('PATH_TO_CONTACT_DETAILS', $arParams['PATH_TO_CONTACT_DETAILS'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&details');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_CONTACT_IMPORT'] = CrmCheckPath('PATH_TO_CONTACT_IMPORT', $arParams['PATH_TO_CONTACT_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_DEDUPE'] = CrmCheckPath('PATH_TO_CONTACT_DEDUPE', $arParams['PATH_TO_CONTACT_DEDUPE'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_PORTRAIT'] = CrmCheckPath('PATH_TO_CONTACT_PORTRAIT', $arParams['PATH_TO_CONTACT_PORTRAIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&portrait');
$arParams['PATH_TO_MIGRATION'] = SITE_DIR."marketplace/category/migration/";
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_contact_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = CCrmContact::CheckReadPermission(0, $CrmPerms);
	$bExport = CCrmContact::CheckExportPermission($CrmPerms);
	$bImport = CCrmContact::CheckImportPermission($CrmPerms);
	$bAdd    = CCrmContact::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmContact::CheckUpdatePermission(0, $CrmPerms);
	$bDelete = false;

	$bDedupe = $bRead && $bWrite && CCrmContact::CheckDeletePermission(0, $CrmPerms);
}
else
{
	$bExport = false;
	$bImport = false;
	$bDedupe = false;

	$bRead   = CCrmContact::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmContact::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmContact::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmContact::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

$isSliderEnabled = \CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::Contact);

if (!$bRead && !$bAdd && !$bWrite)
	return false;

//Skip COPY menu in slider mode
if($arParams['TYPE'] == 'copy' && $isSliderEnabled)
{
	return false;
}

if($arParams['TYPE'] === 'details')
{
	if($arParams['ELEMENT_ID'] <= 0)
	{
		return false;
	}

	$scripts = isset($arParams['~SCRIPTS']) && is_array($arParams['~SCRIPTS']) ? $arParams['~SCRIPTS'] : array();

	//region APPLICATION PLACEMENT
	$placementGroupInfos = \Bitrix\Crm\Integration\Rest\AppPlacementManager::getHandlerInfos(
		\Bitrix\Crm\Integration\Rest\AppPlacement::CONTACT_DETAIL_TOOLBAR
	);
	foreach($placementGroupInfos as $placementGroupName => $placementInfos)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'rest-app-toolbar',
			'NAME' => $placementGroupName,
			'DATA' => array(
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : array(),
				'PLACEMENT' => \Bitrix\Crm\Integration\Rest\AppPlacement::CONTACT_DETAIL_TOOLBAR,
				'APP_INFOS' => $placementInfos
			)
		);
	}
	//endregion

	if (!empty($arParams['BIZPROC_STARTER_DATA']))
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'bizproc-starter-button',
			'DATA' => $arParams['BIZPROC_STARTER_DATA']
		);
	}

	//Force start new bar after first button
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TYPE' => 'crm-communication-panel',
			'DATA' => array(
				'ENABLE_CALL' => \Bitrix\Main\ModuleManager::isModuleInstalled('calendar'),
				'OWNER_INFO' => isset($arParams['OWNER_INFO']) ? $arParams['OWNER_INFO'] : array(),
				'MULTIFIELDS' => isset($arParams['MULTIFIELD_DATA']) ? $arParams['MULTIFIELD_DATA'] : array()
			)
		);
	}

	if($bAdd)
	{
		$copyUrl = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CONTACT_DETAILS'],
				array('contact_id' => $arParams['ELEMENT_ID'])
			),
			array('copy' => 1)
		);
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_COPY'),
			'TITLE' => GetMessage('CRM_CONTACT_COPY_TITLE'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($copyUrl)."')",
			'ICON' => 'btn-copy'
		);
	}

	if($bDelete && isset($scripts['DELETE']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_DELETE'),
			'TITLE' => GetMessage('CRM_CONTACT_DELETE_TITLE'),
			'ONCLICK' => $scripts['DELETE'],
			'ICON' => 'btn-delete'
		);
	}

	if(\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isDocumentButtonAvailable())
	{
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('DOCUMENT_BUTTON_TEXT'),
			'TITLE' => GetMessage('DOCUMENT_BUTTON_TITLE'),
			'TYPE' => 'crm-document-button',
			'PARAMS' => \Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->getDocumentButtonParameters(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Contact::class, $arParams['ELEMENT_ID']),
		];
	}

	$this->IncludeComponentTemplate();
	return;
}

if($arParams['TYPE'] === 'list')
{
	if($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_ADD'),
			'TITLE' => GetMessage('CRM_CONTACT_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams[$isSliderEnabled ? 'PATH_TO_CONTACT_DETAILS' : 'PATH_TO_CONTACT_EDIT'],
				array('contact_id' => 0)
			),
			'HIGHLIGHT' => true
		);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_VCARD'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_VCARD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_IMPORTVCARD'], array()),
			'ICON' => 'btn-import'
		);

		$importUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_IMPORT'], array());

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_GMAIL'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_GMAIL_TITLE'),
			'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'gmail')),
			'ICON' => 'btn-import'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_OUTLOOK'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_OUTLOOK_TITLE'),
			'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'outlook')),
			'ICON' => 'btn-import'
		);

		if(LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'ua')
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('CRM_CONTACT_IMPORT_YANDEX'),
				'TITLE' => GetMessage('CRM_CONTACT_IMPORT_YANDEX_TITLE'),
				'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'yandex')),
				'ICON' => 'btn-import'
			);
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_YAHOO'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_YAHOO_TITLE'),
			'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'yahoo')),
			'ICON' => 'btn-import'
		);

		if(LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'ua')
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('CRM_CONTACT_IMPORT_MAILRU'),
				'TITLE' => GetMessage('CRM_CONTACT_IMPORT_MAILRU_TITLE'),
				'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'mailru')),
				'ICON' => 'btn-import'
			);
		}

		/*
		* LIVEMAIL is temporary disabled due to implementation error
		* $arResult['BUTTONS'][] = array(
		*  'TEXT' => GetMessage('CRM_CONTACT_IMPORT_LIVEMAIL'),
		*  'TITLE' => GetMessage('CRM_CONTACT_IMPORT_LIVEMAIL_TITLE'),
		*  'LINK' => CCrmUrlUtil::AddUrlParams($importUrl, array('origin' => 'livemail')),
		*  'ICON' => 'btn-import'
		);*/
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT_CUSTOM'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_CUSTOM_TITLE'),
			'LINK' => $importUrl,
			'ICON' => 'btn-import'
		);

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_MIGRATION'),
			'TITLE' => GetMessage('CRM_CONTACT_MIGRATION_TITLE'),
			'LINK' => $arParams['PATH_TO_MIGRATION'],
			'ICON' => 'btn-migration'
		);
	}

	if ($bExport)
	{
		if($bImport)
		{
			$arResult['BUTTONS'][] = array('SEPARATOR' => true);
		}

		$entityType = CCrmOwnerType::ContactName;
		$stExportId = 'STEXPORT_'.$entityType.'_MANAGER';
		$randomSequence = new Bitrix\Main\Type\RandomSequence($stExportId);
		$stExportManagerId = $stExportId.'_'.$randomSequence->randString();
		$componentName = 'bitrix:crm.contact.list';

		$componentParams = array(
			'CONTACT_COUNT' => '20',
			'PATH_TO_CONTACT_LIST' => $arParams['PATH_TO_CONTACT_LIST'],
			'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
			'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
			'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'NAVIGATION_CONTEXT_ID' => $entityType
		);
		if (isset($_REQUEST['WG']) && strtoupper($_REQUEST['WG']) === 'Y')
		{
			$widgetDataFilter = \Bitrix\Crm\Widget\Data\Contact\DataSource::extractDetailsPageUrlParams($_REQUEST);
			if (!empty($widgetDataFilter))
			{
				$componentParams['WIDGET_DATA_FILTER'] = $widgetDataFilter;
			}
		}

		$arResult['STEXPORT_PARAMS'] = array(
			'componentName' => $componentName,
			'siteId' => SITE_ID,
			'entityType' => $entityType,
			'stExportId' => $stExportId,
			'managerId' => $stExportManagerId,
			'sToken' => 's'.time(),
			'initialOptions' => array(
				'REQUISITE_MULTILINE' => array(
					'name' => 'REQUISITE_MULTILINE',
					'type' => 'checkbox',
					'title' => GetMessage('CRM_CONTACT_STEXPORT_OPTION_REQUISITE_MULTILINE'),
					'value' => 'N'
				),
				'EXPORT_ALL_FIELDS' => array(
					'name' => 'EXPORT_ALL_FIELDS',
					'type' => 'checkbox',
					'title' => GetMessage('CRM_CONTACT_STEXPORT_OPTION_EXPORT_ALL_FIELDS'),
					'value' => 'N'
				),
			),
			'componentParams' => \Bitrix\Main\Component\ParameterSigner::signParameters($componentName, $componentParams),
			'messages' => array(
				'stExportExcelDlgTitle' => GetMessage('CRM_CONTACT_EXPORT_EXCEL_TITLE'),
				'stExportExcelDlgSummary' => GetMessage('CRM_CONTACT_STEXPORT_SUMMARY'),
				'stExportCsvDlgTitle' => GetMessage('CRM_CONTACT_EXPORT_CSV_TITLE'),
				'stExportCsvDlgSummary' => GetMessage('CRM_CONTACT_STEXPORT_SUMMARY')
			)
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('CRM_CONTACT_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('CRM_CONTACT_EXPORT_CSV'),
			'ONCLICK' => "BX.Crm.ExportManager.items['".CUtil::JSEscape($stExportManagerId)."'].startExport('csv')",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('CRM_CONTACT_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('CRM_CONTACT_EXPORT_EXCEL'),
			'ONCLICK' => "BX.Crm.ExportManager.items['".CUtil::JSEscape($stExportManagerId)."'].startExport('excel')",
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array('SEPARATOR' => true);


		unset($entityType, $stExportId, $randomSequence, $stExportManagerId);

		if (CModule::IncludeModule('webservice') && class_exists("\\Bitrix\\WebService\\StsSync"))
		{
			$rsSites = CSite::GetByID(SITE_ID);
			$arSite = $rsSites->Fetch();
			if (strlen($arSite['SITE_NAME']) > 0)
				$sPrefix = $arSite['SITE_NAME'];
			else
				$sPrefix = COption::GetOptionString('main', 'site_name', GetMessage('CRM_OUTLOOK_PREFIX_CONTACTS'));

			$GUID = CCrmContactWS::makeGUID(md5($_SERVER['SERVER_NAME'].'|'.'contacts_crm'));
			$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EXPORT_OUTLOOK_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EXPORT_OUTLOOK'),
				'ONCLICK' => \Bitrix\WebService\StsSync::getUrl('contacts', 'contacts_crm', $APPLICATION->GetCurPage(), $sPrefix, GetMessage('CRM_OUTLOOK_TITLE_CONTACTS'), $GUID),
				'ICON' => 'btn-export'
			);
		}
	}

	if ($bDedupe)
	{
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getDuplicateControlRestriction();
		if($restriction->hasPermission())
		{
			$dedupePath = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isDedupeWizardEnabled()
				? $arParams['PATH_TO_CONTACT_DEDUPEWIZARD']
				: $arParams['PATH_TO_CONTACT_DEDUPE'];

			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('CONTACT_DEDUPE'),
				'TITLE' => GetMessage('CONTACT_DEDUPE_TITLE'),
				'LINK' => CComponentEngine::MakePathFromTemplate($dedupePath, array())
			);
		}
		else
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('CONTACT_DEDUPE'),
				'TITLE' => GetMessage('CONTACT_DEDUPE_TITLE'),
				'ONCLICK' => $restriction->preparePopupScript(),
				'MENU_ICON' => 'grid-lock'
			);
		}
	}

	if(\Bitrix\Main\Loader::includeModule('rest') && is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl'))
	{
		$url = \Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl('crm_contact', 'setting_list');
		$arResult['BUTTONS'][] = [
			'TEXT' => GetMessage('CRM_CONTACT_VERTICAL_CRM'),
			'TITLE' => GetMessage('CRM_CONTACT_VERTICAL_CRM_TITLE'),
			'ONCLICK' => 'BX.SidePanel.Instance.open(\''.$url.'\');'
		];
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'portrait')
	&& !empty($arParams['ELEMENT_ID'])
	&& $bWrite
)
{
	$plannerButton = \Bitrix\Crm\Activity\Planner::getToolbarButton($arParams['ELEMENT_ID'], CCrmOwnerType::Contact);
	if($plannerButton)
	{
		CJSCore::Init(array('crm_activity_planner'));
		$arResult['BUTTONS'][] = $plannerButton;
	}
}

if (($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_PORTRAIT'),
		'TITLE' => GetMessage('CRM_CONTACT_PORTRAIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_PORTRAIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-portrait'
	);

	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Contact,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'contact_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#bitrix/components/bitrix/crm.contact.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_CONTACT_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_CONTACT_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_CONTACT_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_CONTACT_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if (($arParams['TYPE'] == 'show') && $bWrite && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_EDIT'),
		'TITLE' => GetMessage('CRM_CONTACT_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'portrait') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_SHOW'),
		'TITLE' => GetMessage('CRM_CONTACT_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_COPY'),
		'TITLE' => GetMessage('CRM_CONTACT_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if ($bAdd && $arParams['TYPE'] != 'list' && $arParams['TYPE'] !== 'portrait')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_ADD'),
		'TITLE' => GetMessage('CRM_CONTACT_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams[$isSliderEnabled ? 'PATH_TO_CONTACT_DETAILS' : 'PATH_TO_CONTACT_EDIT'],
			array('contact_id' => 0)
		),
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

if ($arParams['TYPE'] == 'show' && CCrmDeal::CheckCreatePermission($CrmPerms))
{
	$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arParams['ELEMENT_ID'],  'CHECK_PERMISSIONS' => 'N'), false, false, array('ID', 'COMPANY_ID'));
	$arFields = $dbRes->Fetch();

	$arResult['BUTTONS'][]= array(
		'TEXT' => GetMessage('CRM_CONTACT_DEAL_ADD'),
		'TITLE' => GetMessage('CRM_CONTACT_DEAL_ADD_TITLE'),
		'LINK' => CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'], array('deal_id' => 0)),
			array('contact_id' =>$arParams['ELEMENT_ID'], 'company_id' => $arFields['COMPANY_ID'])
		),
		'ICONCLASS' => 'btn-add-deal'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_DELETE'),
		'TITLE' => GetMessage('CRM_CONTACT_DELETE_TITLE'),
		'LINK' => "javascript:contact_delete('".GetMessage('CRM_CONTACT_DELETE_DLG_TITLE')."', '".GetMessage('CRM_CONTACT_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_CONTACT_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();

?>

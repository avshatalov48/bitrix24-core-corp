<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

use Bitrix\Crm\Activity\ToDo\CalendarSettings\CalendarSettingsProvider;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Crm\UI\SettingsButtonExtender\SettingsButtonExtenderParams;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if (SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\UI\Extension::load(
	[
		'crm.merger.batchmergemanager',
		'ui.icons.b24',
		'ui.fonts.opensans',
		'crm.autorun',
		'crm.entity-list.panel',
		'crm.badge',
		'ui.design-tokens',
		'crm.template.editor'
	]
);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

?><div id="crm-company-list-progress-bar-container"></div><div id="batchDeletionWrapper"></div><?

echo (\Bitrix\Crm\Tour\NumberOfClients::getInstance())->build();

echo \Bitrix\Crm\Tour\GridGroupWhatsAppMessage::getInstance()->build();
if($arResult['NEED_TO_CONVERT_ADDRESSES']):
	?><div id="convertCompanyAddressesWrapper"></div><?
endif;
if($arResult['NEED_TO_CONVERT_UF_ADDRESSES']):
	?><div id="convertCompanyUfAddressesWrapper"></div><?
endif;
if($arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS']):
	?><div id="backgroundCompanyIndexRebuildWrapper"></div><?
endif;
if($arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS']):
	?><div id="backgroundCompanyMergeWrapper"></div><?
endif;
if($arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE']):
	?><div id="backgroundCompanyDupVolDataPrepareWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):
	?><div id="rebuildCompanyDupIndexMsg" class="crm-view-message">
		<?=Loc::getMessage('CRM_COMPANY_REBUILD_DUP_INDEX')?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):
	?><div id="rebuildCompanySearchWrapper"></div><?
endif;

if($arResult['NEED_FOR_BUILD_TIMELINE']):
	?><div id="buildCompanyTimelineWrapper"></div><?
endif;

if($arResult['NEED_FOR_BUILD_DUPLICATE_INDEX']):
	?><div id="buildCompanyDuplicateIndexWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']):
	?><div id="rebuildCompanySecurityAttrsWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_COMPANY_ATTRS']):
	?><div id="rebuildCompanyAttrsMsg" class="crm-view-message">
		<?=Loc::getMessage('CRM_COMPANY_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildCompanyAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;

if($arResult['NEED_FOR_TRANSFER_REQUISITES']):
	?><div id="transferRequisitesMsg" class="crm-view-message">
		<?=Bitrix\Crm\Requisite\EntityRequisiteConverter::getIntroMessage(
			array(
				'EXEC_ID' => 'transferRequisitesLink', 'EXEC_URL' => '#',
				'SKIP_ID' => 'skipTransferRequisitesLink', 'SKIP_URL' => '#'
			)
		)?>
	</div><?
endif;

$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'] ?? false;
$allowWrite = $arResult['PERMS']['WRITE'] && !$callListUpdateMode;
$allowDelete = $arResult['PERMS']['DELETE'] && !$callListUpdateMode;
$currentUserID = $arResult['CURRENT_USER_ID'];
$isMyCompanyMode = (isset($arResult['MYCOMPANY_MODE']) && $arResult['MYCOMPANY_MODE'] === 'Y');
$activityEditorID = '';
if(!$isInternal):
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'COMPANY',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;
$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$preparedGridId = htmlspecialcharsbx(CUtil::JSescape($gridManagerID));
$gridManagerCfg = array(
	'ownerType' => 'COMPANY',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => []
);
$prefix = $arResult['GRID_ID'];

$arResult['GRID_DATA'] = [];
$arColumns = [];
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

$now = time() + CTimeZone::GetOffset();

$fieldContentTypeMap = \Bitrix\Crm\Model\FieldContentTypeTable::loadForMultipleItems(
	\CCrmOwnerType::Company,
	array_keys($arResult['COMPANY']),
);

if ($arResult['NEED_ADD_ACTIVITY_BLOCK'] ?? false)
{
	$arResult['COMPANY'] = (new \Bitrix\Crm\Component\EntityList\NearestActivity\Manager(CCrmOwnerType::Company))->appendNearestActivityBlock($arResult['COMPANY']);
}

$toolsManager = Container::getInstance()->getIntranetToolsManager();
$availabilityManager = AvailabilityManager::getInstance();
$isQuoteAvailable = $toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Quote);
$isInvoiceAvailable = $toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Invoice);

foreach($arResult['COMPANY'] as $sKey =>  $arCompany)
{
	$arEntitySubMenuItems = [];
	$arActivityMenuItems = [];
	$arActivitySubMenuItems = [];
	$arActions = [];

	$arActions[] = array(
		'TITLE' => Loc::getMessage('CRM_COMPANY_SHOW_TEXT'),
		'TEXT' => Loc::getMessage('CRM_COMPANY_SHOW_TEXT'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_SHOW'])."')",
		'DEFAULT' => true
	);
	if($arCompany['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => Loc::getMessage('CRM_COMPANY_EDIT_TEXT'),
			'TEXT' => Loc::getMessage('CRM_COMPANY_EDIT_TEXT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_EDIT'])."')"
		);
		$arActions[] = array(
			'TITLE' => Loc::getMessage('CRM_COMPANY_COPY_TEXT'),
			'TEXT' => Loc::getMessage('CRM_COMPANY_COPY_TEXT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_COPY'])."')",
		);
	}

	if(!$isInternal && $arCompany['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arCompany['PATH_TO_COMPANY_DELETE']);
		$arActions[] = array(
			'TITLE' => Loc::getMessage('CRM_COMPANY_DELETE_TEXT'),
			'TEXT' => Loc::getMessage('CRM_COMPANY_DELETE_TEXT'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	if($isMyCompanyMode)
	{
		if(!(isset($arCompany['IS_DEF_MYCOMPANY']) && $arCompany['IS_DEF_MYCOMPANY'] === 'Y'))
		{
			$arActions[] = array('SEPARATOR' => true);
			$companySetDefMyCompany = CHTTP::urlAddParams(
				$arParams['PATH_TO_COMPANY_LIST'],
				array('action_'.$arResult['GRID_ID'] => 'set_def_mycompany', 'ID' => $sKey, 'sessid' => bitrix_sessid())
			);
			$arActions[] = array(
				'TEXT' => Loc::getMessage('CRM_COMPANY_LIST_ACTION_MENU_SET_DEF_MYCOMPANY'),
				'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($companySetDefMyCompany).'\');'
			);
		}
	}

	if(!$isInternal && !$isMyCompanyMode)
	{
		$arActions[] = array('SEPARATOR' => true);

		if($arResult['PERM_CONTACT'] && !$arResult['CATEGORY_ID'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => Loc::getMessage('CRM_COMPANY_CONTACT_ADD_TITLE'),
				'TEXT' => Loc::getMessage('CRM_COMPANY_CONTACT_ADD_SHORT'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_CONTACT_EDIT'])."')"
			);
		}
		if($arResult['PERM_DEAL'] && !$arResult['CATEGORY_ID'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => Loc::getMessage('CRM_COMPANY_DEAL_ADD_TITLE'),
				'TEXT' => Loc::getMessage('CRM_COMPANY_DEAL_ADD_SHORT'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_DEAL_EDIT'])."')"
			);
		}
		if($arResult['PERM_QUOTE'] && !$arResult['CATEGORY_ID'])
		{
			$analyticsEventBuilderForQuote = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault(\CCrmOwnerType::Quote)
				->setSection(
					!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
					? $arParams['ANALYTICS']['c_section']
					: null
				)
				->setSubSection(
					!empty($arParams['ANALYTICS']['c_sub_section']) && is_string($arParams['ANALYTICS']['c_sub_section'])
					? $arParams['ANALYTICS']['c_sub_section']
					: null
				)
				->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU)
			;
			if ($isQuoteAvailable)
			{
				$onClick = "jsUtils.Redirect([], '" . CUtil::JSEscape(
					$analyticsEventBuilderForQuote->buildUri($arCompany['PATH_TO_QUOTE_ADD'])->getUri()
				) . "');";
			}
			else
			{
				$onClick = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::Quote);
			}

			$quoteAction = [
				'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_QUOTE_TITLE_MSGVER_1'),
				'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_QUOTE_SHORT_MSGVER_1'),
				'ONCLICK' => $onClick,
			];
			if ($isQuoteAvailable && \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->isFactoryEnabled())
			{
				$link = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
					\CCrmOwnerType::Quote,
					0,
					null
				);
				$link->addParams([
					'company_id' => $arCompany['ID'],
				]);
				unset($quoteAction['ONCLICK']);
				$quoteAction['HREF'] = $analyticsEventBuilderForQuote->buildUri($link)->getUri();
			}
			$arEntitySubMenuItems[] = $quoteAction;
		}
		if (
			$arResult['PERM_INVOICE']
			&& IsModuleInstalled('sale')
			&& \Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled()
			&& !$arResult['CATEGORY_ID']
		)
		{
			$analyticsEventBuilderForInvoice = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault(\CCrmOwnerType::Invoice)
				->setSection(
					!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
					? $arParams['ANALYTICS']['c_section']
					: null
				)
				->setSubSection(
					!empty($arParams['ANALYTICS']['c_sub_section']) && is_string($arParams['ANALYTICS']['c_sub_section'])
					? $arParams['ANALYTICS']['c_sub_section']
					: null
				)
				->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU)
			;
			if ($isInvoiceAvailable)
			{
				$onClick = "jsUtils.Redirect([], '" . CUtil::JSEscape(
					$analyticsEventBuilderForInvoice->buildUri($arCompany['PATH_TO_INVOICE_ADD'])->getUri()
				) . "');";
			}
			else
			{
				$onClick = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::Invoice);
			}

			$localization = \Bitrix\Crm\Service\Container::getInstance()->getLocalization();
			$arEntitySubMenuItems[] = array(
				'TITLE' => $localization->appendOldVersionSuffix(Loc::getMessage('CRM_DEAL_ADD_INVOICE_TITLE')),
				'TEXT' => $localization->appendOldVersionSuffix(Loc::getMessage('CRM_DEAL_ADD_INVOICE')),
				'ONCLICK' => $onClick,
			);
		}

		if (
			\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkAddPermissions(\CCrmOwnerType::SmartInvoice)
			&& !$arResult['CATEGORY_ID']
		)
		{
			$analyticsEventBuilderForSmartInvoice = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault(\CCrmOwnerType::SmartInvoice)
				->setSection(
					!empty($arParams['ANALYTICS']['c_section']) && is_string($arParams['ANALYTICS']['c_section'])
						? $arParams['ANALYTICS']['c_section']
						: null
				)
				->setSubSection(
					!empty($arParams['ANALYTICS']['c_sub_section']) && is_string($arParams['ANALYTICS']['c_sub_section'])
						? $arParams['ANALYTICS']['c_sub_section']
						: null
				)
				->setElement(\Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU)
			;
			$subMenuItem = [
				'TITLE' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
				'TEXT' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
			];

			if ($isInvoiceAvailable)
			{
				$href = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
					\CCrmOwnerType::SmartInvoice,
					0,
					null,
					new \Bitrix\Crm\ItemIdentifier(
						\CCrmOwnerType::Company,
						$arCompany['ID']
					)
				);
				$subMenuItem['HREF'] = $analyticsEventBuilderForSmartInvoice->buildUri($href)->getUri();
			}
			else
			{
				$subMenuItem['ONCLICK'] = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::SmartInvoice);
			}

			$arEntitySubMenuItems[] = $subMenuItem;
		}

		if (!empty($arEntitySubMenuItems))
		{
			$arActions[] = array(
				'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_ENTITY_TITLE_MSGVER_1'),
				'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_ENTITY'),
				'MENU' => $arEntitySubMenuItems
			);
		}

		$arActions[] = array('SEPARATOR' => true);

		if ($arCompany['EDIT'])
		{
			$currentUser = CUtil::PhpToJSObject(CCrmViewHelper::getUserInfo(true, false));

			$pingSettings = (new TodoPingSettingsProvider(
				\CCrmOwnerType::Company,
				(int)($arResult['CATEGORY_ID'] ?? 0)
			))->fetchForJsComponent();
			$calendarSettings = (new CalendarSettingsProvider())->fetchForJsComponent();
			$colorSettings = (new ColorSettingsProvider())->fetchForJsComponent();

			$settings = CUtil::PhpToJSObject([
				'pingSettings' => $pingSettings,
				'calendarSettings' => $calendarSettings,
				'colorSettings' => $colorSettings,
			]);

			$analytics = CUtil::PhpToJSObject($arParams['ANALYTICS'] ?? []);

			$arActivitySubMenuItems[] = [
				'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_TODO'),
				'ONCLICK' => "BX.CrmUIGridExtension.showActivityAddingPopupFromMenu('".$preparedGridId."', " . CCrmOwnerType::Company . ", " . (int)$arCompany['ID'] . ", " . $currentUser . ", " . $settings . ", " . $analytics .");"
			];

			if (IsModuleInstalled('subscribe'))
			{
				$arActions[] = $arActivityMenuItems[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_EMAIL_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_EMAIL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.email, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);
			}

			if (IsModuleInstalled(CRM_MODULE_CALENDAR_ID) && \Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled())
			{
				$arActivityMenuItems[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_CALL_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_MEETING_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_CALL_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_MEETING_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);
			}

			if (IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_TASK_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_TASK_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);
			}

			if (!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => Loc::getMessage('CRM_COMPANY_ADD_ACTIVITY_TITLE'),
					'TEXT' => Loc::getMessage('CRM_COMPANY_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}
		}
	}

	if ($arCompany['EDIT'])
	{
		if ($arResult['IS_BIZPROC_AVAILABLE'])
		{
			\Bitrix\Main\UI\Extension::load(['bp_starter']);

			//$arActions[] = array('SEPARATOR' => true);
			$arActions[] = [
				'TITLE' => Loc::getMessage('CRM_COMPANY_BIZPROC_LIST_TITLE'),
				'TEXT' => Loc::getMessage('CRM_COMPANY_BIZPROC_LIST'),
				'ONCLICK' => (
					$toolsManager->checkBizprocAvailability()
						? CCrmBizProcHelper::getShowTemplatesJsAction(
							CCrmOwnerType::Company,
							$arCompany['ID'],
							'function(){BX.Main.gridManager.reload(\'' . CUtil::JSEscape($arResult['GRID_ID']) . '\');}'
						)
						: $availabilityManager->getBizprocAvailabilityLock()
				),
			];
		}
	}

	$eventParam = array(
		'ID' => $arCompany['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);

	foreach(GetModuleEvents('crm', 'onCrmCompanyListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_COMPANY_LIST_MENU', $eventParam, &$arActions));
	}

	$dateCreate = $arCompany['DATE_CREATE'] ?? '';
	$dateModify = $arCompany['DATE_MODIFY'] ?? '';
	$companyType = null;
	if (isset($arCompany['COMPANY_TYPE']))
	{
		$companyType = $arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']] ?? $arCompany['COMPANY_TYPE'];
	}
	$webformId = null;
	if (isset($arCompany['WEBFORM_ID']))
	{
		$webformId = $arResult['WEBFORM_LIST'][$arCompany['WEBFORM_ID']] ?? $arCompany['WEBFORM_ID'];
	}
	$industry = null;
	if (isset($arCompany['INDUSTRY']))
	{
		$industry = $arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']] ?? $arCompany['INDUSTRY'];
	}
	$employees = null;
	if (isset($arCompany['EMPLOYEES']))
	{
		$employees = $arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']] ?? $arCompany['EMPLOYEES'];
	}

	$resultItem = array(
		'id' => $arCompany['ID'],
		'actions' => $arActions,
		'data' => $arCompany,
		'editable' => !$arCompany['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'COMPANY_SUMMARY' => (new \Bitrix\Crm\Service\Display\ClientSummary(\CCrmOwnerType::Company, (int)$arCompany['ID']))
				->withUrl((string)$arCompany['PATH_TO_COMPANY_SHOW'])
				->withTitle((string)$arCompany['TITLE'])
				->withDescription((string)$arCompany['COMPANY_TYPE_NAME'])
				->withTracking(true)
				->withPhoto((int)$arCompany['~LOGO'])
				->render()
			,
			'ASSIGNED_BY' => $arCompany['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					[
						'PREFIX' => "COMPANY_{$arCompany['~ID']}_RESPONSIBLE",
						'USER_ID' => $arCompany['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arCompany['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arCompany['PATH_TO_USER_PROFILE']
					]
				) : '',
			'REVENUE' =>  '<nobr>'.number_format($arCompany['REVENUE'] ?? 0, 2, ',', ' ').'</nobr>',
			'COMMENTS' => htmlspecialcharsback($arCompany['COMMENTS'] ?? ''),
			'BANKING_DETAILS' => nl2br($arCompany['BANKING_DETAILS'] ?? ''),
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateCreate), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateModify), $now),
			'COMPANY_TYPE' => $companyType,
			'CURRENCY_ID' =>  CCrmCurrency::GetEncodedCurrencyName($arCompany['CURRENCY_ID'] ?? null),
			'WEBFORM_ID' => $webformId,
			'INDUSTRY' => $industry,
			'EMPLOYEES' => $employees,
			'CREATED_BY' => isset($arCompany['~CREATED_BY']) && $arCompany['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml([
					'PREFIX' => "COMPANY_{$arCompany['~ID']}_CREATOR",
					'USER_ID' => $arCompany['~CREATED_BY'],
					'USER_NAME'=> $arCompany['CREATED_BY_FORMATTED_NAME'],
					'USER_PROFILE_URL' => $arCompany['PATH_TO_USER_CREATOR']
				])
				: '',
			'MODIFY_BY' => isset($arCompany['~MODIFY_BY']) && $arCompany['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "COMPANY_{$arCompany['~ID']}_MODIFIER",
						'USER_ID' => $arCompany['~MODIFY_BY'],
						'USER_NAME'=> $arCompany['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arCompany['PATH_TO_USER_MODIFIER']
					)
				) : '',
			'OBSERVERS' => CCrmViewHelper::renderObservers(\CCrmOwnerType::Company, $arCompany['ID'], $arCompany['~OBSERVERS'] ?? []),
		) + CCrmViewHelper::RenderListMultiFields($arCompany, "COMPANY_{$arCompany['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::CompanyName, 'ENTITY_ID' => $arCompany['ID']))) + $arResult['COMPANY_UF'][$sKey]
	);

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Company,
		$arCompany['ID'],
		$resultItem['columns']
	);

	$resultItem['columns'] = \Bitrix\Crm\Entity\CommentsHelper::enrichGridRow(
		\CCrmOwnerType::Company,
		$fieldContentTypeMap[$arCompany['ID']] ?? [],
		$arCompany,
		$resultItem['columns'],
	);

	if ($arResult['ENABLE_OUTMODED_FIELDS'])
	{
		$resultItem['columns']['ADDRESS'] = nl2br($arCompany['ADDRESS']);
		$resultItem['columns']['ADDRESS_LEGAL'] = nl2br($arCompany['ADDRESS_LEGAL']);
	}

	if (isset($arCompany['ACTIVITY_BLOCK']) && $arCompany['ACTIVITY_BLOCK'] instanceof \Bitrix\Crm\Component\EntityList\NearestActivity\Block)
	{
		$resultItem['columns']['ACTIVITY_ID'] = $arCompany['ACTIVITY_BLOCK']->render($gridManagerID);
		if ($arCompany['ACTIVITY_BLOCK']->needHighlight())
		{
			$resultItem['columnClasses'] = ['ACTIVITY_ID' => 'crm-list-deal-today'];
		}
	}

	if (isset($arCompany['badges']) && is_array($arCompany['badges']))
	{
		$resultItem['columns']['COMPANY_SUMMARY'] .= Bitrix\Crm\Component\EntityList\BadgeBuilder::render($arCompany['badges']);
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}

if ($arResult['ENABLE_TOOLBAR'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		[
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => [
				[
					'TEXT' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Company),
					'LINK' => $arResult['PATH_TO_COMPANY_ADD'],
					'ICON' => 'btn-new',
				]
			]
		],
		$component,
		['HIDE_ICONS' => 'Y'],
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.newentity.counter.panel',
	'',
	[
		'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
		'GRID_ID' => $arResult['GRID_ID'],
		'CATEGORY_ID' => $arResult['CATEGORY_ID'],
	],
	$component
);

$filterLazyLoadUrl = '/bitrix/components/bitrix/crm.company.list/filter.ajax.php?' . bitrix_sessid_get();
$filterLazyLoadParams = [
	'filter_id' => urlencode($arResult['GRID_ID']),
	'category_id' => $arResult['CATEGORY_ID'] ?? null,
	'siteID' => SITE_ID,
];
$uri = new Uri($filterLazyLoadUrl);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
		'ENABLE_FIELDS_SEARCH' => 'Y',
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'HIDE_FILTER' => $arParams['HIDE_FILTER'] ?? null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => [
			'LAZY_LOAD' => [
				'GET_LIST' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'list']))->getUri(),
				'GET_FIELD' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'field']))->getUri(),
				'GET_FIELDS' => $uri->addParams(array_merge($filterLazyLoadParams, ['action' => 'fields']))->getUri(),
			],
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
			'CONFIG' => [
				'AUTOFOCUS' => false,
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
				$arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? \Bitrix\Main\ModuleManager::isModuleInstalled('ui')
			),
			'RESTRICTED_FIELDS' => $arResult['RESTRICTED_FIELDS'] ?? [],
		],
		'LIVE_SEARCH_LIMIT_INFO' => $arResult['LIVE_SEARCH_LIMIT_INFO'] ?? null,
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => [
			'GROUPS' => [
				[
					'ITEMS' => $isInternal ? [] : $arResult['PANEL']?->getControls(),
				],
			],
		],
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION']) ? $arResult['PAGINATION'] : [],
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAVIGATION_BAR' => $isMyCompanyMode
			? null
			: (new NavigationBarPanel(CCrmOwnerType::Company))->setBinding($arResult['NAVIGATION_CONTEXT_ID'])->get(),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => [
			'ID' => $gridManagerID,
			'CONFIG' => [
				'ownerTypeName' => CCrmOwnerType::CompanyName,
				'categoryId' => (int)($arResult['CATEGORY_ID'] ?? 0),
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> $arResult['TASK_CREATE_URL'] ?? '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.company.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => $arParams['AJAX_LOADER'] ?? null,
			],
			'MESSAGES' => [
				'deletionDialogTitle' => Loc::getMessage('CRM_COMPANY_DELETE_TITLE'),
				'deletionDialogMessage' => Loc::getMessage('CRM_COMPANY_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => Loc::getMessage('CRM_COMPANY_DELETE')
			]
		]
	],
	$component
);

?><script>
	BX.ready(
		function()
		{
			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
				"/bitrix/components/bitrix/crm.company.show/ajax.php?<?=bitrix_sessid_get()?>"
			);

			if(typeof(BX.CrmSipManager.messages) === 'undefined')
			{
				BX.CrmSipManager.messages =
				{
					"unknownRecipient": "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
					"makeCall": "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
				};
			}
		}
	);
</script>
<?php

if (
	!$isInternal
	&& \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME') !== 'Y'
)
{
	$settingsButtonExtenderParams = SettingsButtonExtenderParams::createDefaultForGrid(
		\CCrmOwnerType::Company,
		$arResult['GRID_ID'],
	);

	$settingsButtonExtenderParams
		->setCategoryId(isset($arResult['CATEGORY_ID']) ? (int)$arResult['CATEGORY_ID'] : null)
	;

	echo <<<HTML
<script>
	BX.ready(() => {
		{$settingsButtonExtenderParams->buildJsInitCode()}
	});
</script>
HTML;
}

if(!$isInternal): ?>
<script>
	BX.ready(
		function()
		{
			BX.CrmActivityEditor.items['<?= CUtil::JSEscape($activityEditorID)?>'].addActivityChangeHandler(
					function()
					{
						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					}
			);
			BX.namespace('BX.Crm.Activity');
			if(typeof BX.Crm.Activity.Planner !== 'undefined')
			{
				BX.Crm.Activity.Planner.Manager.setCallback('onAfterActivitySave', function()
				{
					BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
				});
			}
		}
	);
</script>
<?endif;?>

<script>
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_REQUEST_ERR')?>"
			};

			BX.Crm.EntityList.Panel.init(<?= \CUtil::PhpToJSObject([
				'gridId' => $arResult['GRID_ID'],
				'progressBarContainerId' => 'crm-company-list-progress-bar-container',
			]) ?>);
		}
	);
</script>

<?if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):?>
<script>
	BX.ready(
		function()
		{
			BX.CrmDuplicateManager.messages =
			{
				rebuildCompanyIndexDlgTitle: "<?=GetMessageJS('CRM_COMPANY_REBUILD_DUP_INDEX_DLG_TITLE')?>",
				rebuildCompanyIndexDlgSummary: "<?=GetMessageJS('CRM_COMPANY_REBUILD_DUP_INDEX_DLG_SUMMARY')?>"
			};

			var mgr = BX.CrmDuplicateManager.create("mgr", { entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>", serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.company.list/list.ajax.php?&<?=bitrix_sessid_get()?>" });
			BX.addCustomEvent(
				mgr,
				'ON_COMPANY_INDEX_REBUILD_COMPLETE',
				function()
				{
					var msg = BX("rebuildCompanyDupIndexMsg");
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var link = BX("rebuildCompanyDupIndexLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						mgr.rebuildIndex();
						return BX.PreventDefault(e);
					}
				);
			}
		}
	);
</script>
<?endif;?>

<?if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
	<script>
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildCompanySearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_COMPANY_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildCompanySearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildCompanySearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']):?>
	<script>
		BX.ready(
			function()
			{
				BX.AutorunProcessManager.createIfNotExists(
					"rebuildCompanySecurityAttrs",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SECURITY_ATTRS",
						container: "rebuildCompanySecurityAttrsWrapper",
						title: "<?=GetMessageJS('CRM_COMPANY_REBUILD_SECURITY_ATTRS_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_COMPANY_STEPWISE_STATE_TEMPLATE')?>",
						enableLayout: true
					}
				).runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if($arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script>
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("buildCompanyTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_COMPANY_BUILD_TIMELINE_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_COMPANY_BUILD_TIMELINE_STATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("buildCompanyTimeline",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "BUILD_TIMELINE",
						container: "buildCompanyTimelineWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if($arResult['NEED_FOR_BUILD_DUPLICATE_INDEX']):?>
	<script>
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("buildCompanyDuplicateIndex"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_COMPANY_BUILD_DUPLICATE_INDEX_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_COMPANY_BUILD_DUPLICATE_INDEX_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("buildCompanyDuplicateIndex",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "BUILD_DUPLICATE_INDEX",
						container: "buildCompanyDuplicateIndexWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>

<?if($arResult['NEED_FOR_REBUILD_COMPANY_ATTRS']):?>
<script>
	BX.ready(
		function()
		{
			var link = BX("rebuildCompanyAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildCompanyAttrsMsg");
						if(msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;?>

<?if($arResult['NEED_FOR_TRANSFER_REQUISITES']):?>
<script>
	BX.ready(
		function()
		{
			BX.CrmRequisitePresetSelectDialog.messages =
			{
				title: "<?=GetMessageJS("CRM_COMPANY_RQ_TX_SELECTOR_TITLE")?>",
				presetField: "<?=GetMessageJS("CRM_COMPANY_RQ_TX_SELECTOR_FIELD")?>"
			};

			BX.CrmRequisiteConverter.messages =
			{
				processDialogTitle: "<?=GetMessageJS('CRM_COMPANY_RQ_TX_PROC_DLG_TITLE')?>",
				processDialogSummary: "<?=GetMessageJS('CRM_COMPANY_RQ_TX_PROC_DLG_DLG_SUMMARY')?>"
			};

			var converter = BX.CrmRequisiteConverter.create(
				"converter",
				{
					entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::CompanyName)?>",
					serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.company.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
				}
			);

			BX.addCustomEvent(
				converter,
				'ON_COMPANY_REQUISITE_TRANFER_COMPLETE',
				function()
				{
					var msg = BX("transferRequisitesMsg");
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var transferLink = BX("transferRequisitesLink");
			if(transferLink)
			{
				BX.bind(
					transferLink,
					"click",
					function(e)
					{
						converter.convert();
						return BX.PreventDefault(e);
					}
				);
			}

			var skipTransferLink = BX("skipTransferRequisitesLink");
			if(skipTransferLink)
			{
				BX.bind(
					skipTransferLink,
					"click",
					function(e)
					{
						converter.skip();

						var msg = BX("transferRequisitesMsg");
						if(msg)
						{
							msg.style.display = "none";
						}

						return BX.PreventDefault(e);
					}
				);
			}
		}
	);
</script>
<?endif;?><?

if ($arResult['NEED_TO_CONVERT_ADDRESSES'])
{?>
<script>
	BX.ready(function () {
		if (BX.AutorunProcessPanel.isExists("convertCompanyAddresses"))
		{
			return;
		}
		BX.AutorunProcessManager.messages =
			{
				title: "<?=GetMessageJS('CRM_COMPANY_CONVERT_ADDRESSES_DLG_TITLE')?>",
				stateTemplate: "<?=GetMessageJS('CRM_COMPANY_CONVERT_ADDRESSES_STATE')?>"
			};
		var manager = BX.AutorunProcessManager.create(
			"convertCompanyAddresses",
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
				actionName: "CONVERT_ADDRESSES",
				container: "convertCompanyAddressesWrapper",
				enableLayout: true
			}
		);
		manager.runAfter(100);
	});
</script><?
}

if ($arResult['NEED_TO_CONVERT_UF_ADDRESSES'])
{?>
<script>
	BX.ready(function () {
		if (BX.AutorunProcessPanel.isExists("convertCompanyUfAddresses"))
		{
			return;
		}
		BX.AutorunProcessManager.messages =
			{
				title: "<?=GetMessageJS('CRM_COMPANY_CONVERT_UF_ADDRESSES_DLG_TITLE')?>",
				stateTemplate: "<?=GetMessageJS('CRM_COMPANY_CONVERT_UF_ADDRESSES_STATE')?>"
			};
		var manager = BX.AutorunProcessManager.create(
			"convertCompanyUfAddresses",
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
				actionName: "CONVERT_UF_ADDRESSES",
				container: "convertCompanyUfAddressesWrapper",
				enableLayout: true
			}
		);
		manager.runAfter(100);
	});
</script><?
}

if ($arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS'])
{?>
<script>
	BX.ready(function () {
		if (BX.AutorunProcessPanel.isExists("backgroundCompanyIndexRebuild"))
		{
			return;
		}
		BX.AutorunProcessManager.messages =
			{
				title: "<?=GetMessageJS('CRM_COMPANY_BACKGROUND_DUPLICATE_INDEX_REBUILD_TITLE')?>",
				stateTemplate: "<?=GetMessageJS('CRM_COMPANY_BACKGROUND_DUPLICATE_INDEX_REBUILD_STATE')?>"
			};
		var manager = BX.AutorunProcessManager.create(
			"backgroundCompanyIndexRebuild",
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
				actionName: "BACKGROUND_INDEX_REBUILD",
				container: "backgroundCompanyIndexRebuildWrapper",
				enableLayout: true
			}
		);
		manager.runAfter(100);
	});
</script><?
}

if ($arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS'])
{?>
<script>
	BX.ready(function () {
		if (BX.AutorunProcessPanel.isExists("backgroundCompanyMerge"))
		{
			return;
		}
		BX.AutorunProcessManager.messages =
			{
				title: "<?=GetMessageJS('CRM_COMPANY_BACKGROUND_DUPLICATE_MERGE_TITLE')?>",
				stateTemplate: "<?=GetMessageJS('CRM_COMPANY_BACKGROUND_DUPLICATE_MERGE_STATE')?>"
			};
		var manager = BX.AutorunProcessManager.create(
			"backgroundCompanyMerge",
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
				actionName: "BACKGROUND_MERGE",
				container: "backgroundCompanyMergeWrapper",
				enableLayout: true
			}
		);
		manager.runAfter(100);
	});
</script><?
}

if ($arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE'])
{?>
	<script>
		BX.ready(function () {
			if (BX.AutorunProcessPanel.isExists("backgroundCompanyIndexRebuild"))
			{
				return;
			}
			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_COMPANY_BACKGROUND_DUPLICATE_VOL_DATA_PREPARE_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_COMPANY_BACKGROUND_DUPLICATE_VOL_DATA_PREPARE_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"backgroundCompanyDupVolDataPrepare",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.company.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "BACKGROUND_DUP_VOL_DATA_PREPARE",
					container: "backgroundCompanyDupVolDataPrepareWrapper",
					enableLayout: true
				}
			);
			manager.runAfter(100);
		});
	</script><?
}

if (!empty($arResult['RESTRICTED_FIELDS_ENGINE']))
{
	Bitrix\Main\UI\Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['RESTRICTED_FIELDS_ENGINE'];
}

if (\Bitrix\Crm\Settings\Crm::isWhatsAppScenarioEnabled()):
/**
 * @see \Bitrix\Crm\Tour\GridGroupWhatsAppMessage select row event proxy
 */
?>
	<script>
		BX.ready(function () {
			BX.Event.EventEmitter.subscribeOnce('Grid::selectRow', function (event) {
				BX.Event.EventEmitter.emit(
					'BX.Crm.Tour.GridGroupWhatsAppMessage::selectRow',
					{
						stepId: 'grid-group-whatsapp-message',
						target: document.getElementById('whatsapp-message_control'),
						delay: 500
					}
				);
			});
		})
	</script>
<?php
endif;

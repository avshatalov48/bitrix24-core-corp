<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
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
use Bitrix\Main\Web\Uri;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
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
		'ui.fonts.opensans',
		'ui.progressbar',
		'ui.icons.b24',
		'crm.autorun',
		'crm.entity-list.panel',
		'crm.badge',
		'ui.design-tokens',
		'crm.template.editor',
	]
);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

?><div id="crm-contact-list-progress-bar-container"></div><div id="batchDeletionWrapper"></div><?

echo (\Bitrix\Crm\Tour\NumberOfClients::getInstance())->build();

echo \Bitrix\Crm\Tour\GridGroupWhatsAppMessage::getInstance()->build();

if($arResult['NEED_TO_CONVERT_ADDRESSES']):
	?><div id="convertContactAddressesWrapper"></div><?
endif;
if($arResult['NEED_TO_CONVERT_UF_ADDRESSES']):
	?><div id="convertContactUfAddressesWrapper"></div><?
endif;
if($arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS']):
	?><div id="backgroundContactIndexRebuildWrapper"></div><?
endif;
if($arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS']):
	?><div id="backgroundContactMergeWrapper"></div><?
endif;
if($arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE']):
	?><div id="backgroundContactDupVolDataPrepareWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):
	?><div id="rebuildContactDupIndexMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONTACT_REBUILD_DUP_INDEX', array('#ID#' => 'rebuildContactDupIndexLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):
	?><div id="rebuildContactSearchWrapper"></div><?
endif;

if($arResult['NEED_FOR_BUILD_TIMELINE']):
	?><div id="buildContactTimelineWrapper"></div><?
endif;

if($arResult['NEED_FOR_BUILD_DUPLICATE_INDEX']):
	?><div id="buildContactDuplicateIndexWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']):
	?><div id="rebuildContactSecurityAttrsWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']):
	?><div id="rebuildContactAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONTACT_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildContactAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
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
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if(!$isInternal):
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'CONTACT',
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
	'ownerType' => 'CONTACT',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);
$prefix = $arResult['GRID_ID'];
$prefixLC = mb_strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();

$fieldContentTypeMap = \Bitrix\Crm\Model\FieldContentTypeTable::loadForMultipleItems(
	\CCrmOwnerType::Contact,
	array_keys($arResult['CONTACT']),
);

if ($arResult['NEED_ADD_ACTIVITY_BLOCK'] ?? false)
{
	$arResult['CONTACT'] = (new \Bitrix\Crm\Component\EntityList\NearestActivity\Manager(CCrmOwnerType::Contact))->appendNearestActivityBlock($arResult['CONTACT']);
}

$toolsManager = Container::getInstance()->getIntranetToolsManager();
$availabilityManager = AvailabilityManager::getInstance();
$isQuoteAvailable = $toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Quote);
$isInvoiceAvailable = $toolsManager->checkEntityTypeAvailability(\CCrmOwnerType::Invoice);

foreach($arResult['CONTACT'] as $sKey =>  $arContact)
{
	$arEntitySubMenuItems = array();
	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_CONTACT_SHOW_TEXT'),
		'TEXT' => GetMessage('CRM_CONTACT_SHOW_TEXT'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_CONTACT_SHOW'])."')",
		'DEFAULT' => true
	);
	if($arContact['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_CONTACT_EDIT_TEXT'),
			'TEXT' => GetMessage('CRM_CONTACT_EDIT_TEXT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_CONTACT_EDIT'])."')"
		);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_CONTACT_COPY_TEXT'),
			'TEXT' => GetMessage('CRM_CONTACT_COPY_TEXT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_CONTACT_COPY'])."')",
		);
	}

	if(!$isInternal && $arContact['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arContact['PATH_TO_CONTACT_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_CONTACT_DELETE_TEXT'),
			'TEXT' => GetMessage('CRM_CONTACT_DELETE_TEXT'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	if(!$isInternal)
	{
		if($arResult['PERM_DEAL'] && !$arResult['CATEGORY_ID'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_CONTACT_DEAL_ADD_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_DEAL_ADD'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_DEAL_EDIT'])."')"
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
				$onClick = "jsUtils.Redirect([], '" . CUtil::JSEscape($analyticsEventBuilderForQuote->buildUri(
					$arContact['PATH_TO_QUOTE_ADD'])->getUri()
				) . "');";
			}
			else
			{
				$onClick = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::Quote);
			}

			$quoteAction = [
				'TITLE' => GetMessage('CRM_CONTACT_ADD_QUOTE_TITLE_MSGVER_1'),
				'TEXT' => GetMessage('CRM_CONTACT_ADD_QUOTE_MSGVER_1'),
				'ONCLICK' => $onClick,
			];
			if ($isQuoteAvailable && \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->isFactoryEnabled())
			{
				unset($quoteAction['ONCLICK']);
				$link = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
					\CCrmOwnerType::Quote,
					0,
					null
				);
				$link->addParams([
					'contact_id' => $arContact['ID'],
				]);
				$quoteAction['HREF'] = $analyticsEventBuilderForQuote->buildUri($link)->getUri();
			}
			$arEntitySubMenuItems[] = $quoteAction;
		}
		if(
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
					$analyticsEventBuilderForInvoice->buildUri($arContact['PATH_TO_INVOICE_ADD'])->getUri()
				) . "');";
			}
			else
			{
				$onClick = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::Invoice);
			}

			$localization = \Bitrix\Crm\Service\Container::getInstance()->getLocalization();
			$arEntitySubMenuItems[] = array(
				'TITLE' => $localization->appendOldVersionSuffix(GetMessage('CRM_DEAL_ADD_INVOICE_TITLE')),
				'TEXT' => $localization->appendOldVersionSuffix(GetMessage('CRM_DEAL_ADD_INVOICE')),
				'ONCLICK' => $onClick,
			);
		}
		if (
			\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkAddPermissions(\CCrmOwnerType::SmartInvoice)
			&& !$arResult['CATEGORY_ID']
		)
		{
			$analyticsEventBuilderFoSmartrInvoice = \Bitrix\Crm\Integration\Analytics\Builder\Entity\AddOpenEvent::createDefault(\CCrmOwnerType::SmartInvoice)
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
				$link = \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
					\CCrmOwnerType::SmartInvoice,
					0,
					null,
					new \Bitrix\Crm\ItemIdentifier(
						\CCrmOwnerType::Contact,
						$arContact['ID']
					)
				);
				$subMenuItem['HREF'] = $analyticsEventBuilderFoSmartrInvoice->buildUri($link)->getUri();
			}
			else
			{
				$subMenuItem['ONCLICK'] = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::SmartInvoice);
			}

			$arEntitySubMenuItems[] = $subMenuItem;
		}

		if(!empty($arEntitySubMenuItems))
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_CONTACT_ADD_ENTITY_TITLE_MSGVER_1'),
				'TEXT' => GetMessage('CRM_CONTACT_ADD_ENTITY'),
				'MENU' => $arEntitySubMenuItems
			);
		}

		$arActions[] = array('SEPARATOR' => true);

		if($arContact['EDIT'])
		{
			$currentUser = CUtil::PhpToJSObject(CCrmViewHelper::getUserInfo(true, false));

			$pingSettings = (new TodoPingSettingsProvider(
				\CCrmOwnerType::Contact,
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
				'TEXT' => GetMessage('CRM_CONTACT_ADD_TODO'),
				'ONCLICK' => "BX.CrmUIGridExtension.showActivityAddingPopupFromMenu('".$preparedGridId."', " . CCrmOwnerType::Contact . ", " . (int)$arContact['ID'] . ", " . $currentUser . ", " . $settings . ", " . $analytics .");"
			];

			if(IsModuleInstalled('subscribe'))
			{
				$arActions[] = $arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_ADD_EMAIL_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_EMAIL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.email, settings: { ownerID: {$arContact['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled(CRM_MODULE_CALENDAR_ID) && \Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled())
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arContact['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arContact['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arContact['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arContact['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arContact['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arContact['ID']} } }
					)"
				);
			}

			if(!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_ADD_ACTIVITY_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}

			if($arResult['IS_BIZPROC_AVAILABLE'])
			{
				\Bitrix\Main\UI\Extension::load(['bp_starter']);

				$arActions[] = ['SEPARATOR' => true];
				$arActions[] = [
					'TITLE' => \Bitrix\Main\Localization\Loc::getMessage('CRM_CONTACT_BIZPROC_LIST_TITLE'),
					'TEXT' => \Bitrix\Main\Localization\Loc::getMessage('CRM_CONTACT_BIZPROC_LIST'),
					'ONCLICK' => (
						$toolsManager->checkBizprocAvailability()
							? CCrmBizProcHelper::getShowTemplatesJsAction(
								CCrmOwnerType::Contact,
								$arContact['ID'],
								'function(){BX.Main.gridManager.reload(\'' . CUtil::JSEscape($arResult['GRID_ID']) . '\');}'
							)
							: $availabilityManager->getBizprocAvailabilityLock()
					),
				];
			}
		}
	}

	$eventParam = [
		'ID' => $arContact['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID'],
	];

	foreach (GetModuleEvents('crm', 'onCrmContactListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, ['CRM_CONTACT_LIST_MENU', $eventParam, &$arActions]);
	}

	$dateCreate = $arContact['DATE_CREATE'] ?? '';
	$dateModify = $arContact['DATE_MODIFY'] ?? '';
	$sourceId = null;
	if (isset($arContact['SOURCE_ID']))
	{
		$sourceId = $arResult['SOURCE_LIST'][$arContact['SOURCE_ID']] ?? $arContact['SOURCE_ID'];
	}
	$webformId = null;
	if (isset($arContact['WEBFORM_ID']))
	{
		$webformId = $arResult['WEBFORM_LIST'][$arContact['WEBFORM_ID']] ?? $arContact['WEBFORM_ID'];
	}

	$resultItem = [
		'id' => $arContact['ID'],
		'actions' => $arActions,
		'data' => $arContact,
		'editable' => !$arContact['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'CONTACT_SUMMARY' => (new \Bitrix\Crm\Service\Display\ClientSummary(\CCrmOwnerType::Contact, (int)$arContact['ID']))
				->withUrl((string)$arContact['PATH_TO_CONTACT_SHOW'])
				->withTitle((string)$arContact['CONTACT_FORMATTED_NAME'])
				->withDescription((string)$arContact['CONTACT_TYPE_NAME'])
				->withTracking(true)
				->withPhoto((int)$arContact['~PHOTO'])
				->render()
			,
			'CONTACT_COMPANY' => isset($arContact['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arContact['COMPANY_INFO']) : '',
			'COMPANY_ID' => isset($arContact['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arContact['COMPANY_INFO']) : '',
			'ASSIGNED_BY' => $arContact['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					[
						'PREFIX' => "CONTACT_{$arContact['~ID']}_RESPONSIBLE",
						'USER_ID' => $arContact['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arContact['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arContact['PATH_TO_USER_PROFILE'],
					]
				)
                : '',
			'COMMENTS' => htmlspecialcharsback($arContact['COMMENTS'] ?? ''),
			'SOURCE_DESCRIPTION' => nl2br($arContact['SOURCE_DESCRIPTION'] ?? ''),
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateCreate), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateModify), $now),
			'HONORIFIC' => $arResult['HONORIFIC'][$arContact['HONORIFIC']] ?? '',
			'TYPE_ID' => $arResult['TYPE_LIST'][$arContact['TYPE_ID']] ?? $arContact['TYPE_ID'],
			'SOURCE_ID' => $sourceId,
			'WEBFORM_ID' => $webformId,
			'CREATED_BY' => isset($arContact['~CREATED_BY']) && $arContact['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "CONTACT_{$arContact['~ID']}_CREATOR",
						'USER_ID' => $arContact['~CREATED_BY'],
						'USER_NAME'=> $arContact['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arContact['PATH_TO_USER_CREATOR']
					)
				) : '',
			'MODIFY_BY' => isset($arContact['~MODIFY_BY']) && $arContact['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "CONTACT_{$arContact['~ID']}_MODIFIER",
						'USER_ID' => $arContact['~MODIFY_BY'],
						'USER_NAME'=> $arContact['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arContact['PATH_TO_USER_MODIFIER']
					)
				) : '',
			'OBSERVERS' => CCrmViewHelper::renderObservers(\CCrmOwnerType::Contact, $arContact['ID'], $arContact['~OBSERVERS'] ?? []),
		) + CCrmViewHelper::RenderListMultiFields($arContact, "CONTACT_{$arContact['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::ContactName, 'ENTITY_ID' => $arContact['ID']))) + $arResult['CONTACT_UF'][$sKey]
	];

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Contact,
		$arContact['ID'],
		$resultItem['columns']
	);

	$resultItem['columns'] = \Bitrix\Crm\Entity\CommentsHelper::enrichGridRow(
		\CCrmOwnerType::Contact,
		$fieldContentTypeMap[$arContact['ID']] ?? [],
		$arContact,
		$resultItem['columns'],
	);

	if ($arResult['ENABLE_OUTMODED_FIELDS'])
	{
		$resultItem['columns']['ADDRESS'] = nl2br($arContact['ADDRESS']);
	}

	if (isset($arContact['~BIRTHDATE']))
	{
		$resultItem['columns']['BIRTHDATE'] = FormatDate('SHORT', MakeTimeStamp($arContact['~BIRTHDATE']));
	}

	if (isset($arContact['ACTIVITY_BLOCK']) && $arContact['ACTIVITY_BLOCK'] instanceof \Bitrix\Crm\Component\EntityList\NearestActivity\Block)
	{
		$resultItem['columns']['ACTIVITY_ID'] = $arContact['ACTIVITY_BLOCK']->render($gridManagerID);
		if ($arContact['ACTIVITY_BLOCK']->needHighlight())
		{
			$resultItem['columnClasses'] = ['ACTIVITY_ID' => 'crm-list-deal-today'];
		}
	}

	if (isset($arContact['badges']) && is_array($arContact['badges']))
	{
		$resultItem['columns']['CONTACT_SUMMARY'] .= Bitrix\Crm\Component\EntityList\BadgeBuilder::render($arContact['badges']);
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}

if($arResult['ENABLE_TOOLBAR'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => array(
				array(
					'TEXT' => GetMessage('CRM_CONTACT_LIST_ADD_SHORT'),
					'TITLE' => GetMessage('CRM_CONTACT_LIST_ADD'),
					'LINK' => $arResult['PATH_TO_CONTACT_ADD'],
					'ICON' => 'btn-new'
				)
			)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.newentity.counter.panel',
	'',
	array(
		'ENTITY_TYPE_NAME' => CCrmOwnerType::ContactName,
		'GRID_ID' => $arResult['GRID_ID'],
		'CATEGORY_ID' => $arResult['CATEGORY_ID'],
	),
	$component
);

$filterLazyLoadUrl = '/bitrix/components/bitrix/crm.contact.list/filter.ajax.php?' . bitrix_sessid_get();
$filterLazyLoadParams = [
	'filter_id' => urlencode($arResult['GRID_ID']),
	'category_id' => $arResult['CATEGORY_ID'] ?? null,
	'siteID' => SITE_ID,
];
$uri = new Uri($filterLazyLoadUrl);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	array(
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
		'HIDE_FILTER' => isset($arParams['HIDE_FILTER']) ? $arParams['HIDE_FILTER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => array(
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
		),
		'LIVE_SEARCH_LIMIT_INFO' => isset($arResult['LIVE_SEARCH_LIMIT_INFO'])
			? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => [
			'GROUPS' => [
				[
					'ITEMS' => $isInternal ? [] : $arResult['PANEL']?->getControls(),
				],
			],
		],
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Contact))->setBinding($arResult['NAVIGATION_CONTEXT_ID'])->get(),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::ContactName,
				'categoryId' => (int)($arResult['CATEGORY_ID'] ?? 0),
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.contact.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_CONTACT_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_CONTACT_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_CONTACT_DELETE'),
				'processExportDialogTitle' => GetMessageJS('CRM_CONTACT_EXPORT_DIALOG_TITLE'),
				'processExportDialogSummary' => GetMessageJS('CRM_CONTACT_EXPORT_DIALOG_SUMMARY'),
			)
		)
	)
);

?><script>
BX.ready(
		function()
		{
			BX.CrmSipManager.getCurrent().setServiceUrl(
				"CRM_<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>",
				"/bitrix/components/bitrix/crm.contact.show/ajax.php?<?=bitrix_sessid_get()?>"
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
	$settingsButtonExtenderParams = \Bitrix\Crm\UI\SettingsButtonExtender\SettingsButtonExtenderParams::createDefaultForGrid(
		\CCrmOwnerType::Contact,
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

if(!$isInternal):?>
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
				BX.Crm.Activity.Planner.Manager.setCallback(
					'onAfterActivitySave',
					function()
					{
						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					}
				);
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
			startButton: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_BTN_START')?>",
			stopButton: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_BTN_STOP')?>",
			closeButton: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_BTN_CLOSE')?>",
			wait: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_WAIT')?>",
			requestError: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_REQUEST_ERR')?>"
		};

		BX.Crm.EntityList.Panel.init(<?= \CUtil::PhpToJSObject([
			'gridId' => $arResult['GRID_ID'],
			'progressBarContainerId' => 'crm-contact-list-progress-bar-container',
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
			rebuildContactIndexDlgTitle: "<?=GetMessageJS('CRM_CONTACT_REBUILD_DUP_INDEX_DLG_TITLE')?>",
			rebuildContactIndexDlgSummary: "<?=GetMessageJS('CRM_CONTACT_REBUILD_DUP_INDEX_DLG_SUMMARY')?>"
		};
		var mgr = BX.CrmDuplicateManager.create("mgr", { entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>", serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.contact.list/list.ajax.php?&<?=bitrix_sessid_get()?>" });
		BX.addCustomEvent(
			mgr,
			'ON_CONTACT_INDEX_REBUILD_COMPLETE',
			function()
			{
				var msg = BX("rebuildContactDupIndexMsg");
				if(msg)
				{
					msg.style.display = "none";
				}
			}
		);

		var link = BX("rebuildContactDupIndexLink");
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
				if(BX.AutorunProcessPanel.isExists("rebuildContactSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_CONTACT_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildContactSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildContactSearchWrapper",
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
					"rebuildContactSecurityAttrs",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SECURITY_ATTRS",
						container: "rebuildContactSecurityAttrsWrapper",
						title: "<?=GetMessageJS('CRM_CONTACT_REBUILD_SECURITY_ATTRS_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_CONTACT_STEPWISE_STATE_TEMPLATE')?>",
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
				if(BX.AutorunProcessPanel.isExists("buildContactTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_CONTACT_BUILD_TIMELINE_DLG_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_CONTACT_BUILD_TIMELINE_STATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("buildContactTimeline",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "BUILD_TIMELINE",
						container: "buildContactTimelineWrapper",
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
				if(BX.AutorunProcessPanel.isExists("buildContactDuplicateIndex"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_CONTACT_BUILD_DUPLICATE_INDEX_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_CONTACT_BUILD_DUPLICATE_INDEX_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("buildContactDuplicateIndex",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "BUILD_DUPLICATE_INDEX",
						container: "buildContactDuplicateIndexWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']):?>
<script>
BX.ready(
	function()
	{
		var link = BX("rebuildContactAttrsLink");
		if(link)
		{
			BX.bind(
				link,
				"click",
				function(e)
				{
					var msg = BX("rebuildContactAttrsMsg");
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
			title: "<?=GetMessageJS("CRM_CONTACT_RQ_TX_SELECTOR_TITLE")?>",
			presetField: "<?=GetMessageJS("CRM_CONTACT_RQ_TX_SELECTOR_FIELD")?>"
		};

		BX.CrmRequisiteConverter.messages =
		{
			processDialogTitle: "<?=GetMessageJS('CRM_CONTACT_RQ_TX_PROC_DLG_TITLE')?>",
			processDialogSummary: "<?=GetMessageJS('CRM_CONTACT_RQ_TX_PROC_DLG_DLG_SUMMARY')?>"
		};

		var converter = BX.CrmRequisiteConverter.create(
			"converter",
			{
				entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>",
				serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.contact.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
			}
		);

		BX.addCustomEvent(
			converter,
			'ON_CONTACT_REQUISITE_TRANFER_COMPLETE',
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
if($arResult['NEED_TO_CONVERT_ADDRESSES'])
{?>
<script>
	BX.ready(function () {
		if (BX.AutorunProcessPanel.isExists("convertContactAddresses"))
		{
			return;
		}
		BX.AutorunProcessManager.messages =
			{
				title: "<?=GetMessageJS('CRM_CONTACT_CONVERT_ADDRESSES_DLG_TITLE')?>",
				stateTemplate: "<?=GetMessageJS('CRM_CONTACT_CONVERT_ADDRESSES_STATE')?>"
			};
		var manager = BX.AutorunProcessManager.create(
			"convertContactAddresses",
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
				actionName: "CONVERT_ADDRESSES",
				container: "convertContactAddressesWrapper",
				enableLayout: true
			}
		);
		manager.runAfter(100);
	});
</script><?
}

if($arResult['NEED_TO_CONVERT_UF_ADDRESSES'])
{?>
<script>
	BX.ready(function () {
		if (BX.AutorunProcessPanel.isExists("convertContactUfAddresses"))
		{
			return;
		}
		BX.AutorunProcessManager.messages =
			{
				title: "<?=GetMessageJS('CRM_CONTACT_CONVERT_UF_ADDRESSES_DLG_TITLE')?>",
				stateTemplate: "<?=GetMessageJS('CRM_CONTACT_CONVERT_UF_ADDRESSES_STATE')?>"
			};
		var manager = BX.AutorunProcessManager.create(
			"convertContactUfAddresses",
			{
				serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
				actionName: "CONVERT_UF_ADDRESSES",
				container: "convertContactUfAddressesWrapper",
				enableLayout: true
			}
		);
		manager.runAfter(100);
	});
</script><?
}

if($arResult['NEED_TO_SHOW_DUP_INDEX_PROCESS'])
{?>
	<script>
		BX.ready(function () {
			if (BX.AutorunProcessPanel.isExists("backgroundContactIndexRebuild"))
			{
				return;
			}
			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_CONTACT_BACKGROUND_DUPLICATE_INDEX_REBUILD_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_CONTACT_BACKGROUND_DUPLICATE_INDEX_REBUILD_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"backgroundContactIndexRebuild",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "BACKGROUND_INDEX_REBUILD",
					container: "backgroundContactIndexRebuildWrapper",
					enableLayout: true
				}
			);
			manager.runAfter(100);
		});
	</script><?
}
if($arResult['NEED_TO_SHOW_DUP_MERGE_PROCESS'])
{?>
	<script>
		BX.ready(function () {
			if (BX.AutorunProcessPanel.isExists("backgroundContactMerge"))
			{
				return;
			}
			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_CONTACT_BACKGROUND_DUPLICATE_MERGE_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_CONTACT_BACKGROUND_DUPLICATE_MERGE_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"backgroundContactMerge",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "BACKGROUND_MERGE",
					container: "backgroundContactMergeWrapper",
					enableLayout: true
				}
			);
			manager.runAfter(100);
		});
	</script><?
}
if($arResult['NEED_TO_SHOW_DUP_VOL_DATA_PREPARE'])
{?>
	<script>
		BX.ready(function () {
			if (BX.AutorunProcessPanel.isExists("backgroundContactIndexRebuild"))
			{
				return;
			}
			BX.AutorunProcessManager.messages =
				{
					title: "<?=GetMessageJS('CRM_CONTACT_BACKGROUND_DUPLICATE_VOL_DATA_PREPARE_TITLE')?>",
					stateTemplate: "<?=GetMessageJS('CRM_CONTACT_BACKGROUND_DUPLICATE_VOL_DATA_PREPARE_STATE')?>"
				};
			var manager = BX.AutorunProcessManager.create(
				"backgroundContactDupVolDataPrepare",
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.contact.list/list.ajax.php?'.bitrix_sessid_get()?>",
					actionName: "BACKGROUND_DUP_VOL_DATA_PREPARE",
					container: "backgroundContactDupVolDataPrepareWrapper",
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

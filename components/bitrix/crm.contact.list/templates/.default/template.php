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

use Bitrix\Crm\Integration;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\NavigationBarPanel;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}
Bitrix\Main\UI\Extension::load(['crm.merger.batchmergemanager', 'ui.fonts.opensans']);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/analytics.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_deletion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

Bitrix\Main\UI\Extension::load(
	[
		'ui.progressbar',
		'ui.icons.b24',
		'crm.restriction.filter-fields',
	]
);

?><div id="batchDeletionWrapper"></div><?

echo (\Bitrix\Crm\Tour\NumberOfClients::getInstance())->build();

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
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'] && !$callListUpdateMode;
$allowDelete = $arResult['PERMS']['DELETE'] && !$callListUpdateMode;
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
			$quoteAction = [
				'TITLE' => GetMessage('CRM_CONTACT_ADD_QUOTE_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_ADD_QUOTE'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_QUOTE_ADD'])."');"
			];
			if (\Bitrix\Crm\Settings\QuoteSettings::getCurrent()->isFactoryEnabled())
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
				$quoteAction['HREF'] = $link;
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
			$localization = \Bitrix\Crm\Service\Container::getInstance()->getLocalization();
			$arEntitySubMenuItems[] = array(
				'TITLE' => $localization->appendOldVersionSuffix(GetMessage('CRM_DEAL_ADD_INVOICE_TITLE')),
				'TEXT' => $localization->appendOldVersionSuffix(GetMessage('CRM_DEAL_ADD_INVOICE')),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_INVOICE_ADD'])."');"
			);
		}
		if (
			\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkAddPermissions(\CCrmOwnerType::SmartInvoice)
			&& !$arResult['CATEGORY_ID']
		)
		{
			$arEntitySubMenuItems[] = [
				'TITLE' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
				'TEXT' => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
				'HREF' => \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getItemDetailUrl(
					\CCrmOwnerType::SmartInvoice,
					0,
					null,
					new \Bitrix\Crm\ItemIdentifier(
						\CCrmOwnerType::Contact,
						$arContact['ID']
					)
				),
			];
		}

		if(!empty($arEntitySubMenuItems))
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_CONTACT_ADD_ENTITY_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_ADD_ENTITY'),
				'MENU' => $arEntitySubMenuItems
			);
		}

		$arActions[] = array('SEPARATOR' => true);

		if($arContact['EDIT'])
		{
			if (\Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled())
			{
				$currentUser = CUtil::PhpToJSObject(CCrmViewHelper::getUserInfo(true, false));
				$arActivitySubMenuItems[] = [
					'TEXT' => GetMessage('CRM_CONTACT_ADD_TODO'),
					'ONCLICK' => "BX.CrmUIGridExtension.showActivityAddingPopupFromMenu('".$preparedGridId."', " . CCrmOwnerType::Contact . ", " . (int)$arContact['ID'] . ", " . $currentUser . ");"
				];
			}

			if (RestrictionManager::isHistoryViewPermitted() && !$arResult['CATEGORY_ID'])
			{
				$arActions[] = $arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_CONTACT_EVENT_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_EVENT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createEvent,
						{ entityTypeName: BX.CrmEntityType.names.contact, entityId: {$arContact['ID']} }
					)"
				);
			}

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
				$arActions[] = array('SEPARATOR' => true);

				if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_CONTACT_BIZPROC_TITLE'),
						'TEXT' => GetMessage('CRM_CONTACT_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_BIZPROC_LIST'])."');"
					);
				if(!empty($arContact['BIZPROC_LIST']))
				{
					$arBizprocList = array();
					foreach($arContact['BIZPROC_LIST'] as $arBizproc)
					{
						$arBizprocList[] = array(
							'TITLE' => $arBizproc['DESCRIPTION'],
							'TEXT' => $arBizproc['NAME'],
							'ONCLICK' => isset($arBizproc['ONCLICK'])
								? $arBizproc['ONCLICK']
								: "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
						);
					}
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_CONTACT_BIZPROC_LIST_TITLE'),
						'TEXT' => GetMessage('CRM_CONTACT_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					);
				}
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
		) + CCrmViewHelper::RenderListMultiFields($arContact, "CONTACT_{$arContact['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::ContactName, 'ENTITY_ID' => $arContact['ID']))) + $arResult['CONTACT_UF'][$sKey]
	];

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Contact,
		$arContact['ID'],
		$resultItem['columns']
	);

	$resultItem['columns'] = \Bitrix\Crm\Entity\FieldContentType::enrichGridRow(
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

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if(!$isInternal
	&& ($allowWrite || $allowDelete || $callListUpdateMode))
{
	$yesnoList = array(
		array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
	);

	$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
	$applyButton = $snippet->getApplyButton(
		array(
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processApplyButtonClick('{$gridManagerID}')"))
				)
			)
		)
	);

	$actionList = array(array('NAME' => GetMessage('CRM_CONTACT_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	if($allowWrite)
	{
		//region Add letter & Add to segment
		Integration\Sender\GridPanel::appendActions($actionList, $applyButton, $gridManagerID);
		//endregion
		//region Add Task
		if (IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_CONTACT_TASK'),
				'VALUE' => 'tasks',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array($applyButton)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'tasks')"))
					)
				)
			);
		}
		//endregion
		//region Assign To
		//region Render User Search control
		if(!Bitrix\Main\Grid\Context::isInternalRequest())
		{
			//action_assigned_by_search + _control
			//Prefix control will be added by main.ui.grid
			$APPLICATION->IncludeComponent(
				'bitrix:intranet.user.selector.new',
				'',
				array(
					'MULTIPLE' => 'N',
					'NAME' => "{$prefix}_ACTION_ASSIGNED_BY",
					'INPUT_NAME' => 'action_assigned_by_search_control',
					'SHOW_EXTRANET_USERS' => 'NONE',
					'POPUP' => 'Y',
					'SITE_ID' => SITE_ID,
					'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'] ?? null,
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
		//endregion
		$actionList[] = array(
			'NAME' => GetMessage('CRM_CONTACT_ASSIGN_TO'),
			'VALUE' => 'assign_to',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_assigned_by_search',
							'NAME' => 'ACTION_ASSIGNED_BY_SEARCH'
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_assigned_by_id',
							'NAME' => 'ACTION_ASSIGNED_BY_ID'
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array('JS' => "BX.CrmUIGridExtension.prepareAction('{$gridManagerID}', 'assign_to',  { searchInputId: 'action_assigned_by_search_control', dataInputId: 'action_assigned_by_id_control', componentName: '{$prefix}_ACTION_ASSIGNED_BY' })")
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'assign_to')"))
				)
			)
		);
		//endregion
		//region Create call list
		if(IsModuleInstalled('voximplant'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_CONTACT_CREATE_CALL_LIST'),
				'VALUE' => 'create_call_list',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							$applyButton
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'create_call_list')"))
					)
				)
			);
		}
		//endregion
	}

	if($allowDelete)
	{
		//region Remove button
		//$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
		$button = $snippet->getRemoveButton();
		$snippet->setButtonActions(
			$button,
			array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'CONFIRM' => false,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'delete')"))
				)
			)
		);
		$controlPanel['GROUPS'][0]['ITEMS'][] = $button;
		//endregion

		//$actionList[] = $snippet->getRemoveAction();

		if(!$arResult['IS_EXTERNAL_FILTER'])
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_CONTACT_ACTION_MERGE'),
				'VALUE' => 'merge',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							array_merge(
								$applyButton,
								['SETTINGS' => [
									'minSelectedRows' => 2,
									'buttonId' => 'apply_button'
								]]
							)
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'merge')"))
					)
				)
			);
		}

		$actionList[] = array(
			'NAME' => GetMessage('CRM_CONTACT_ACTION_DELETE'),
			'VALUE' => 'delete',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::RESET_CONTROLS,
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'delete')"))
				)
			)
		);
	}

	if($allowWrite)
	{
		//region Edit Button
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getEditButton();
		$actionList[] = $snippet->getEditAction();
		//endregion
		//region Mark as Opened
		$actionList[] = array(
			'NAME' => GetMessage('CRM_CONTACT_MARK_AS_OPENED'),
			'VALUE' => 'mark_as_opened',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_opened',
							'NAME' => 'ACTION_OPENED',
							'ITEMS' => $yesnoList
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'mark_as_opened')"))
				)
			)
		);
		//endregion
		//region Export
		$actionList[] = array(
			'NAME' => GetMessage('CRM_CONTACT_EXPORT_NEW'),
			'VALUE' => 'export',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_export',
							'NAME' => 'ACTION_EXPORT',
							'ITEMS' => $yesnoList
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'mark_as_opened')"))
				)
			)
		);
		//endregion
	}

	if($callListUpdateMode)
	{
		$callListContext = \CUtil::jsEscape($arResult['CALL_LIST_CONTEXT']);
		$controlPanel['GROUPS'][0]['ITEMS'][] = [
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_CONTACT_UPDATE_CALL_LIST"),
			"ID" => "update_call_list",
			"NAME" => "update_call_list",
			'ONCHANGE' => [
				[
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => [['JS' => "BX.CrmUIGridExtension.updateCallList('{$gridManagerID}', {$arResult['CALL_LIST_ID']}, '{$callListContext}')"]]
				]
			]
		];
	}
	else
	{
		//region Create & start call list
		if(IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_CONTACT_START_CALL_LIST'),
				"VALUE" => "start_call_list",
				"ONCHANGE" => array(
					array(
						"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						"DATA" => array(array('JS' => "BX.CrmUIGridExtension.createCallList('{$gridManagerID}', true)"))
					)
				)
			);
		}
		//endregion
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
			"ID" => "action_button_{$prefix}",
			"NAME" => "action_button_{$prefix}",
			"ITEMS" => $actionList
		);
	}

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}
//endregion

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
			'LAZY_LOAD' => array(
				'GET_LIST' => '/bitrix/components/bitrix/crm.contact.list/filter.ajax.php?action=list&filter_id='.urlencode($arResult['GRID_ID']).'&category_id='.$arResult['CATEGORY_ID'].'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'GET_FIELD' => '/bitrix/components/bitrix/crm.contact.list/filter.ajax.php?action=field&filter_id='.urlencode($arResult['GRID_ID']).'&category_id='.$arResult['CATEGORY_ID'].'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			),
			'ENABLE_FIELDS_SEARCH' => 'Y',
			'HEADERS_SECTIONS' => $arResult['HEADERS_SECTIONS'],
			'CONFIG' => [
				'AUTOFOCUS' => false,
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
		),
		'LIVE_SEARCH_LIMIT_INFO' => isset($arResult['LIVE_SEARCH_LIMIT_INFO'])
			? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
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
?><script type="text/javascript">
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
<?php if(
	!$isInternal
	&& \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME') !== 'Y'
	&& \Bitrix\Crm\Settings\Crm::isUniversalActivityScenarioEnabled()
): ?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Runtime.loadExtension(['crm.push-crm-settings', 'crm.toolbar-component']).then((exports) => {
				/** @see BX.Crm.ToolbarComponent */
				const settingsButton = exports.ToolbarComponent.Instance.getSettingsButton();

				/** @see BX.Crm.PushCrmSettings */
				new exports.PushCrmSettings({
					smartActivityNotificationSupported: <?= Container::getInstance()->getFactory(\CCrmOwnerType::Contact)->isSmartActivityNotificationSupported() ? 'true' : 'false' ?>,
					entityTypeId: <?= (int)\CCrmOwnerType::Contact ?>,
					rootMenu: settingsButton ? settingsButton.getMenuWindow() : undefined,
					grid: BX.Reflection.getClass('BX.Main.gridManager') ? BX.Main.gridManager.getInstanceById('<?= \CUtil::JSEscape($arResult['GRID_ID']) ?>') : undefined,
				});
			});
		}
	);
</script>
<?php endif; ?>
<?if(!$isInternal):?>
<script type="text/javascript">
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
<script type="text/javascript">
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

		var gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";
		BX.Crm.BatchDeletionManager.create(
			gridId,
			{
				gridId: gridId,
				entityTypeId: <?=CCrmOwnerType::Contact?>,
				extras: { CATEGORY_ID: <?=(int)$arResult['CATEGORY_ID']?> },
				container: "batchDeletionWrapper",
				stateTemplate: "<?=GetMessageJS('CRM_CONTACT_STEPWISE_STATE_TEMPLATE')?>",
				messages:
					{
						title: "<?=GetMessageJS('CRM_CONTACT_LIST_DEL_PROC_DLG_TITLE')?>",
						confirmation: "<?=GetMessageJS('CRM_CONTACT_LIST_DEL_PROC_DLG_SUMMARY')?>",
						summaryCaption: "<?=GetMessageJS('CRM_CONTACT_BATCH_DELETION_COMPLETED')?>",
						summarySucceeded: "<?=GetMessageJS('CRM_CONTACT_BATCH_DELETION_COUNT_SUCCEEDED')?>",
						summaryFailed: "<?=GetMessageJS('CRM_CONTACT_BATCH_DELETION_COUNT_FAILED')?>"
					}
			}
		);

		BX.Crm.BatchMergeManager.create(
			gridId,
			{
				gridId: gridId,
				entityTypeId: <?=CCrmOwnerType::Contact?>,
				mergerUrl: "<?=\CUtil::JSEscape($arParams['PATH_TO_CONTACT_MERGE'])?>"
			}
		);

		BX.Crm.AnalyticTracker.config =
			{
				id: "contact_list",
				settings: { params: <?=CUtil::PhpToJSObject($arResult['ANALYTIC_TRACKER'])?> }
			};
	}
);
</script>
<?if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):?>
<script type="text/javascript">
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
	<script type="text/javascript">
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
	<script type="text/javascript">
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
	<script type="text/javascript">
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
	<script type="text/javascript">
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
<script type="text/javascript">
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
<script type="text/javascript">
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
<script type="text/javascript">
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
<script type="text/javascript">
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
	<script type="text/javascript">
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
	<script type="text/javascript">
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
	<script type="text/javascript">
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

echo $arResult['ACTIVITY_FIELD_RESTRICTIONS'] ?? '';

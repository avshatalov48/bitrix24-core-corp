<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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
use Bitrix\Crm\Tracking;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/analytics.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_deletion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

Bitrix\Main\UI\Extension::load("ui.progressbar");

?><div id="batchDeletionWrapper"></div><?

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
$prefixLC = strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$now = time() + CTimeZone::GetOffset();

foreach($arResult['CONTACT'] as $sKey =>  $arContact)
{
	$arEntitySubMenuItems = array();
	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_CONTACT_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_CONTACT_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_CONTACT_SHOW'])."')",
		'DEFAULT' => true
	);
	if($arContact['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_CONTACT_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_CONTACT_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_CONTACT_EDIT'])."')"
		);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_CONTACT_COPY_TITLE'),
			'TEXT' => GetMessage('CRM_CONTACT_COPY'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_CONTACT_COPY'])."')",
		);
	}

	if(!$isInternal && $arContact['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arContact['PATH_TO_CONTACT_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_CONTACT_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_CONTACT_DELETE'),
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
		if($arResult['PERM_DEAL'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_CONTACT_DEAL_ADD_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_DEAL_ADD'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arContact['PATH_TO_DEAL_EDIT'])."')"
			);
		}

		if($arResult['PERM_QUOTE'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_CONTACT_ADD_QUOTE_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_ADD_QUOTE'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_QUOTE_ADD'])."');"
			);
		}
		if($arResult['PERM_INVOICE'] && IsModuleInstalled('sale'))
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_DEAL_ADD_INVOICE_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_ADD_INVOICE'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_INVOICE_ADD'])."');"
			);
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
			$arActions[] = $arActivityMenuItems[] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EVENT_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EVENT'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}', 
					BX.CrmUIGridMenuCommand.createEvent, 
					{ entityTypeName: BX.CrmEntityType.names.contact, entityId: {$arContact['ID']} }
				)"
			);

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

			if(IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
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
							'ONCLICK' => isset($arBizproc['ONCLICK']) ?
								$arBizproc['ONCLICK']
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

	$eventParam = array(
		'ID' => $arContact['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);
	foreach(GetModuleEvents('crm', 'onCrmContactListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_CONTACT_LIST_MENU', $eventParam, &$arActions));
	}

	$_sBPHint = 'class="'.($arContact['BIZPROC_STATUS'] != '' ? 'bizproc bizproc_status_'.$arContact['BIZPROC_STATUS'] : '').'"
				'.($arContact['BIZPROC_STATUS_HINT'] != '' ? 'onmouseover="BX.hint(this, \''.CUtil::JSEscape($arContact['BIZPROC_STATUS_HINT']).'\');"' : '');

	$resultItem = array(
		'id' => $arContact['ID'],
		'actions' => $arActions,
		'data' => $arContact,
		'editable' => !$arContact['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'CONTACT_SUMMARY' => CCrmViewHelper::RenderClientSummary(
				$arContact['PATH_TO_CONTACT_SHOW'],
				$arContact['CONTACT_FORMATTED_NAME'],
				Tracking\UI\Grid::enrichSourceName(
					\CCrmOwnerType::Contact,
					$arContact['ID'],
					$arContact['CONTACT_TYPE_NAME']
				),
				isset($arContact['PHOTO']) ? $arContact['PHOTO'] : '',
				'_top'
			),
			'CONTACT_COMPANY' => isset($arContact['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arContact['COMPANY_INFO']) : '',
			'COMPANY_ID' => isset($arContact['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arContact['COMPANY_INFO']) : '',
			'ASSIGNED_BY' => $arContact['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "CONTACT_{$arContact['~ID']}_RESPONSIBLE",
						'USER_ID' => $arContact['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arContact['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arContact['PATH_TO_USER_PROFILE']
					)
				) : '',
			'COMMENTS' => htmlspecialcharsback($arContact['COMMENTS']),
			'SOURCE_DESCRIPTION' => nl2br($arContact['SOURCE_DESCRIPTION']),
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arContact['DATE_CREATE']), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arContact['DATE_MODIFY']), $now),
			'HONORIFIC' => isset($arResult['HONORIFIC'][$arContact['HONORIFIC']]) ? $arResult['HONORIFIC'][$arContact['HONORIFIC']] : '',
			'TYPE_ID' => isset($arResult['TYPE_LIST'][$arContact['TYPE_ID']]) ? $arResult['TYPE_LIST'][$arContact['TYPE_ID']] : $arContact['TYPE_ID'],
			'SOURCE_ID' => isset($arResult['SOURCE_LIST'][$arContact['SOURCE_ID']]) ? $arResult['SOURCE_LIST'][$arContact['SOURCE_ID']] : $arContact['SOURCE_ID'],
			'WEBFORM_ID' => isset($arResult['WEBFORM_LIST'][$arContact['WEBFORM_ID']]) ? $arResult['WEBFORM_LIST'][$arContact['WEBFORM_ID']] : $arContact['WEBFORM_ID'],
			'CREATED_BY' => $arContact['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "CONTACT_{$arContact['~ID']}_CREATOR",
						'USER_ID' => $arContact['~CREATED_BY'],
						'USER_NAME'=> $arContact['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arContact['PATH_TO_USER_CREATOR']
					)
				) : '',
			'MODIFY_BY' => $arContact['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "CONTACT_{$arContact['~ID']}_MODIFIER",
						'USER_ID' => $arContact['~MODIFY_BY'],
						'USER_NAME'=> $arContact['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arContact['PATH_TO_USER_MODIFIER']
					)
				) : '',
		) + CCrmViewHelper::RenderListMultiFields($arContact, "CONTACT_{$arContact['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::ContactName, 'ENTITY_ID' => $arContact['ID']))) + $arResult['CONTACT_UF'][$sKey]
	);

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Contact,
		$arContact['ID'],
		$resultItem['columns']
	);

	if($arResult['ENABLE_OUTMODED_FIELDS'])
	{
		$resultItem['columns']['ADDRESS'] = nl2br($arContact['ADDRESS']);
	}

	if(isset($arContact['~BIRTHDATE']))
	{
		$resultItem['columns']['BIRTHDATE'] = FormatDate('SHORT', MakeTimeStamp($arContact['~BIRTHDATE']));
	}

	$userActivityID = isset($arContact['~ACTIVITY_ID']) ? intval($arContact['~ACTIVITY_ID']) : 0;
	$commonActivityID = isset($arContact['~C_ACTIVITY_ID']) ? intval($arContact['~C_ACTIVITY_ID']) : 0;
	if($userActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'ENTITY_ID' => $arContact['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arContact['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $userActivityID,
				'ACTIVITY_SUBJECT' => isset($arContact['~ACTIVITY_SUBJECT']) ? $arContact['~ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arContact['~ACTIVITY_TIME']) ? $arContact['~ACTIVITY_TIME'] : '',
				'ACTIVITY_EXPIRED' => isset($arContact['~ACTIVITY_EXPIRED']) ? $arContact['~ACTIVITY_EXPIRED'] : '',
				'ALLOW_EDIT' => $arContact['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arContact,
			'ACTIVITY' => array(
				'RESPONSIBLE_ID' => $currentUserID,
				'TIME' => isset($arContact['~ACTIVITY_TIME']) ? $arContact['~ACTIVITY_TIME'] : '',
				'IS_CURRENT_DAY' => isset($arContact['~ACTIVITY_IS_CURRENT_DAY']) ? $arContact['~ACTIVITY_IS_CURRENT_DAY'] : false
			)
		);

		if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentContactActivies, $counterData))
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
		}
	}
	elseif($commonActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'ENTITY_ID' => $arContact['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arContact['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $commonActivityID,
				'ACTIVITY_SUBJECT' => isset($arContact['~C_ACTIVITY_SUBJECT']) ? $arContact['~C_ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arContact['~C_ACTIVITY_TIME']) ? $arContact['~C_ACTIVITY_TIME'] : '',
				'ACTIVITY_RESPONSIBLE_ID' => isset($arContact['~C_ACTIVITY_RESP_ID']) ? intval($arContact['~C_ACTIVITY_RESP_ID']) : 0,
				'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arContact['~C_ACTIVITY_RESP_LOGIN']) ? $arContact['~C_ACTIVITY_RESP_LOGIN'] : '',
				'ACTIVITY_RESPONSIBLE_NAME' => isset($arContact['~C_ACTIVITY_RESP_NAME']) ? $arContact['~C_ACTIVITY_RESP_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arContact['~C_ACTIVITY_RESP_LAST_NAME']) ? $arContact['~C_ACTIVITY_RESP_LAST_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arContact['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arContact['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'ALLOW_EDIT' => $arContact['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
	}
	else
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'ENTITY_ID' => $arContact['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arContact['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ALLOW_EDIT' => $arContact['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
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
			'TOOLBAR_ID' => strtolower($arResult['GRID_ID']).'_toolbar',
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
					'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE']
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
		$actionList[] = array(
			'NAME' => GetMessage('CRM_CONTACT_ACTION_DELETE'),
			'VALUE' => 'delete',
			'ONCHANGE' => array(
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
			'NAME' => GetMessage('CRM_CONTACT_EXPORT'),
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
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_CONTACT_UPDATE_CALL_LIST"),
			"ID" => "update_call_list",
			"NAME" => "update_call_list",
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.updateCallList('{$gridManagerID}', {$arResult['CALL_LIST_ID']}, '{$arResult['CALL_LIST_CONTEXT']}')"))
				)
			)
		);
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
		'GRID_ID' => $arResult['GRID_ID']
	),
	$component
);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => array(
			'LAZY_LOAD' => array(
				'GET_LIST' => '/bitrix/components/bitrix/crm.contact.list/filter.ajax.php?action=list&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'GET_FIELD' => '/bitrix/components/bitrix/crm.contact.list/filter.ajax.php?action=field&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
			)
		),
		'LIVE_SEARCH_LIMIT_INFO' => isset($arResult['LIVE_SEARCH_LIMIT_INFO'])
			? $arResult['LIVE_SEARCH_LIMIT_INFO'] : null,
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAVIGATION_BAR' => array(
			'ITEMS' => array(
				array(
					//'icon' => 'table',
					'id' => 'list',
					'name' => GetMessage('CRM_CONTACT_LIST_FILTER_NAV_BUTTON_LIST'),
					'active' => true,
					'url' => $arParams['PATH_TO_CONTACT_LIST'],
				)
				/*array(
					//'icon' => 'chart',
					'id' => 'widget',
					'name' => GetMessage('CRM_CONTACT_LIST_FILTER_NAV_BUTTON_WIDGET'),
					'active' => false,
					'url' => $arParams['PATH_TO_CONTACT_WIDGET']
				)*/
			),
			'BINDING' => array(
				'category' => 'crm.navigation',
				'name' => 'index',
				'key' => strtolower($arResult['NAVIGATION_CONTEXT_ID'])
			)
		),
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
<?endif;?>

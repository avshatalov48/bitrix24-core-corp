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
Bitrix\Main\UI\Extension::load(['crm.merger.batchmergemanager']);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/analytics.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_deletion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

?><div id="batchDeletionWrapper"></div><?

if($arResult['NEED_TO_CONVERT_ADDRESSES']):
	?><div id="convertCompanyAddressesWrapper"></div><?
endif;
if($arResult['NEED_TO_CONVERT_UF_ADDRESSES']):
	?><div id="convertCompanyUfAddressesWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):
	?><div id="rebuildCompanyDupIndexMsg" class="crm-view-message">
		<?=GetMessage('CRM_COMPANY_REBUILD_DUP_INDEX', array('#ID#' => 'rebuildCompanyDupIndexLink', '#URL#' => '#'))?>
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

if($arResult['NEED_FOR_REBUILD_COMPANY_ATTRS']):
	?><div id="rebuildCompanyAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_COMPANY_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildCompanyAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
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
$gridManagerCfg = array(
	'ownerType' => 'COMPANY',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);
$prefix = $arResult['GRID_ID'];

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

$now = time() + CTimeZone::GetOffset();

foreach($arResult['COMPANY'] as $sKey =>  $arCompany)
{
	$arEntitySubMenuItems = array();
	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_COMPANY_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_COMPANY_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_SHOW'])."')",
		'DEFAULT' => true
	);
	if($arCompany['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_COMPANY_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_COMPANY_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_EDIT'])."')"
		);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_COMPANY_COPY_TITLE'),
			'TEXT' => GetMessage('CRM_COMPANY_COPY'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_COMPANY_COPY'])."')",
		);
	}

	if(!$isInternal && $arCompany['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arCompany['PATH_TO_COMPANY_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_COMPANY_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_COMPANY_DELETE'),
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
				'TEXT' => GetMessage('CRM_COMPANY_LIST_ACTION_MENU_SET_DEF_MYCOMPANY'),
				'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($companySetDefMyCompany).'\');'
			);
		}
	}

	if(!$isInternal && !$isMyCompanyMode)
	{
		$arActions[] = array('SEPARATOR' => true);

		if($arResult['PERM_CONTACT'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_COMPANY_CONTACT_ADD_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_CONTACT_ADD_SHORT'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_CONTACT_EDIT'])."')"
			);
		}
		if($arResult['PERM_DEAL'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_COMPANY_DEAL_ADD_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_DEAL_ADD_SHORT'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arCompany['PATH_TO_DEAL_EDIT'])."')"
			);
		}
		if($arResult['PERM_QUOTE'])
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_COMPANY_ADD_QUOTE_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_ADD_QUOTE_SHORT'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_QUOTE_ADD'])."');"
			);
		}
		if($arResult['PERM_INVOICE'] && IsModuleInstalled('sale'))
		{
			$arEntitySubMenuItems[] = array(
				'TITLE' => GetMessage('CRM_DEAL_ADD_INVOICE_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_ADD_INVOICE_SHORT'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_INVOICE_ADD'])."');"
			);
		}

		if(!empty($arEntitySubMenuItems))
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_COMPANY_ADD_ENTITY_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_ADD_ENTITY'),
				'MENU' => $arEntitySubMenuItems
			);
		}

		$arActions[] = array('SEPARATOR' => true);

		if($arCompany['EDIT'])
		{
			$arActions[] = $arActivityMenuItems[] = array(
				'TITLE' => GetMessage('CRM_COMPANY_EVENT_TITLE'),
				'TEXT' => GetMessage('CRM_COMPANY_EVENT'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}', 
					BX.CrmUIGridMenuCommand.createEvent, 
					{ entityTypeName: BX.CrmEntityType.names.company, entityId: {$arCompany['ID']} }
				)"
			);

			if(IsModuleInstalled('subscribe'))
			{
				$arActions[] = $arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_ADD_EMAIL_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_EMAIL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.email, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arCompany['ID']} } }
					)"
				);
			}

			if(!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_ADD_ACTIVITY_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}
		}
	}
	if($arCompany['EDIT'])
	{
		if($arResult['IS_BIZPROC_AVAILABLE'])
		{
			//$arActions[] = array('SEPARATOR' => true);
			if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_COMPANY_BIZPROC_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_BIZPROC'),
					'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arCompany['PATH_TO_BIZPROC_LIST'])."');"
				);
			if(!empty($arCompany['BIZPROC_LIST']))
			{
				$arBizprocList = array();
				foreach($arCompany['BIZPROC_LIST'] as $arBizproc)
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
					'TITLE' => GetMessage('CRM_COMPANY_BIZPROC_LIST_TITLE'),
					'TEXT' => GetMessage('CRM_COMPANY_BIZPROC_LIST'),
					'MENU' => $arBizprocList
				);
			}
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

	$resultItem = array(
		'id' => $arCompany['ID'],
		'actions' => $arActions,
		'data' => $arCompany,
		'editable' => !$arCompany['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'COMPANY_SUMMARY' => CCrmViewHelper::RenderClientSummary(
				$arCompany['PATH_TO_COMPANY_SHOW'],
				$arCompany['TITLE'],
				Tracking\UI\Grid::enrichSourceName(
					\CCrmOwnerType::Company,
					$arCompany['ID'],
					$arCompany['COMPANY_TYPE_NAME']
				),
				isset($arCompany['LOGO']) ? $arCompany['LOGO'] : '',
				'_top'
			),
			'ASSIGNED_BY' => $arCompany['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "COMPANY_{$arCompany['~ID']}_RESPONSIBLE",
						'USER_ID' => $arCompany['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arCompany['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arCompany['PATH_TO_USER_PROFILE']
					)
				) : '',
			'COMMENTS' => nl2br($arCompany['COMMENTS']),
			'REVENUE' =>  '<nobr>'.number_format($arCompany['REVENUE'], 2, ',', ' ').'</nobr>',
			'COMMENTS' => htmlspecialcharsback($arCompany['COMMENTS']),
			'BANKING_DETAILS' => nl2br($arCompany['BANKING_DETAILS']),
			'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arCompany['DATE_CREATE']), $now),
			'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($arCompany['DATE_MODIFY']), $now),
			'COMPANY_TYPE' => isset($arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']]) ? $arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']] : $arCompany['COMPANY_TYPE'],
			'CURRENCY_ID' =>  CCrmCurrency::GetCurrencyName($arCompany['CURRENCY_ID']),
			'WEBFORM_ID' => isset($arResult['WEBFORM_LIST'][$arCompany['WEBFORM_ID']]) ? $arResult['WEBFORM_LIST'][$arCompany['WEBFORM_ID']] : $arCompany['WEBFORM_ID'],
			'INDUSTRY' => isset($arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']]) ? $arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']] : $arCompany['INDUSTRY'],
			'EMPLOYEES' => isset($arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']]) ? $arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']] : $arCompany['EMPLOYEES'],
			'CREATED_BY' => $arCompany['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "COMPANY_{$arCompany['~ID']}_CREATOR",
						'USER_ID' => $arCompany['~CREATED_BY'],
						'USER_NAME'=> $arCompany['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arCompany['PATH_TO_USER_CREATOR']
					)
				) : '',
			'MODIFY_BY' => $arCompany['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "COMPANY_{$arCompany['~ID']}_MODIFIER",
						'USER_ID' => $arCompany['~MODIFY_BY'],
						'USER_NAME'=> $arCompany['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arCompany['PATH_TO_USER_MODIFIER']
					)
				) : '',
		) + CCrmViewHelper::RenderListMultiFields($arCompany, "COMPANY_{$arCompany['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::CompanyName, 'ENTITY_ID' => $arCompany['ID']))) + $arResult['COMPANY_UF'][$sKey]
	);

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Company,
		$arCompany['ID'],
		$resultItem['columns']
	);

	if($arResult['ENABLE_OUTMODED_FIELDS'])
	{
		$resultItem['columns']['ADDRESS'] = nl2br($arCompany['ADDRESS']);
		$resultItem['columns']['ADDRESS_LEGAL'] = nl2br($arCompany['ADDRESS_LEGAL']);
	}

	$userActivityID = isset($arCompany['~ACTIVITY_ID']) ? intval($arCompany['~ACTIVITY_ID']) : 0;
	$commonActivityID = isset($arCompany['~C_ACTIVITY_ID']) ? intval($arCompany['~C_ACTIVITY_ID']) : 0;
	if($userActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'ENTITY_ID' => $arCompany['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arCompany['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $userActivityID,
				'ACTIVITY_SUBJECT' => isset($arCompany['~ACTIVITY_SUBJECT']) ? $arCompany['~ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arCompany['~ACTIVITY_TIME']) ? $arCompany['~ACTIVITY_TIME'] : '',
				'ACTIVITY_EXPIRED' => isset($arCompany['~ACTIVITY_EXPIRED']) ? $arCompany['~ACTIVITY_EXPIRED'] : '',
				'ALLOW_EDIT' => $arCompany['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arCompany,
			'ACTIVITY' => array(
				'RESPONSIBLE_ID' => $currentUserID,
				'TIME' => isset($arCompany['~ACTIVITY_TIME']) ? $arCompany['~ACTIVITY_TIME'] : '',
				'IS_CURRENT_DAY' => isset($arCompany['~ACTIVITY_IS_CURRENT_DAY']) ? $arCompany['~ACTIVITY_IS_CURRENT_DAY'] : false
			)
		);

		if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentCompanyActivies, $counterData))
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
		}
	}
	elseif($commonActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'ENTITY_ID' => $arCompany['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arCompany['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $commonActivityID,
				'ACTIVITY_SUBJECT' => isset($arCompany['~C_ACTIVITY_SUBJECT']) ? $arCompany['~C_ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arCompany['~C_ACTIVITY_TIME']) ? $arCompany['~C_ACTIVITY_TIME'] : '',
				'ACTIVITY_RESPONSIBLE_ID' => isset($arCompany['~C_ACTIVITY_RESP_ID']) ? intval($arCompany['~C_ACTIVITY_RESP_ID']) : 0,
				'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arCompany['~C_ACTIVITY_RESP_LOGIN']) ? $arCompany['~C_ACTIVITY_RESP_LOGIN'] : '',
				'ACTIVITY_RESPONSIBLE_NAME' => isset($arCompany['~C_ACTIVITY_RESP_NAME']) ? $arCompany['~C_ACTIVITY_RESP_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arCompany['~C_ACTIVITY_RESP_LAST_NAME']) ? $arCompany['~C_ACTIVITY_RESP_LAST_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arCompany['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arCompany['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'ALLOW_EDIT' => $arCompany['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
	}
	else
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'ENTITY_ID' => $arCompany['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arCompany['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ALLOW_EDIT' => $arCompany['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
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

	$actionList = array(array('NAME' => GetMessage('CRM_COMPANY_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	if($allowWrite)
	{
		//region Add letter & Add to segment
		if (!$isMyCompanyMode)
		{
			Integration\Sender\GridPanel::appendActions($actionList, $applyButton, $gridManagerID);
		}

		//endregion
		//region Add Task
		if (!$isMyCompanyMode && IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_COMPANY_TASK'),
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
			'NAME' => GetMessage('CRM_COMPANY_ASSIGN_TO'),
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
		if(!$isMyCompanyMode && IsModuleInstalled('voximplant'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_COMPANY_CREATE_CALL_LIST'),
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
			'NAME' => GetMessage('CRM_COMPANY_ACTION_MERGE'),
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

		$actionList[] = array(
			'NAME' => GetMessage('CRM_COMPANY_ACTION_DELETE'),
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

	if(!$isMyCompanyMode && $allowWrite)
	{
		//region Edit Button
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getEditButton();
		$actionList[] = $snippet->getEditAction();
		//endregion
		//region Mark as Opened
		$actionList[] = array(
			'NAME' => GetMessage('CRM_COMPANY_MARK_AS_OPENED'),
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
	}

	if($callListUpdateMode)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = array(
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_COMPANY_UPDATE_CALL_LIST"),
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
		//region Start call list
		if(IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_COMPANY_START_CALL_LIST'),
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
		'ENTITY_TYPE_NAME' => CCrmOwnerType::CompanyName,
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
		'HIDE_FILTER' => isset($arParams['HIDE_FILTER']) ? $arParams['HIDE_FILTER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_PARAMS' => array(
			'LAZY_LOAD' => array(
				'GET_LIST' => '/bitrix/components/bitrix/crm.company.list/filter.ajax.php?action=list&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'GET_FIELD' => '/bitrix/components/bitrix/crm.company.list/filter.ajax.php?action=field&filter_id='.urlencode($arResult['GRID_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
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
		'NAVIGATION_BAR' => $isMyCompanyMode ? null :
			array(
				'ITEMS' => array(
					array(
						//'icon' => 'table',
						'id' => 'list',
						'name' => GetMessage('CRM_COMPANY_LIST_FILTER_NAV_BUTTON_LIST'),
						'active' => true,
						'url' => $arParams['PATH_TO_COMPANY_LIST']
					),
				),
				'BINDING' => array(
					'category' => 'crm.navigation',
					'name' => 'index',
					'key' => mb_strtolower($arResult['NAVIGATION_CONTEXT_ID'])
				)
			),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::CompanyName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.company.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_COMPANY_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_COMPANY_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_COMPANY_DELETE')
			)
		)
	),
	$component
);
?><script type="text/javascript">
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
				BX.Crm.Activity.Planner.Manager.setCallback('onAfterActivitySave', function()
				{
					BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
				});
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
				startButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_COMPANY_LRP_DLG_REQUEST_ERR')?>"
			};

			var gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";
			BX.Crm.BatchDeletionManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Company?>,
					container: "batchDeletionWrapper",
					stateTemplate: "<?=GetMessageJS('CRM_COMPANY_STEPWISE_STATE_TEMPLATE')?>",
					messages:
						{
							title: "<?=GetMessageJS('CRM_COMPANY_LIST_DEL_PROC_DLG_TITLE')?>",
							confirmation: "<?=GetMessageJS('CRM_COMPANY_LIST_DEL_PROC_DLG_SUMMARY')?>",
							summaryCaption: "<?=GetMessageJS('CRM_COMPANY_BATCH_DELETION_COMPLETED')?>",
							summarySucceeded: "<?=GetMessageJS('CRM_COMPANY_BATCH_DELETION_COUNT_SUCCEEDED')?>",
							summaryFailed: "<?=GetMessageJS('CRM_COMPANY_BATCH_DELETION_COUNT_FAILED')?>"
						}
				}
			);

			BX.Crm.BatchMergeManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Company?>,
					mergerUrl: "<?=\CUtil::JSEscape($arParams['PATH_TO_COMPANY_MERGE'])?>"
				}
			);

			BX.Crm.AnalyticTracker.config =
				{
					id: "company_list",
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
	<script type="text/javascript">
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
<?if($arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script type="text/javascript">
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
	<script type="text/javascript">
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
<script type="text/javascript">
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
<script type="text/javascript">
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
if($arResult['NEED_TO_CONVERT_ADDRESSES'])
{?>
<script type="text/javascript">
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

if($arResult['NEED_TO_CONVERT_UF_ADDRESSES'])
{?>
<script type="text/javascript">
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

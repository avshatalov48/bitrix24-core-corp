<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Activity\Provider\ProviderManager;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;

UI\Extension::load(["ui.tooltip", "ui.fonts.opensans", 'crm.autorun']);

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/crm-entity-show.css");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
if (CModule::IncludeModule('bitrix24') && !\Bitrix\Crm\CallList\CallList::isAvailable())
{
	CBitrix24::initLicenseInfoPopupJS();
}

$isInternal = $arResult['IS_INTERNAL'];
$currentUserID = $arResult['CURRENT_USER_ID'] ;

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'MY_ACTIVITY',
			'ACTIVE_ITEM_ID' => 'MY_ACTIVITY',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

$gridManagerID = $arResult['UID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'ACTIVITY',
	'gridId' => $arResult['UID'],
	'formName' => "form_{$arResult['UID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['UID']}",
	'filterFields' => array()
);

$arResult['PREFIX'] = isset($arResult['PREFIX']) ? strval($arResult['PREFIX']) : 'activity_list';
$gridEditorID = $arResult['PREFIX'].'_crm_activity_grid_editor';
$editorItems = array();
$isEditable = !$arResult['READ_ONLY'];

$arResult['GRID_DATA'] = array();

$dateTimeOptions = array('TIME_FORMAT' => '<span class="crm-activity-time">#TIME#</span>');
foreach($arResult['ITEMS'] as &$item)
{
	$provider = CCrmActivity::GetActivityProvider($item);
	// Preparing of grid row -->
	$openViewJS = "BX.CrmActivityEditor.items['{$gridEditorID}'].viewActivity({$item['ID']}, {});";
	$arActions = array(
		array(
			'TITLE' => GetMessage('CRM_ACTION_SHOW'),
			'TEXT' => GetMessage('CRM_ACTION_SHOW'),
			'ONCLICK' => $openViewJS,
			'DEFAULT' => true
		)
	);

	$itemTypeID = intval($item['TYPE_ID']);

	if($isEditable)
	{
		if (
			$item['CAN_EDIT']
			&& (
				$itemTypeID === CCrmActivityType::Call || $itemTypeID === CCrmActivityType::Meeting
				|| (
					$itemTypeID === CCrmActivityType::Provider
					&& $provider
					&& $provider::isTypeEditable($item['PROVIDER_TYPE_ID'], $item['DIRECTION'])
					&& $provider::isActivityEditable($item, $currentUserID)
				)
			)
		)
		{
			$arActions[] = [
				'TITLE' => GetMessage('CRM_ACTION_EDIT'),
				'TEXT' => GetMessage('CRM_ACTION_EDIT'),
				'ONCLICK' => $provider::isTask()
					? (new $provider())->getEditAction($item['ID'], $currentUserID)
					: "(new BX.Crm.Activity.Planner()).showEdit({ID:{$item['ID']}});",
			];
		}

		if($item['CAN_COMPLETE'] && $itemTypeID !== CCrmActivityType::Email) //Email is always COMPLETED
		{
			if(isset($item['COMPLETED'])
				&& $item['COMPLETED'] === 'Y')
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_ACTION_MARK_AS_NOT_COMPLETED_2'),
					'TEXT' => GetMessage('CRM_ACTION_MARK_AS_NOT_COMPLETED_2'),
					'ONCLICK' => "BX.CrmActivityEditor.items['{$gridEditorID}'].setActivityCompleted({$item['ID']}, false);",
				);
			}
			else
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_ACTION_MARK_AS_COMPLETED_2'),
					'TEXT' => GetMessage('CRM_ACTION_MARK_AS_COMPLETED_2'),
					'ONCLICK' => "BX.CrmActivityEditor.items['{$gridEditorID}'].setActivityCompleted({$item['ID']}, true);",
				);
			}
		}

		if($item['CAN_DELETE'])
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_ACTION_DELETE'),
				'TEXT' => GetMessage('CRM_ACTION_DELETE'),
				'ONCLICK' => "BX.CrmActivityEditor.items['{$gridEditorID}'].deleteActivity({$item['ID']}, false);",
			);
		}

		$eventParam = array(
			'ID' => $item['ID'],
		);
		foreach(GetModuleEvents('crm', 'onCrmActivityListItemBuildMenu', true) as $event)
		{
			ExecuteModuleEventEx($event, array('CRM_ACTIVITY_LIST_MENU', $eventParam, &$arActions));
		}
	}

	$typeID = isset($item['~TYPE_ID']) ? intval($item['~TYPE_ID']) : CCrmActivityType::Undefined;
	$direction = isset($item['~DIRECTION']) ? intval($item['~DIRECTION']) : CCrmActivityDirection::Undefined;
	$typeClassName = '';
	$typeTitle = '';
	if($typeID === CCrmActivityType::Meeting):
		$typeClassName = 'crm-activity-meeting';
		$typeTitle = GetMessage('CRM_ACTION_TYPE_MEETING');
	elseif($typeID === CCrmActivityType::Call):
		if($direction === CCrmActivityDirection::Outgoing):
			$typeClassName = 'crm-activity-call-outgoing';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_CALL_OUTGOING');
		else:
			$typeClassName = 'crm-activity-call-incoming';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_CALL_INCOMING');
		endif;
	elseif($typeID === CCrmActivityType::Email):
		if($direction === CCrmActivityDirection::Outgoing):
			$typeClassName = 'crm-activity-email-outgoing';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_EMAIL_OUTGOING');
		else:
			$typeClassName = 'crm-activity-email-incoming';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_EMAIL_INCOMING');
		endif;
	elseif($typeID === CCrmActivityType::Task):
		$typeClassName = 'crm-activity-task';
		$typeTitle = GetMessage('CRM_ACTION_TYPE_TASK');
	elseif($typeID === CCrmActivityType::Provider && $provider !== null):
		$typeTitle = $provider::getTypeName($item['PROVIDER_TYPE_ID'], $item['DIRECTION']);
		if (
			isset($item['ORIGINATOR_ID']) &&
			in_array($item['ORIGINATOR_ID'], array('BITRIX', 'WORDPRESS', 'DRUPAL', 'JOOMLA', 'MAGENTO'))
		)
		{
			$typeClassName = 'crm-activity-crm_external_channel_cms';
		}
		elseif (isset($item['PROVIDER_ID']))
		{
			$typeClassName = 'crm-activity-'.mb_strtolower($item['PROVIDER_ID']);
			if (
				isset($item['DIRECTION']) &&
				(
					$item['DIRECTION'] == CCrmActivityDirection::Incoming ||
					$item['DIRECTION'] == CCrmActivityDirection::Outgoing
				)
			)
			{
				$typeClassName .= ' ' . $typeClassName
					.'-'.($item['DIRECTION'] == CCrmActivityDirection::Incoming ? 'incoming' : 'outgoing');
			}
		}
	endif;

	$subject = ($item['~SUBJECT'] ?? '');
	if ($subject === '' && $provider)
	{
		$subject = $provider::getActivityTitle($item);
	}

	if($subject !== '')
	{
		$typeTitle = "{$typeTitle}. {$subject}";
	}

	$typeTitle = htmlspecialcharsbx($typeTitle);

	$filteredSubject = ($item['SUBJECT'] ?? '');
	if ($filteredSubject === '' && $provider)
	{
		$filteredSubject = $provider::getActivityTitle($item);
	}

	$subjectHtml = '<div title="'.$typeTitle.'" class="crm-activity-info '.$typeClassName.'"><a alt="'.$typeTitle.'" class="crm-activity-subject" href="#" onclick="'.htmlspecialcharsbx($openViewJS).' return false;">' . $filteredSubject . '</a>';

	$priority = isset($item['~PRIORITY']) ? intval($item['~PRIORITY']) : CCrmActivityPriority::None;
	if($priority === CCrmActivityPriority::High)
	{
		$subjectHtml .= '<div class="crm-activity-important" title="'.htmlspecialcharsbx(GetMessage('CRM_ACTION_IMPORTANT')).'"></div>';
	}
	$subjectHtml .= '</div>';

	$completed = isset($item['~COMPLETED'])? mb_strtoupper($item['~COMPLETED']) : 'N';
	if($completed === 'Y'):
		$completedClassName = 'crm-activity-completed';
		$completedTitle = GetMessage('CRM_ACTION_COMPLETED');
		$completedOnClick = 'return false;';
	else:
		$completedClassName = 'crm-activity-not-completed';
		$completedTitle = GetMessage($item['CAN_COMPLETE'] ? 'CRM_ACTION_CLICK_TO_COMPLETE' : 'CRM_ACTION_NOT_COMPLETED');
		$completedOnClick = $item['CAN_COMPLETE'] ? 'BX.CrmActivityEditor.items[\''.$gridEditorID.'\'].setActivityCompleted('.$item['ID'].', true); return false;' : 'return false;';
	endif;

	$completedHtml = '<a class="'.$completedClassName.'" title="'.$completedTitle.'" alt="'.$completedTitle.'" href="#" onclick="'.$completedOnClick.'"></a>';
	$description = isset($item['DESCRIPTION_RAW']) ? $item['DESCRIPTION_RAW'] : '';

	$enableDescriptionCut = isset($item['ENABLE_DESCRIPTION_CUT']) ? $item['ENABLE_DESCRIPTION_CUT'] : false;
	if($enableDescriptionCut && mb_strlen($description) > 256)
	{
		$description = mb_substr($description, 0, 256).'<a href="#" onclick="BX.CrmActivityEditor.items[\''.$gridEditorID.'\'].viewActivity('.$item['ID'].', {}); return false;">...</a>';
	}

	$arRowData =
		array(
			'id' => $item['~ID'],
			'actions' => $arActions,
			'data' => $item,
			'editable' => $isEditable,
			'columnClasses' => array('COMPLETED' => 'bx-minimal'),
			'columns' => array(
				'SUBJECT'=> $subjectHtml,
				'RESPONSIBLE_FULL_NAME' => $item['~RESPONSIBLE_FULL_NAME'] !== '' ?
					'<a href="'.htmlspecialcharsbx($item['PATH_TO_RESPONSIBLE']).'" id="balloon_'.$arResult['GRID_ID'].'_'.$item['ID'].'" bx-tooltip-user-id="'.$item['RESPONSIBLE_ID'].'">'.htmlspecialcharsbx($item['~RESPONSIBLE_FULL_NAME']).'</a>'
					: '',
				'CREATED' => isset($item['~CREATED']) ? '<span class="crm-activity-date-time">'.FormatDate('SHORT', MakeTimeStamp($item['~CREATED'])).'</span>' : '',
				'START_TIME' => isset($item['~START_TIME']) && $item['~START_TIME'] !== '' ? '<span class="crm-activity-date-time">'.CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($item['~START_TIME'])), $dateTimeOptions).'</span>' : '',
				'END_TIME' => isset($item['~END_TIME']) && $item['~END_TIME'] !== '' ? '<span class="crm-activity-date-time">'.CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($item['~END_TIME'])), $dateTimeOptions).'</span>' : '',
				'DEADLINE' => isset($item['~DEADLINE']) && $item['~DEADLINE'] !== '' ? '<span class="crm-activity-date-time">'.CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($item['~DEADLINE'])), $dateTimeOptions).'</span>' : '',
				'COMPLETED' => $completedHtml,
				'DESCRIPTION' => $description
				)
		);

	$ownerTypeID = isset($item['OWNER_TYPE_ID']) ? intval($item['OWNER_TYPE_ID']) : 0;
	$ownerID = isset($item['OWNER_ID']) ? intval($item['OWNER_ID']) : 0;
	$ownerInfo = null;
	if (
		$ownerID > 0
		&& (
			$ownerTypeID === CCrmOwnerType::Deal
			|| $ownerTypeID === CCrmOwnerType::Lead
			|| $ownerTypeID === CCrmOwnerType::Quote
			|| \CCrmOwnerType::isUseDynamicTypeBasedApproach($ownerTypeID)
		)
		&& isset($arResult['OWNER_INFOS'][$ownerTypeID])
		&& isset($arResult['OWNER_INFOS'][$ownerTypeID][$ownerID])
	)
	{
		$ownerInfo = $arResult['OWNER_INFOS'][$ownerTypeID][$ownerID];
		$showPath = isset($ownerInfo['SHOW_URL']) ? $ownerInfo['SHOW_URL'] : '';
		$title = isset($ownerInfo['TITLE']) ? $ownerInfo['TITLE'] : '';
		if($showPath !== '' && $title !== '')
		{
			$arRowData['columns']['REFERENCE'] = '<a target="_self" href="'.htmlspecialcharsbx($showPath).'">'.htmlspecialcharsbx($title).'</a>';
		}
	}

	$commLoaded = isset($item['COMMUNICATIONS_LOADED']) ? $item['COMMUNICATIONS_LOADED'] : true;
	$communications = $commLoaded && isset($item['COMMUNICATIONS']) ? $item['COMMUNICATIONS'] : array();

	if($arResult['DISPLAY_CLIENT'])
	{
		$columnHtml = '';
		$clientInfo = isset($item['CLIENT_INFO']) ? $item['CLIENT_INFO'] : null;
		if(is_array($clientInfo))
		{
			$columnHtml= CCrmViewHelper::PrepareEntityBaloonHtml(
				array(
					'ENTITY_TYPE_ID' => $clientInfo['ENTITY_TYPE_ID'],
					'ENTITY_ID' => $clientInfo['ENTITY_ID'],
					'PREFIX' => "{$arResult['UID']}_{$item['~ID']}_CLIENT",
					'TITLE' => isset($clientInfo['TITLE']) ? $clientInfo['TITLE'] : '',
					'SHOW_URL' => isset($clientInfo['SHOW_URL']) ? $clientInfo['SHOW_URL'] : ''
				)
			);
		}
		$arRowData['columns']['CLIENT'] = $columnHtml;
	}

	$arResult['GRID_DATA'][] = $arRowData;
	// <-- Preparing grig row

	// Preparing activity editor item -->
	$commData = array();
	if(!empty($communications))
	{
		foreach($communications as &$arComm)
		{
			CCrmActivity::PrepareCommunicationInfo($arComm);
			$commData[] = array(
				'id' => $arComm['ID'],
				'type' => $arComm['TYPE'],
				'value' => $arComm['VALUE'],
				'entityId' => $arComm['ENTITY_ID'],
				'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
				'entityTitle' => $arComm['TITLE'],
				'entityUrl' => CCrmOwnerType::GetEntityShowPath($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
			);
		}
		unset($arComm);
	}

	$responsibleID = isset($item['~RESPONSIBLE_ID']) ? intval($item['~RESPONSIBLE_ID']) : 0;
	$responsibleUrl = isset($item['PATH_TO_RESPONSIBLE']) ? $item['PATH_TO_RESPONSIBLE'] : '';
	if($responsibleUrl === '')
	{
		$responsibleUrl = CComponentEngine::MakePathFromTemplate(
			$arResult['PATH_TO_USER_PROFILE'],
			array('user_id' => $responsibleID)
		);
	}

	$editorItem = array(
		'ID' => $item['~ID'],
		'typeID' => $item['~TYPE_ID'],
		'providerID' => $item['~PROVIDER_ID'],
		'subject' => $item['~SUBJECT'],
		'description' => isset($item['DESCRIPTION_RAW']) ? $item['DESCRIPTION_RAW'] : '',
		'descriptionBBCode' => isset($item['DESCRIPTION_BBCODE']) ? $item['DESCRIPTION_BBCODE'] : '',
		'descriptionHtml' => isset($item['DESCRIPTION_HTML']) ? $item['DESCRIPTION_HTML'] : '',
		'direction' => intval($item['~DIRECTION']),
		'location' => $item['~LOCATION'] ?? null,
		'start' => isset($item['~START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($item['~START_TIME']), 'FULL', SITE_ID) : '',
		'end' => isset($item['~END_TIME']) ? ConvertTimeStamp(MakeTimeStamp($item['~END_TIME']), 'FULL', SITE_ID) : '',
		'deadline' => isset($item['~DEADLINE']) ? ConvertTimeStamp(MakeTimeStamp($item['~DEADLINE']), 'FULL', SITE_ID) : '',
		'completed' => $item['~COMPLETED'] == 'Y',
		'notifyType' => intval($item['~NOTIFY_TYPE'] ?? 0),
		'notifyValue' => intval($item['~NOTIFY_VALUE'] ?? 0),
		'priority' => intval($item['~PRIORITY'] ?? 0),
		'responsibleID' => $responsibleID,
		'responsibleName' => isset($item['~RESPONSIBLE_FULL_NAME'][0]) ? $item['~RESPONSIBLE_FULL_NAME'] : GetMessage('CRM_UNDEFINED_VALUE'),
		'responsibleUrl' =>  $responsibleUrl,
		'storageTypeID' => intval($item['STORAGE_TYPE_ID'] ?? 0),
		'files' => isset($item['FILES']) ? $item['FILES'] : array(),
		'webdavelements' => isset($item['WEBDAV_ELEMENTS']) ? $item['WEBDAV_ELEMENTS'] : array(),
		'diskfiles' => isset($item['DISK_FILES']) ? $item['DISK_FILES'] : array(),
		'associatedEntityID' => isset($item['~ASSOCIATED_ENTITY_ID']) ? intval($item['~ASSOCIATED_ENTITY_ID']) : 0,
		'customViewLink' => (($provider && !is_null($provider::getCustomViewLink($item))) ? $provider::getCustomViewLink($item) : ''),
	);
	if (
		$item['~PROVIDER_ID'] === \Bitrix\Crm\Activity\Provider\ConfigurableRestApp::getId()
		&& ($item['PROVIDER_PARAMS']['clientId'] ?? null)
		&& \Bitrix\Main\Loader::includeModule('rest')
	)
	{
		$app = \Bitrix\Rest\AppTable::getByClientId($item['PROVIDER_PARAMS']['clientId']);
		$editorItem['associatedEntityID'] = (int)($app['ID'] ?? 0);
	}

	if(!$commLoaded)
	{
		$editorItem['communicationsLoaded'] = false;
	}
	else
	{
		$editorItem['communicationsLoaded'] = true;
		$editorItem['communications'] = $commData;
	}

	if($ownerID > 0 && $ownerTypeID > 0)
	{
		$editorItem['ownerType'] = CCrmOwnerType::ResolveName($ownerTypeID);
		$editorItem['ownerID'] = $ownerID;
		if(is_array($ownerInfo))
		{
			$editorItem['ownerTitle'] = isset($ownerInfo['TITLE']) ? $ownerInfo['TITLE'] : '';
			$editorItem['ownerUrl'] = isset($ownerInfo['SHOW_URL']) ? $ownerInfo['SHOW_URL'] : '';
		}
	}

	$editorItems[] = $editorItem;
	// <-- Preparing activity editor item
}
unset($item);

?><div id="rebuildMessageWrapper"><?
if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'])
{
	?><div id="rebuildActivitySearchWrapper"></div><?
}
if($arResult['NEED_FOR_BUILD_TIMELINE'])
{
	?><div id="buildActivityTimelineWrapper"></div><?
}
if($arResult['NEED_FOR_CONVERTING_OF_CALENDAR_EVENTS'])
{
	?><div class="crm-view-message"><?= GetMessage('CRM_ACTION_CONVERTING_OF_CALENDAR_EVENTS', array('#URL_EXECUTE_CONVERTING#' => htmlspecialcharsbx($arResult['CAL_EVENT_CONV_EXEC_URL']), '#URL_SKIP_CONVERTING#' => htmlspecialcharsbx($arResult['CAL_EVENT_CONV_SKIP_URL']))) ?></div><?
}
if($arResult['NEED_FOR_CONVERTING_OF_TASKS'])
{
	?><div class="crm-view-message"><?= GetMessage('CRM_ACTION_CONVERTING_OF_TASKS', array('#URL_EXECUTE_CONVERTING#' => htmlspecialcharsbx($arResult['TASK_CONV_EXEC_URL']), '#URL_SKIP_CONVERTING#' => htmlspecialcharsbx($arResult['TASK_CONV_SKIP_URL']))) ?></div><?
}
?></div><?

$enableToolbar = $arResult['ENABLE_TOOLBAR'];
$toolbarID = mb_strtolower("{$gridEditorID}_toolbar");
$useQuickFilter = $arResult['USE_QUICK_FILTER'];

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	array(
		'CONTAINER_ID' => '',
		'EDITOR_ID' => $gridEditorID,
		'EDITOR_TYPE' => 'MIXED',
		'PREFIX' => $arResult['PREFIX'],
		'OWNER_TYPE' => $arResult['OWNER_TYPE'],
		'OWNER_ID' => $arResult['OWNER_ID'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'ENABLE_UI' => false,
		'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK_ADD'],
		'ENABLE_CALENDAR_EVENT_ADD' => $arResult['ENABLE_CALENDAR_EVENT_ADD'],
		'ENABLE_EMAIL_ADD' => $arResult['ENABLE_EMAIL_ADD'],
		'ENABLE_TOOLBAR' => $enableToolbar,
		'TOOLBAR_ID' => $toolbarID,
		'FORM_ID' => $arResult['FORM_ID'],
		'EDITOR_ITEMS' => $editorItems,
		'DISABLE_STORAGE_EDIT' => isset($arResult['DISABLE_STORAGE_EDIT']) && $arResult['DISABLE_STORAGE_EDIT'],
		'SKIP_VISUAL_COMPONENTS' => 'Y'
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

$prefix = $arResult['GRID_ID'];

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if(!$isInternal && $isEditable)
{
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

	$actionList = array(array('NAME' => GetMessage('CRM_ACTIVITY_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));
	$actionList[] = $snippet->getRemoveAction();
	$actionList[] = $snippet->getEditAction();

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getEditButton();
	//region Action Button

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
			'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'] ?? null
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	}
	//endregion
	$actionList[] = array(
		'NAME' => GetMessage('CRM_ACTION_ASSIGN_TO'),
		'VALUE' => 'assign_to',
		'ONCHANGE' => array(
			array(
				'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
				'DATA' => array(
					array(
						'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
						'ID' => 'action_responsible_search',
						'NAME' => 'ACTION_RESPONSIBLE_SEARCH'
					),
					array(
						'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
						'ID' => 'action_responsible_id',
						'NAME' => 'ACTION_RESPONSIBLE_ID'
					),
					$applyButton
				)
			),
			array(
				'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => array(
					array('JS' => "BX.CrmUIGridExtension.prepareAction('{$gridManagerID}', 'assign_to',  { searchInputId: 'action_responsible_search_control', dataInputId: 'action_responsible_id_control', componentName: '{$prefix}_ACTION_ASSIGNED_BY' })")
				)
			),
			array(
				'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
				'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'assign_to')"))
			)
		)
	);
	//endregion

	//region Mark as completed
	$actionList[] = array(
		'NAME' => GetMessage('CRM_ACTION_MARK_AS_COMPLETED_2'),
		'VALUE' => 'mark_as_completed',
		'ONCHANGE' => array(
			array(
				'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
				'DATA' => array($applyButton)
			)
		)
	);
	//endregion

	//region Mark as not completed
	$actionList[] = array(
		'NAME' => GetMessage('CRM_ACTION_MARK_AS_NOT_COMPLETED_2'),
		'VALUE' => 'mark_as_not_completed',
		'ONCHANGE' => array(
			array(
				'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
				'DATA' => array($applyButton)
			)
		)
	);
	//endregion

	$controlPanel['GROUPS'][0]['ITEMS'][] = array(
		"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
		"ID" => "action_button_{$prefix}",
		"NAME" => "action_button_{$prefix}",
		"ITEMS" => $actionList
	);
	//endregion

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}
//endregion

if(!$isInternal && ! $useQuickFilter)
{
	$APPLICATION->ShowViewContent('crm-grid-filter');
}

if($enableToolbar && $arResult['ENABLE_CREATE_TOOLBAR_BUTTON'])
{
	$isSingleButtonMode = SITE_TEMPLATE_ID === 'bitrix24' && !$isInternal;
	$toolbarButtons = array();
	if($isEditable && $arResult['OWNER_TYPE'] !== '' && $arResult['OWNER_ID'] !== '')
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_EVENT_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_EVENT'),
			'ICON' => 'btn-new crm-activity-command-add-event',
			'ONCLICK' => $isSingleButtonMode ? 'BX.CrmActivityEditor.items["'.CUtil::JSEscape($gridEditorID).'"].addEvent();' : ''
		);
	}

	if($arResult['ENABLE_TASK_ADD'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_TASK_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_TASK'),
			'ICON' => 'btn-new crm-activity-command-add-task',
			'ONCLICK' => $isSingleButtonMode ? 'BX.CrmActivityEditor.items["'.CUtil::JSEscape($gridEditorID).'"].addTask();' : '',
			'CLASS_NAME' => RestrictionManager::getTaskRestriction()->hasPermission() ? '' : 'crm-tariff-lock-behind',
		);
	}

	if($arResult['ENABLE_CALENDAR_EVENT_ADD'])
	{
		$addedQty = ProviderManager::prepareToolbarButtons(
			$toolbarButtons,
			array(
				'UID' => $arResult['UID'],
				'OWNER_TYPE_ID' => $arResult['OWNER_TYPE_ID'],
				'OWNER_ID' => $arResult['OWNER_ID'],
			)
		);

		if($addedQty == 0)
		{
			$toolbarButtons[] = array(
				'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_CALL_SHORT'),
				'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_CALL'),
				'ICON' => 'btn-new crm-activity-command-add-call',
				'ONCLICK' => $isSingleButtonMode ? 'BX.CrmActivityEditor.items["'.CUtil::JSEscape($gridEditorID).'"].addCall();' : ''
			);

			$toolbarButtons[] = array(
				'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_MEETING_SHORT'),
				'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_MEETING'),
				'ICON' => 'btn-new crm-activity-command-add-meeting',
				'ONCLICK' => $isSingleButtonMode ? 'BX.CrmActivityEditor.items["'.CUtil::JSEscape($gridEditorID).'"].addMeeting();' : ''
			);
		}
	}

	if($isSingleButtonMode)
	{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
			'title',
			array(
				'TOOLBAR_ID' => $toolbarID,
				'BUTTONS' => array(
					array(
						'TEXT' => GetMessage('CRM_ACTIVITY_LIST_CREATE'),
						'TYPE' => 'crm-context-menu',
						'ITEMS' => $toolbarButtons
					)
				)
			),
			$component,
			array('HIDE_ICONS' => 'Y')
		);
	}
	else
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.interface.toolbar',
			'',
			array(
				'TOOLBAR_ID' => $toolbarID,
				'BUTTONS' => $toolbarButtons
			),
			$component,
			array('HIDE_ICONS' => 'Y')
		);
	}
}

if($useQuickFilter)
{
	$APPLICATION->ShowViewContent('crm-quick-filter');
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	array(
		'GRID_ID' => $arResult['UID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'FORM_URI' => $arResult['FORM_URI'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'RENDER_FILTER_INTO_VIEW' => $useQuickFilter ? 'crm-quick-filter' : '',
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_LIVE_SEARCH' => true,
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAVIGATION_BAR' => [
			'ITEMS' => [
				/*[
					'icon' => 'table',
					'id' => 'kanban',
					'name' => Loc::getMessage('CRM_COMMON_KANBAN'),
					'active' => false,
					'url' => $arParams['PATH_TO_ACTIVITY_KANBAN'] ?? '',
				],*/
				[
					'icon' => 'table',
					'id' => 'list',
					'name' => Loc::getMessage('CRM_ACTIVITY_LIST_FILTER_NAV_BUTTON_LIST'),
					'active' => true,
					'url' => $arParams['PATH_TO_ACTIVITY_LIST']
				],
			],
			'BINDING' => [
				'category' => 'crm.navigation',
				'name' => 'index',
				'key' => mb_strtolower($arResult['NAVIGATION_CONTEXT_ID']),
			],
		],
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::ActivityName,
				'gridId' => $arResult['UID'],
				'serviceUrl' => '/bitrix/components/bitrix/crm.activity.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			)
		)
	),
	$component
);

?><script>
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
				{
					startButton: "<?=GetMessageJS('CRM_LRP_DLG_BTN_START')?>",
					stopButton: "<?=GetMessageJS('CRM_LRP_DLG_BTN_STOP')?>",
					closeButton: "<?=GetMessageJS('CRM_LRP_DLG_BTN_CLOSE')?>",
					requestError: "<?=GetMessageJS('CRM_LRP_DLG_REQUEST_ERR')?>"
				};
		}
	);
</script><?

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
	<script>
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildActivitySearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
						title: "<?=GetMessageJS('CRM_ACTIVITY_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("rebuildActivitySearch",
						{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.activity.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildActivitySearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;
if($arResult['NEED_FOR_BUILD_TIMELINE']):?>
	<script>
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("buildActivityTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
				{
						title: "<?=GetMessageJS('CRM_ACTIVITY_BUILD_TIMELINE_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_ACTIVITY_BUILD_TIMELINE_STATE')?>"
				};
				var manager = BX.AutorunProcessManager.create("buildActivityTimeline",
						{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.activity.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "BUILD_TIMELINE",
						container: "buildActivityTimelineWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;

if ($arResult['SHOW_MISMATCH_NOTIFY']):?>
	<div class="crm-warning-message">
		<?=GetMessage("CRM_WIDGET_COUNTER_MISMATCH_NOTIFY")?>
	</div>
<?endif;

if(!$useQuickFilter):
?><script>
	BX.ready(
			function()
			{
				var editor = BX.CrmActivityEditor.items['<?= CUtil::JSEscape($gridEditorID)?>'];
				function reload()
				{
					BX.removeCustomEvent("tasksTaskEvent", reload);
					if(editor)
					{
						editor.setLocked(true);
						editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
						editor.release();
					}

					BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
				}

				BX.addCustomEvent(
					window,
					"CrmGridFilterApply",
					function()
					{
						if(editor)
						{
							editor.setLocked(true);
							editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
							editor.release();
						}
					}
				);

				BX.addCustomEvent("tasksTaskEvent", reload);
				//HACK: fix task popup overlay position & size
				BX.CrmActivityEditor.attachInterfaceGridReload();

				BX.Crm.Activity.Planner.Manager.setCallback(
					"onAfterActivitySave",
					function()
					{
						if(editor)
						{
							editor.setLocked(true);
							editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
							editor.release();
						}

						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					}
				);

				BX.CrmActivityVisit.setCallback("onVisitCreated", function()
				{
					var eventArgs = { cancel: false };
					BX.onCustomEvent("BeforeCrmActivityListReload", [eventArgs]);
					if(!eventArgs.cancel)
					{
						if(editor)
						{
							editor.setLocked(true);
							editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
							editor.release();
						}

						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					}
				});
			}
	);
</script><?
else:
?><script>
	BX.ready(
			function()
			{
				var editor = BX.CrmActivityEditor.items['<?= CUtil::JSEscape($gridEditorID)?>'];
				function reload()
				{
					BX.removeCustomEvent("tasksTaskEvent", reload);
					if(editor)
					{
						editor.setLocked(true);
						editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
						editor.release();
					}

					BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
				}

				editor.addActivityChangeHandler(
					function()
					{
						var eventArgs = { cancel: false };
						BX.onCustomEvent("BeforeCrmActivityListReload", [eventArgs]);

						if(!eventArgs.cancel)
						{
							editor.removeActivityChangeHandler(this);
							editor.lockAndRelease("<?=GetMessageJS('CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD')?>");
							BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
					}
				);

				BX.Crm.Activity.Planner.Manager.setCallback('onAfterActivitySave',function()
					{
						var eventArgs = { cancel: false };
						BX.onCustomEvent("BeforeCrmActivityListReload", [eventArgs]);

						if(!eventArgs.cancel)
						{

							if(editor)
							{
								editor.setLocked(true);
								editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
								editor.release();
							}
							BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
					}
				);

				BX.addCustomEvent(
					window,
					"BXInterfaceGridApplyFilter",
					function() { editor.lockAndRelease("<?=GetMessageJS('CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD')?>"); }
				);

				BX.addCustomEvent(
					window,
					'BXInterfaceGridBeforeReload',
					function() { editor.lockAndRelease("<?=GetMessageJS('CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD')?>"); }
				);

				BX.addCustomEvent("tasksTaskEvent", reload);
				//HACK: fix task popup overlay position & size
				BX.CrmActivityEditor.attachInterfaceGridReload();

				BX.CrmActivityVisit.setCallback("onVisitCreated", function()
				{
					var eventArgs = { cancel: false };
					BX.onCustomEvent("BeforeCrmActivityListReload", [eventArgs]);

					if(!eventArgs.cancel)
					{
						if(editor)
						{
							editor.setLocked(true);
							editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
							editor.release();
						}

						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					}
				});
			}
	);
</script><?
endif;

$openViewItemId = isset($arResult['OPEN_VIEW_ITEM_ID']) ? $arResult['OPEN_VIEW_ITEM_ID'] : 0;
$openEditItemId = isset($arResult['OPEN_EDIT_ITEM_ID']) ? $arResult['OPEN_EDIT_ITEM_ID'] : 0;
if($openViewItemId > 0):
?><script>
	BX.ready(
		function()
		{
			var editor = BX.CrmActivityEditor.items['<?=CUtil::JSEscape($gridEditorID)?>'];
			if(editor)
			{
				editor.viewActivity(<?=$openViewItemId?>);
			}
		}
	);
</script><?
elseif($openEditItemId > 0):
	?><script>
		BX.ready(
			function()
			{
				var editor = BX.CrmActivityEditor.items['<?=CUtil::JSEscape($gridEditorID)?>'];
				if(editor)
				{
					editor.editActivity(<?=$openEditItemId?>);
				}
			}
		);
	</script><?
endif;

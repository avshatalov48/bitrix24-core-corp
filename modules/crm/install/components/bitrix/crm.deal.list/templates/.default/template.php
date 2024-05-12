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

use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Component\EntityList\ActionManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\NavigationBarPanel;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
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

Extension::load(
	[
		'crm.merger.batchmergemanager',
		'ui.fonts.opensans',
	]
);

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/batch_deletion.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/partial_entity_editor.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');

?><div id="batchDeletionWrapper"></div>
<?
echo \Bitrix\Crm\Update\Order\DealGenerator::getHtml();
echo (\Bitrix\Crm\Tour\NumberOfClients::getInstance())->build();
?>
<div id="rebuildMessageWrapper"><?

	if (!empty($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']))
	{
		?><div id="rebuildDealSearchWrapper"></div><?
	}

	if (!empty($arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT']))
	{
		?><div id="buildTimelineSearchWrapper"></div><?
	}

	if (!empty($arResult['NEED_FOR_BUILD_TIMELINE']))
	{
		?><div id="buildDealTimelineWrapper"></div><?
	}

	if (!empty($arResult['NEED_FOR_REFRESH_ACCOUNTING']))
	{
		?><div id="refreshDealAccountingWrapper"></div><?
	}

	if (!empty($arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS']))
	{
		?><div id="rebuildDealSemanticsWrapper"></div><?
	}

	if (!empty($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS']))
	{
		?><div id="rebuildDealSecurityAttrsWrapper"></div><?
	}

	if (!empty($arResult['NEED_FOR_REBUILD_DEAL_ATTRS']))
	{
		?><div id="rebuildDealAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_DEAL_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildDealAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
		</div><?
	}
	?></div><?

$isRecurring = isset($arParams['IS_RECURRING']) && $arParams['IS_RECURRING'] === 'Y';
$isInternal = $arResult['INTERNAL'];
$callListUpdateMode = $arResult['CALL_LIST_UPDATE_MODE'];
$allowWrite = $arResult['PERMS']['WRITE'];
$allowDelete = $arResult['PERMS']['DELETE'];
$allowExclude = $arResult['CAN_EXCLUDE'];
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if (!$isInternal)
{
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'DEAL',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'SKIP_VISUAL_COMPONENTS' => 'Y'
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$preparedGridId = htmlspecialcharsbx(CUtil::JSescape($gridManagerID));
$gridManagerCfg = array(
	'ownerType' => 'DEAL',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => []
);

echo CCrmViewHelper::RenderDealStageSettings($arParams['CATEGORY_ID'] ?? null);

$prefix = $arResult['GRID_ID'] ?? '';
$prefixLC = mb_strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = [];
$arColumns = [];
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

$now = time() + CTimeZone::GetOffset();

$fieldContentTypeMap = \Bitrix\Crm\Model\FieldContentTypeTable::loadForMultipleItems(\CCrmOwnerType::Deal, array_keys($arResult['DEAL']));

if ($arResult['NEED_ADD_ACTIVITY_BLOCK'] ?? false)
{
	$arResult['DEAL'] = (new \Bitrix\Crm\Component\EntityList\NearestActivity\Manager(CCrmOwnerType::Deal))->appendNearestActivityBlock($arResult['DEAL']);
}

/** @var \Bitrix\Crm\Conversion\EntityConversionConfig $conversionConfig */
$conversionConfig = $arResult['CONVERSION_CONFIG'] ?? null;

foreach ($arResult['DEAL'] as $sKey =>  $arDeal)
{
	$jsTitle = isset($arDeal['~TITLE']) ? CUtil::JSEscape($arDeal['~TITLE']) : '';
	$jsShowUrl = isset($arDeal['PATH_TO_DEAL_SHOW']) ? CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW']) : '';

	$arActivityMenuItems = [];
	$arActivitySubMenuItems = [];
	$arActions = [];

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_DEAL_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_DEAL_SHOW'),
		'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW'])."')",
		'DEFAULT' => true
	);

	if ($arDeal['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_EDIT'),
			'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arDeal['PATH_TO_DEAL_EDIT'])."')"
		);

		if ($arParams['IS_RECURRING'] !== 'Y')
		{
			$arActions[] = array(
				'TITLE' => GetMessage('CRM_DEAL_COPY_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_COPY'),
				'ONCLICK' => "BX.Crm.Page.open('".CUtil::JSEscape($arDeal['PATH_TO_DEAL_COPY'])."')"
			);
		}
	}

	if (!$isInternal && $arDeal['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arDeal['PATH_TO_DEAL_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}',
				BX.CrmUIGridMenuCommand.remove,
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	if ($allowExclude && $arDeal['CAN_EXCLUDE'])
	{
		$pathToExclude = CUtil::JSEscape($arDeal['PATH_TO_DEAL_EXCLUDE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_EXCLUDE_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_EXCLUDE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}',
					BX.CrmUIGridMenuCommand.exclude,
					{ pathToExclude: '{$pathToExclude}' }
				)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	if (!$isInternal && $arParams['IS_RECURRING'] !== 'Y')
	{
		if ($arResult['CAN_CONVERT'])
		{
			if ($arResult['CONVERSION_PERMITTED'] && $conversionConfig)
			{
				$arSchemeList = [];

				$toolsManager = Container::getInstance()->getIntranetToolsManager();
				$availabilityManager = AvailabilityManager::getInstance();

				foreach ($conversionConfig->getScheme()->getItems() as $item)
				{
					$entityTypeId = current($item->getEntityTypeIds());
					if ($toolsManager->checkEntityTypeAvailability($entityTypeId))
					{
						$onClick = sprintf(
							"BX.Crm.Conversion.Manager.Instance.getConverter('%s').convertBySchemeItemId('%s', %d);",
							\CUtil::JSEscape($arResult['CONVERTER_ID']),
							\CUtil::JSEscape($item->getId()),
							(int)$arDeal['ID']
						);
					}
					else
					{
						$onClick = $availabilityManager->getEntityTypeAvailabilityLock($entityTypeId);
					}

					$arSchemeList[] = [
						'TITLE' => $item->getPhrase(),
						'TEXT' => $item->getPhrase(),
						'ONCLICK' => $onClick,
					];
				}

				if (!empty($arSchemeList))
				{
					$arActions[] = array('SEPARATOR' => true);
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_DEAL_CREATE_ON_BASIS_TITLE_MSGVER_1'),
						'TEXT' => GetMessage('CRM_DEAL_CREATE_ON_BASIS'),
						'MENU' => $arSchemeList
					);
				}
			}
			else
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_DEAL_CREATE_ON_BASIS_TITLE_MSGVER_1'),
					'TEXT' => GetMessage('CRM_DEAL_CREATE_ON_BASIS'),
					'ONCLICK' => $arResult['CONVERSION_LOCK_SCRIPT'] ?? ''
				);
			}

			$arActions[] = array('SEPARATOR' => true);
		}

		if ($arDeal['EDIT'])
		{
			$baseCategoryId = (int)($arResult['CATEGORY_ID'] ?? 0);
			$dealCategoryId = $baseCategoryId === -1 ? (int)$arDeal['CATEGORY_ID'] : $baseCategoryId;

			$currentUser = CUtil::PhpToJSObject(CCrmViewHelper::getUserInfo(true, false));
			$pingSettings = CUtil::PhpToJSObject(
				(new TodoPingSettingsProvider(\CCrmOwnerType::Deal, $dealCategoryId))->fetchForJsComponent()
			);
			$arActivitySubMenuItems[] = [
				'TEXT' => GetMessage('CRM_DEAL_ADD_TODO'),
				'ONCLICK' => "BX.CrmUIGridExtension.showActivityAddingPopupFromMenu('".$preparedGridId."', " . CCrmOwnerType::Deal . ", " . (int)$arDeal['ID'] . ", " . $currentUser . ", " . $pingSettings . ");"
			];

			if (IsModuleInstalled(CRM_MODULE_CALENDAR_ID) && \Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled())
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);
			}

			if (IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}',
						BX.CrmUIGridMenuCommand.createActivity,
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);
			}

			if (!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_ACTIVITY_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}

			if ($arResult['IS_BIZPROC_AVAILABLE'])
			{
				$arActions[] = array('SEPARATOR' => true);
				if (isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_DEAL_BIZPROC_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_BIZPROC_LIST'])."');"
					);
				if (!empty($arDeal['BIZPROC_LIST']))
				{
					$arBizprocList = [];
					foreach ($arDeal['BIZPROC_LIST'] as $arBizproc)
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
						'TITLE' => GetMessage('CRM_DEAL_BIZPROC_LIST_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					);
				}
			}
		}
	}

	$eventParam = array(
		'ID' => $arDeal['ID'],
		'CALL_LIST_ID' => $arResult['CALL_LIST_ID'],
		'CALL_LIST_CONTEXT' => $arResult['CALL_LIST_CONTEXT'],
		'GRID_ID' => $arResult['GRID_ID']
	);

	foreach (GetModuleEvents('crm', 'onCrmDealListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_DEAL_LIST_MENU', $eventParam, &$arActions));
	}

	$bizprocStatus = empty($arDeal['BIZPROC_STATUS']) ? '' : 'bizproc bizproc_status_' . $arDeal['BIZPROC_STATUS'];
	$bizprocStatusHint = empty($arDeal['BIZPROC_STATUS_HINT'])
		? ''
		: 'onmouseover="BX.hint(this, \'' . CUtil::JSEscape($arDeal['BIZPROC_STATUS_HINT']) . '\');"';
	$title = '<a target="_self" href="' . $arDeal['PATH_TO_DEAL_SHOW'] . '" class="' . $bizprocStatus . '"' . $bizprocStatusHint . '>' . $arDeal['TITLE'] . '</a>';

	$dateCreate = $arDeal['DATE_CREATE'] ?? '';
	$dateModify = $arDeal['DATE_MODIFY'] ?? '';
	$webformId = null;
	if (isset($arDeal['WEBFORM_ID']))
	{
		$webformId = $arResult['WEBFORM_LIST'][$arDeal['WEBFORM_ID']] ?? $arDeal['WEBFORM_ID'];
	}

	$typeId = null;
	if (isset($arDeal['TYPE_ID']))
	{
		$typeId = $arResult['TYPE_LIST'][$arDeal['TYPE_ID']] ?? $arDeal['TYPE_ID'];
	}

	$sourceId = null;
	if (isset($arDeal['SOURCE_ID']))
	{
		$sourceId = $arResult['SOURCE_LIST'][$arDeal['SOURCE_ID']] ?? $arDeal['SOURCE_ID'];
	}

	$eventId = null;
	if (isset($arDeal['EVENT_ID']))
	{
		$eventId = $arResult['EVENT_LIST'][$arDeal['EVENT_ID']] ?? $arDeal['EVENT_ID'];
	}

	$stateId = null;
	if (isset($arDeal['STATE_ID']))
	{
		$stateId = $arResult['STATE_LIST'][$arDeal['STATE_ID']] ?? $arDeal['STATE_ID'];
	}

	$probability = isset($arDeal['PROBABILITY']) ? "{$arDeal['PROBABILITY']}%" : '';

	$resultItem = array(
		'id' => $arDeal['ID'],
		'actions' => $arActions,
		'data' => $arDeal,
		'editable' => !$arDeal['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
				'DEAL_SUMMARY' => CCrmViewHelper::RenderInfo(
					$arDeal['PATH_TO_DEAL_SHOW'] ?? '',
					($arDeal['TITLE_PREFIX'] ?? '') . ($arDeal['TITLE'] ?? ('[' . $arDeal['ID'] . ']')),
					Tracking\UI\Grid::enrichSourceName(
						\CCrmOwnerType::Deal,
						$arDeal['ID'],
						$arDeal['DEAL_DESCRIPTION']
					),
					[
						'TARGET' => '_top',
						'LEGEND' => $arDeal['DEAL_LEGEND'],
					]
				),
				'DEAL_CLIENT' => isset($arDeal['CLIENT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CLIENT_INFO']) : '',
				'COMPANY_ID' => isset($arDeal['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['COMPANY_INFO']) : '',
				'CONTACT_ID' => isset($arDeal['CONTACT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CONTACT_INFO']) : '',
				'TITLE' => $title,
				'CLOSED' => isset($arDeal['CLOSED']) && $arDeal['CLOSED'] === 'Y'
					? GetMessage('MAIN_YES')
					: GetMessage('MAIN_NO'),
				'ASSIGNED_BY' => isset($arDeal['~ASSIGNED_BY_ID']) && $arDeal['~ASSIGNED_BY_ID'] > 0
					? CCrmViewHelper::PrepareUserBaloonHtml(
						[
							'PREFIX' => "DEAL_{$arDeal['~ID']}_RESPONSIBLE",
							'USER_ID' => $arDeal['~ASSIGNED_BY_ID'],
							'USER_NAME'=> $arDeal['ASSIGNED_BY'],
							'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_PROFILE'],
						]
					) : '',
				'COMMENTS' => htmlspecialcharsback($arDeal['COMMENTS'] ?? ''),
				'SUM' => $arDeal['FORMATTED_OPPORTUNITY'],
				'OPPORTUNITY' => $arDeal['OPPORTUNITY'] ?? 0.0,
				'PROBABILITY' => $probability,
				'DATE_CREATE' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateCreate), $now),
				'DATE_MODIFY' => FormatDate($arResult['TIME_FORMAT'], MakeTimeStamp($dateModify), $now),
				'TYPE_ID' => $typeId,
				'SOURCE_ID' => $sourceId,
				'EVENT_ID' => $eventId,
				'CURRENCY_ID' => CCrmCurrency::GetEncodedCurrencyName($arDeal['CURRENCY_ID'] ?? null),
				'PRODUCT_ID' => isset($arDeal['PRODUCT_ROWS'])
					? htmlspecialcharsbx(CCrmProductRow::RowsToString($arDeal['PRODUCT_ROWS']))
					: '',
				'STATE_ID' => $stateId,
				'WEBFORM_ID' => $webformId,
				'PAYMENT_STAGE' => ($arDeal['PAYMENT_STAGE'] ?? ''),
				'DELIVERY_STAGE' => ($arDeal['DELIVERY_STAGE'] ?? ''),
				'STAGE_ID' => CCrmViewHelper::RenderDealStageControl(
					array(
						'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
						'ENTITY_ID' => $arDeal['~ID'],
						'CURRENT_ID' => $arDeal['~STAGE_ID'],
						'CATEGORY_ID' => $arDeal['~CATEGORY_ID'],
						'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php',
						'READ_ONLY' => !(isset($arDeal['EDIT']) && $arDeal['EDIT'] === true)
					)
				),
				'CATEGORY_ID' => $arDeal['DEAL_CATEGORY_NAME'],
				'IS_RETURN_CUSTOMER' => $arDeal['IS_RETURN_CUSTOMER'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
				'IS_REPEATED_APPROACH' => $arDeal['IS_REPEATED_APPROACH'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
				'ORIGINATOR_ID' => $arDeal['ORIGINATOR_NAME'] ?? '',
				'CREATED_BY' => isset($arDeal['~CREATED_BY']) && $arDeal['~CREATED_BY'] > 0
					? CCrmViewHelper::PrepareUserBaloonHtml([
						'PREFIX' => "DEAL_{$arDeal['~ID']}_CREATOR",
						'USER_ID' => $arDeal['~CREATED_BY'],
						'USER_NAME'=> $arDeal['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_CREATOR']
					])
					: '',
				'MODIFY_BY' => isset($arDeal['~MODIFY_BY']) && $arDeal['~MODIFY_BY'] > 0
					? CCrmViewHelper::PrepareUserBaloonHtml(
						array(
							'PREFIX' => "DEAL_{$arDeal['~ID']}_MODIFIER",
							'USER_ID' => $arDeal['~MODIFY_BY'],
							'USER_NAME'=> $arDeal['MODIFY_BY_FORMATTED_NAME'],
							'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_MODIFIER']
						)
					) : '',
				'OBSERVERS' => CCrmViewHelper::renderObservers(\CCrmOwnerType::Deal, $arDeal['ID'], $arDeal['~OBSERVERS'] ?? []),
			) + (is_array($arResult['DEAL_UF'][$sKey]) ? $arResult['DEAL_UF'][$sKey] : [])
	);

	if (isset($arDeal['COMPANY_REVENUE']))
	{
		$resultItem['columns']['COMPANY_REVENUE'] =
			'<nobr>'
			. number_format($arDeal['COMPANY_REVENUE'], 2, ',', ' ')
			. '</nobr>'
		;
	}

	$extraUserIdFields = [
		'CONTACT_CREATED_BY_ID',
		'CONTACT_MODIFY_BY_ID',
		'CONTACT_ASSIGNED_BY_ID',
		'COMPANY_CREATED_BY_ID',
		'COMPANY_MODIFY_BY_ID',
		'COMPANY_ASSIGNED_BY_ID',
	];
	foreach ($extraUserIdFields as $extraUserIdField)
	{
		if (isset($arDeal[$extraUserIdField]) && $arDeal[$extraUserIdField] > 0)
		{
			$resultItem['columns'][$extraUserIdField] =
				CCrmViewHelper::PrepareUserBaloonHtml(
					[
						'PREFIX' => "DEAL_{$arDeal['~ID']}_".$extraUserIdField,
						'USER_ID' => $arDeal[$extraUserIdField],
						'USER_NAME' => $arDeal[$extraUserIdField . '_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arDeal[$extraUserIdField . '_SHOW_URL'],
					]
				)
			;
		}
	}

	$extraWebformFields = [
		'CONTACT_WEBFORM_ID',
		'COMPANY_WEBFORM_ID',
	];
	foreach ($extraWebformFields as $extraWebformField)
	{
		if (
			isset($arDeal[$extraWebformField])
			&& $arDeal[$extraWebformField] != ''
			&& isset($arResult['WEBFORM_LIST'][$arDeal[$extraWebformField]])
		)
		{
			$resultItem['columns'][$extraWebformField] = $arResult['WEBFORM_LIST'][$arDeal[$extraWebformField]];
		}
	}

	if (isset($arDeal['CONTACT_SOURCE_DESCRIPTION']))
	{
		$resultItem['columns']['CONTACT_SOURCE_DESCRIPTION'] = nl2br($arDeal['CONTACT_SOURCE_DESCRIPTION']);
	}

	if (isset($arDeal['CONTACT_COMMENTS']))
	{
		$resultItem['columns']['CONTACT_COMMENTS'] = htmlspecialcharsback($arDeal['CONTACT_COMMENTS']);
	}

	if (isset($arDeal['COMPANY_BANKING_DETAILS']))
	{
		$resultItem['columns']['COMPANY_BANKING_DETAILS'] = nl2br($arDeal['COMPANY_BANKING_DETAILS']);
	}

	if (isset($arDeal['COMPANY_COMMENTS']))
	{
		$resultItem['columns']['COMPANY_COMMENTS'] = htmlspecialcharsback($arDeal['COMPANY_COMMENTS']);
	}

	Tracking\UI\Grid::appendRows(
		\CCrmOwnerType::Deal,
		$arDeal['ID'],
		$resultItem['columns']
	);

	$resultItem['columns'] = \Bitrix\Crm\Entity\CommentsHelper::enrichGridRow(
		\CCrmOwnerType::Deal,
		$fieldContentTypeMap[$arDeal['ID']] ?? [],
		$arDeal,
		$resultItem['columns'],
	);
	if (isset($arDeal['ACTIVITY_BLOCK']) && $arDeal['ACTIVITY_BLOCK'] instanceof \Bitrix\Crm\Component\EntityList\NearestActivity\Block)
	{
		$resultItem['columns']['ACTIVITY_ID'] = $arDeal['ACTIVITY_BLOCK']->render($gridManagerID);
		if ($arDeal['ACTIVITY_BLOCK']->needHighlight())
		{
			$resultItem['columnClasses'] = ['ACTIVITY_ID' => 'crm-list-deal-today'];
		}
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'AJAX_ONLY' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if (!$isInternal
	&& ($allowWrite || $allowExclude || $allowDelete))
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

	$actionList = array(array('NAME' => GetMessage('CRM_DEAL_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	$yesnoList = array(
		array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
	);

	if ($allowWrite && $arParams['IS_RECURRING'] !== "Y")
	{
		//region Add Task
		if (IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_TASK'),
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

		if ($arParams['IS_RECURRING'] !== "Y")
		{
			//region Set Stage
			if ($arResult['EFFECTIVE_CATEGORY_ID'] >= 0)
			{
				//TODO: if category not selected show 2 selectors: category and stage
				$stageList = array(array('NAME' => GetMessage('CRM_STAGE_INIT'), 'VALUE' => ''));
				if (isset($arResult['CATEGORY_STAGE_LIST']))
				{
					foreach ($arResult['CATEGORY_STAGE_LIST'] as $stageID => $stageName)
					{
						$stageList[] = array('NAME' => $stageName, 'VALUE' => $stageID);
					}
				}
				elseif(isset($arResult['CATEGORY_STAGE_GROUPS']))
				{
					foreach ($arResult['CATEGORY_STAGE_GROUPS'] as $group)
					{
						$groupName = isset($group['name']) ? $group['name'] : '';
						if ($groupName !== '')
						{
							$stageList[] = array('NAME' => $groupName, 'VALUE' => '', 'IS_GROUP' => true);
						}

						$groupItems = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];
						foreach ($groupItems as $itemKey => $itemName)
						{
							$stageList[] = array('NAME' => $itemName, 'VALUE' => $itemKey);
						}
					}
				}

				$actionList[] = array(
					'NAME' => GetMessage('CRM_DEAL_SET_STAGE'),
					'VALUE' => 'set_stage',
					'ONCHANGE' => array(
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
							'DATA' => array(
								array(
									'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
									'ID' => 'action_stage_id',
									'NAME' => 'ACTION_STAGE_ID',
									'ITEMS' => $stageList
								),
								$applyButton
							)
						),
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
							'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'set_stage')"))
						)
					)
				);
			}
			else
			{
				$actionList[] = array(
					'NAME' => GetMessage('CRM_DEAL_SET_STAGE'),
					'VALUE' => 'set_stage',
					'PSEUDO' => true,
					'ONCHANGE' => array(
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
							'DATA' => array($applyButton)
						),
						array(
							'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
							'DATA' => array(array('JS' => "BX.NotificationPopup.show(\"select_category\", { timeout: 5000, align: \"justify\", messages: [\"".GetMessageJS('CRM_DEAL_LIST_SELECT_CATEGORY_FOR_SET_STAGE_TITLE')."\", \"".GetMessageJS('CRM_DEAL_LIST_SELECT_CATEGORY_FOR_SET_STAGE_CONTENT')."\"] })"))
						)
					)
				);
			}
			//endregion
		}

		//region Assign To
		//region Render User Search control
		if (!Bitrix\Main\Grid\Context::isInternalRequest())
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
					'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'] ?? ''
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
		//endregion
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_ASSIGN_TO'),
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
		if (IsModuleInstalled('voximplant'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_CREATE_CALL_LIST'),
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

		//region Refresh Accounting Data
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_REFRESH_ACCOUNT'),
			'VALUE' => 'refresh_account',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				)
			)

		);
		//endregion

		if ($allowDelete && !$arResult['IS_EXTERNAL_FILTER'])
		{
			$actionList[] = [
				'NAME' => GetMessage('CRM_DEAL_ACTION_MERGE'),
				'VALUE' => 'merge',
				'ONCHANGE' => [
					[
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => [
							array_merge(
								$applyButton,
								['SETTINGS' => [
									'minSelectedRows' => 2,
									'buttonId' => 'apply_button'
								]]
							)
						]
					],
					[
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => [['JS' => "BX.CrmUIGridExtension.applyAction('{$gridManagerID}', 'merge')"]]
					]
				]
			];
		}
	}

	if ($allowDelete)
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
			'NAME' => GetMessage('CRM_DEAL_ACTION_DELETE'),
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

	if ($allowExclude)
	{
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_EXCLUDE'),
			'VALUE' => 'exclude',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'exclude')"))
				)
			)
		);
	}

	if ($allowWrite)
	{
		//region Edit Button
		$actionManager = new ActionManager($gridManagerID);
		$controlPanel['GROUPS'][0]['ITEMS'][] = $actionManager->getEditButton();
		$actionList[] = $actionManager->getEditAction();
		//endregion

		//region Mark as Opened
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_MARK_AS_OPENED'),
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

		//region Change category
		if ($arResult['HAVE_CUSTOM_CATEGORIES'] && $arResult['CATEGORY_ID'] >= 0)
		{
			$categoryList = [];
			foreach ($arResult['CATEGORY_LIST'] as $categoryID => $categoryName)
			{
				if ($categoryID !== $arResult['CATEGORY_ID'])
				{
					$categoryList[] = array('NAME' => $categoryName, 'VALUE' => $categoryID);
				}
			}
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_MOVE_TO_CATEGORY'),
				'VALUE' => 'move_to_category',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							array(
								'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
								'ID' => 'action_move_to_category',
								'NAME' => 'ACTION_CATEGORY_ID',
								'ITEMS' => $categoryList
							),
							$applyButton
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'set_stage')"))
					)
				)
			);
		}
	}

	if ($callListUpdateMode)
	{
		$callListContext = \CUtil::jsEscape($arResult['CALL_LIST_CONTEXT']);
		$controlPanel['GROUPS'][0]['ITEMS'][] = [
			"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
			"TEXT" => GetMessage("CRM_DEAL_UPDATE_CALL_LIST"),
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
		if (IsModuleInstalled('voximplant'))
		{
			$controlPanel['GROUPS'][0]['ITEMS'][] = array(
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => GetMessage('CRM_DEAL_START_CALL_LIST'),
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

if ($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_DEAL_LIST_ADD_SHORT'),
		'TITLE' => GetMessage('CRM_DEAL_LIST_ADD'),
		'LINK' => $arResult['PATH_TO_DEAL_ADD'],
		'ICON' => 'btn-new'
	);

	if ($arResult['ADD_EVENT_NAME'] !== '')
	{
		$addButton['ONCLICK'] = "BX.onCustomEvent(window, '{$arResult['ADD_EVENT_NAME']}')";
	}
	else
	{
		$urlParams = isset($arResult['DEAL_ADD_URL_PARAMS']) && is_array($arResult['DEAL_ADD_URL_PARAMS'])
			? $arResult['DEAL_ADD_URL_PARAMS'] : [];
		$addButton['ONCLICK'] = 'BX.CrmEntityManager.createEntity(BX.CrmEntityType.names.deal, { urlParams: '.CUtil::PhpToJSObject($urlParams).' })';
		unset($addButton['LINK']);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => mb_strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => array($addButton)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

$messages = [];
if (isset($arResult['ERRORS']) && is_array($arResult['ERRORS']))
{
	foreach ($arResult['ERRORS'] as $error)
	{
		$messages[] = array(
			'TYPE' => \Bitrix\Main\Grid\MessageType::ERROR,
			'TITLE' => $error['TITLE'],
			'TEXT' => $error['TEXT']
		);
	}
}
if (isset($arResult['MESSAGES']) && is_array($arResult['MESSAGES']))
{
	foreach ($arResult['MESSAGES'] as $message)
	{
		$messages[] = array(
			'TYPE' => \Bitrix\Main\Grid\MessageType::MESSAGE,
			'TITLE' => $message['TITLE'],
			'TEXT' => $message['TEXT']
		);
	}
}

//$arParams['IS_RECURRING']
$APPLICATION->IncludeComponent(
	'bitrix:crm.newentity.counter.panel',
	'',
	array(
		'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
		'GRID_ID' => $arResult['GRID_ID'],
		'CATEGORY_ID' => $arResult['CATEGORY_ID'] >=0 ? $arResult['CATEGORY_ID'] : null,
	),
	$component
);

$filterLazyLoadUrl = '/bitrix/components/bitrix/crm.deal.list/filter.ajax.php?' . bitrix_sessid_get();
$filterLazyLoadParams = [
	'filter_id' => urlencode($arResult['GRID_ID']),
	'category_id' => $arResult['CATEGORY_ID'] ?? null,
	'is_recurring' => $arParams['IS_RECURRING'],
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
		'AJAX_LOADER' => ($arParams['AJAX_LOADER'] ?? null),
		'HIDE_FILTER' => ($arParams['HIDE_FILTER'] ?? null),
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
				'popupColumnsCount' => 4,
				'popupWidth' => 800,
				'showPopupInCenter' => true,
			],
			'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => (bool)(
				$arParams['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? \Bitrix\Main\ModuleManager::isModuleInstalled('ui')
			),
			'RESTRICTED_FIELDS' => $arResult['RESTRICTED_FIELDS'] ?? [],
		],
		'LIVE_SEARCH_LIMIT_INFO' => ($arResult['LIVE_SEARCH_LIMIT_INFO'] ?? null),
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => (
		isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION']
			: []
		),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'MESSAGES' => $messages,
		'DISABLE_NAVIGATION_BAR' => $arResult['DISABLE_NAVIGATION_BAR'],
		'NAVIGATION_BAR' => (new NavigationBarPanel(CCrmOwnerType::Deal, $arResult['CATEGORY_ID']))
			->setItems([
				NavigationBarPanel::ID_KANBAN,
				NavigationBarPanel::ID_LIST,
				NavigationBarPanel::ID_ACTIVITY,
				NavigationBarPanel::ID_CALENDAR,
				NavigationBarPanel::ID_AUTOMATION
			], NavigationBarPanel::ID_LIST)
			->setBinding($arResult['NAVIGATION_CONTEXT_ID'])
			->get(),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => [
			'ID' => $gridManagerID,
			'CONFIG' => [
				'ownerTypeName' => CCrmOwnerType::DealName,
				'categoryId' => (int)($arResult['CATEGORY_ID'] ?? 0),
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> ($arResult['TASK_CREATE_URL'] ?? ''),
				'serviceUrl' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => ($arParams['AJAX_LOADER'] ?? null),
			],
			'MESSAGES' => [
				'deletionDialogTitle' => GetMessage('CRM_DEAL_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_DEAL_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_DEAL_DELETE'),
				'moveToCategoryDialogTitle' => GetMessage('CRM_DEAL_MOVE_TO_CATEGORY_DLG_TITLE'),
				'moveToCategoryDialogMessage' => GetMessage('CRM_DEAL_MOVE_TO_CATEGORY_DLG_SUMMARY'),
				'exclusionDialogTitle' => GetMessage('CRM_DEAL_EXCLUDE_TITLE'),
				'exclusionDialogMessage' => GetMessage('CRM_DEAL_EXCLUDE_CONFIRM'),
				'exclusionDialogMessageHelp' => GetMessage('CRM_DEAL_EXCLUDE_CONFIRM_HELP'),
				'exclusionDialogButtonTitle' => GetMessage('CRM_DEAL_EXCLUDE'),
			],
		],
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'] ?? '',
	],
	$component
);
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.PartialEditorDialog.messages =
				{
					entityHasInaccessibleFields: "<?= CUtil::JSEscape(Loc::getMessage('CRM_DEAL_HAS_INACCESSIBLE_FIELDS')) ?>",
				};

			BX.CrmEntityManager.entityCreateUrls = <?=CUtil::PhpToJSObject($arResult['ENTITY_CREATE_URLS'])?>;
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(
				Bitrix\Crm\Category\DealCategory::getJavaScriptInfos($arResult['CATEGORY_ACCESS']['CREATE'])
			)?>;
			BX.CrmDealCategorySelectDialog.messages =
				{
					title: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DLG_TITLE')?>",
					field: "<?=GetMessageJS('CRM_DEAL_CATEGORY_DLG_FIELD')?>",
					saveButton: "<?=GetMessageJS('CRM_DEAL_BUTTON_SAVE')?>",
					cancelButton: "<?=GetMessageJS('CRM_DEAL_BUTTON_CANCEL')?>"
				};

			BX.CrmLongRunningProcessDialog.messages =
				{
					startButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_START')?>",
					stopButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_STOP')?>",
					closeButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_CLOSE')?>",
					wait: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_WAIT')?>",
					requestError: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_REQUEST_ERR')?>"
				};

			BX.Crm.PartialEditorDialog.registerEntityEditorUrl(
				"<?=CCrmOwnerType::DealName?>",
				"<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?'.bitrix_sessid_get()?>"
			);

			var gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";
			BX.Crm.BatchDeletionManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Deal?>,
					extras: { CATEGORY_ID: <?=$arResult['CATEGORY_ID']?>, IS_RECURRING: "<?=CUtil::JSEscape($arParams['IS_RECURRING'])?>" },
					container: "batchDeletionWrapper",
					stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>",
					messages:
						{
							title: "<?=GetMessageJS('CRM_DEAL_LIST_DEL_PROC_DLG_TITLE')?>",
							confirmation: "<?=GetMessageJS('CRM_DEAL_LIST_DEL_PROC_DLG_SUMMARY')?>",
							summaryCaption: "<?=GetMessageJS('CRM_DEAL_BATCH_DELETION_COMPLETED')?>",
							summarySucceeded: "<?=GetMessageJS('CRM_DEAL_BATCH_DELETION_COUNT_SUCCEEDED')?>",
							summaryFailed: "<?=GetMessageJS('CRM_DEAL_BATCH_DELETION_COUNT_FAILED')?>"
						}
				}
			);

			BX.Crm.BatchMergeManager.create(
				gridId,
				{
					gridId: gridId,
					entityTypeId: <?=CCrmOwnerType::Deal?>,
					mergerUrl: "<?=\CUtil::JSEscape($arParams['PATH_TO_DEAL_MERGE'])?>"
				}
			);

			<?php if (isset($arResult['RESTRICTED_VALUE_CLICK_CALLBACK'])):?>
			BX.addCustomEvent(window, 'onCrmRestrictedValueClick', function() {
				<?=$arResult['RESTRICTED_VALUE_CLICK_CALLBACK'];?>
			});
			<?php endif;?>
		}
	);
</script><?php
if (
	!$isInternal
	&& !$isRecurring
	&& \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME') !== 'Y'
):
	Extension::load(['crm.settings-button-extender', 'crm.toolbar-component']);
	$todoCreateNotificationSkipPeriod =
		(new \Bitrix\Crm\Activity\TodoCreateNotification(\CCrmOwnerType::Deal))
			->getCurrentSkipPeriod()
	;
	?><script type="text/javascript">
	BX.ready(
		function()
		{
			const settingsButton = BX.Crm.ToolbarComponent.Instance.getSettingsButton();
			const settingsMenu = settingsButton ? settingsButton.getMenuWindow() : undefined;
			if (settingsMenu)
			{
				new BX.Crm.SettingsButtonExtender({
					smartActivityNotificationSupported: <?= Container::getInstance()->getFactory(\CCrmOwnerType::Deal)->isSmartActivityNotificationSupported() ? 'true' : 'false' ?>,
					entityTypeId: <?= \CCrmOwnerType::Deal ?>,
					categoryId: <?= isset($arResult['CATEGORY_ID']) ? (int)$arResult['CATEGORY_ID'] : 'null' ?>,
					pingSettings: <?= \CUtil::PhpToJSObject((new TodoPingSettingsProvider(\CCrmOwnerType::Deal, (int)($arResult['CATEGORY_ID'] ?? 0)))->fetchAll()) ?>,
					rootMenu: settingsMenu,
					grid: BX.Reflection.getClass('BX.Main.gridManager') ? BX.Main.gridManager.getInstanceById('<?= \CUtil::JSEscape($arResult['GRID_ID']) ?>') : undefined,
					<?php if (is_string($todoCreateNotificationSkipPeriod)): ?>
					todoCreateNotificationSkipPeriod: '<?= \CUtil::JSEscape($todoCreateNotificationSkipPeriod) ?>',
					<?php endif; ?>
					<?php if (
						\Bitrix\Crm\Integration\AI\AIManager::isAiCallAutomaticProcessingAllowed()
						&& in_array(\CCrmOwnerType::Deal, \Bitrix\Crm\Integration\AI\AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
						&& Container::getInstance()->getUserPermissions()->isAdmin()
						&& isset($arResult['CATEGORY_ID'])
						&& (int)$arResult['CATEGORY_ID'] >= 0
					): ?>
					aiAutostartSettings: '<?= \Bitrix\Main\Web\Json::encode(\Bitrix\Crm\Integration\AI\Operation\AutostartSettings::get(\CCrmOwnerType::Deal, (int)$arResult['CATEGORY_ID'])) ?>',
					<?php endif; ?>
				});
			}
		}
	);
</script><?php
endif;

if (!$isInternal):
	?><script type="text/javascript">
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
			if (typeof BX.Crm.Activity.Planner !== 'undefined')
			{
				BX.Crm.Activity.Planner.Manager.setCallback('onAfterActivitySave', function()
				{
					BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
				});
			}
		}
	);
</script>
<?php endif;

if ($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && $conversionConfig):
	Extension::load('crm.conversion');
	?><script type="text/javascript">
		BX.ready(
			function()
			{
				BX.Crm.Conversion.Manager.Instance.initializeConverter(
					BX.CrmEntityType.enumeration.deal,
					{
						configItems: <?= CUtil::PhpToJSObject($conversionConfig->toJson()) ?>,
						scheme: <?= CUtil::PhpToJSObject($conversionConfig->getScheme()->toJson(true)) ?>,
						params: {
							id: '<?= \CUtil::JSEscape($arResult['CONVERTER_ID']) ?>',
							serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?action=convert&'.bitrix_sessid_get()?>",
							originUrl: '<?= CUtil::JSEscape($APPLICATION->GetCurPage()) ?>',
							messages: {
								accessDenied: "<?=GetMessageJS("CRM_DEAL_CONV_ACCESS_DENIED")?>",
								generalError: "<?=GetMessageJS("CRM_DEAL_CONV_GENERAL_ERROR")?>",
								dialogTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_TITLE")?>",
								syncEditorLegend: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_LEGEND")?>",
								syncEditorFieldListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
								syncEditorEntityListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
								continueButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CONTINUE_BTN")?>",
								cancelButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CANCEL_BTN")?>"
							},
							analytics: {
								c_sub_section: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_LIST ?>',
								c_element: '<?= \Bitrix\Crm\Integration\Analytics\Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU ?>',
							},
						}
					},
				);

				BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("rebuildDealSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_DEAL_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildDealSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildDealSearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['NEED_FOR_REBUILD_TIMELINE_SEARCH_CONTENT'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("rebuildTimelineSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_TIMELINE_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildTimelineSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.timeline/ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_TIMELINE_SEARCH_CONTENT",
						container: "buildTimelineSearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['NEED_FOR_REBUILD_SECURITY_ATTRS'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.AutorunProcessManager.createIfNotExists(
					"rebuildDealSecurityAttrs",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SECURITY_ATTRS",
						container: "rebuildDealSecurityAttrsWrapper",
						title: "<?=GetMessageJS('CRM_DEAL_REBUILD_SECURITY_ATTRS_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>",
						enableLayout: true
					}
				).runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['NEED_FOR_BUILD_TIMELINE'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("buildDealTimeline"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_DEAL_BUILD_TIMELINE_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_DEAL_BUILD_TIMELINE_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("buildDealTimeline",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "<?=$isRecurring ? 'BUILD_RECURRING_TIMELINE' : 'BUILD_TIMELINE'?>",
						container: "buildDealTimelineWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['NEED_FOR_REFRESH_ACCOUNTING'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("refreshDealAccounting"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_DEAL_REFRESH_ACCOUNTING_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("refreshDealAccounting",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REFRESH_ACCOUNTING",
						container: "refreshDealAccountingWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if (BX.AutorunProcessPanel.isExists("rebuildDealSemantics"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_DEAL_REBUILD_SEMANTICS_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_DEAL_STEPWISE_STATE_TEMPLATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildDealSemantics",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEMANTICS",
						container: "rebuildDealSemanticsWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if (!empty($arResult['NEED_FOR_REBUILD_DEAL_ATTRS'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				var link = BX("rebuildDealAttrsLink");
				if (link)
				{
					BX.bind(
						link,
						"click",
						function(e)
						{
							var msg = BX("rebuildDealAttrsMsg");
							if (msg)
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

<?php

if (!empty($arResult['RESTRICTED_FIELDS_ENGINE']))
{
	Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['RESTRICTED_FIELDS_ENGINE'];
}

\Bitrix\Crm\Integration\NotificationsManager::showSignUpFormOnCrmShopCreated();

<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Main\UI;
use Bitrix\Main\Web\Uri;

UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.tooltip',
]);

/** @var array $arResult */

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');

$listID = $arResult['LIST_ID'];
$entityTypeID = $arResult['ENTITY_TYPE_ID'];
$entityTypeName = $arResult['ENTITY_TYPE_NAME'];
$entityInfos = $arResult['ENTITY_INFOS'];
$layoutID = $arResult['LAYOUT_ID'];
$enableLayout = $arResult['ENABLE_LAYOUT'];
$untitled = '<'.GetMessage($layoutID === CCrmOwnerType::Contact ? 'CRM_DEDUPE_LIST_UNNAMED_PERSON' : 'CRM_DEDUPE_LIST_UNTITLED_COMPANY').'>';
$sortTypeID = $arResult['SORT_TYPE_ID'];
$sortBy = $arResult['SORT_BY'];
$sortOrder = $arResult['SORT_ORDER'];
$findButtonID = "{$listID}_find";
$buildIndexButtonID = "{$listID}_build_index";
$typeContainerID = "{$listID}_type";
$scopeSelectorID = "{$listID}_scope";

if(!empty($arResult['ERRORS']))
{
	foreach($arResult['ERRORS'] as $error)
	{
		ShowError($error);
	}
}

?><div class="sidebar-block double">
	<div class="sidebar-block-inner">
		<div class="reports-description-text">
			<?=GetMessage('CRM_DEDUPE_LIST_CRITERION_SELECTOR')?>:<?
			if ($entityTypeID === CCrmOwnerType::Company || $entityTypeID === CCrmOwnerType::Contact)
			{
				$displayStyle = count($arResult['SCOPE_LIST_ITEMS']) <= 1 ? ' display: none;' : '';
			?>
			<span style="float: right;<?= $displayStyle ?>">
				<label for="<?=htmlspecialcharsbx($scopeSelectorID)?>"><?=htmlspecialcharsbx(GetMessage('CRM_DEDUPE_LIST_SCOPE_SELECTOR_LABEL').': ')?></label>
				<select id="<?=htmlspecialcharsbx($scopeSelectorID)?>">
					<?
					foreach ($arResult['SCOPE_LIST_ITEMS'] as $scope => $scopeTitle)
					{?>
						<option<?=$scope === $arResult['CURRENT_SCOPE'] ? ' selected="selected"' : ''?> value="<?= $scope ?>"><?= htmlspecialcharsbx($scopeTitle) ?></option><?
					}?>
				</select>
			</span><?
			}
			?><br/><br/>
			<div id="<?=htmlspecialcharsbx($typeContainerID)?>" class="crm-double-set-checkbox-wrap">
				<div>
					<div style="float: right; margin-right: 0;">
						<span class="crm-items-table-bar-l-wtax">
							<span id="<?=htmlspecialcharsbx($findButtonID)?>" class="webform-small-button webform-small-button-accept" style="line-height: 25px !important;">
								<span class="webform-small-button-left"></span>
								<span class="webform-small-button-text"><?=GetMessage('CRM_DEDUPE_LIST_BUTTON_FIND')?></span>
								<span class="webform-small-button-right"></span>
							</span>
							<span id="<?=htmlspecialcharsbx($buildIndexButtonID)?>" class="webform-small-button webform-small-button" style="line-height: 25px !important; margin-right: 0;">
								<span class="webform-small-button-left"></span>
								<span class="webform-small-button-text"><?=GetMessage('CRM_DEDUPE_LIST_BUTTON_BUILD_INDEX')?></span>
								<span class="webform-small-button-right"></span>
							</span>
						</span>
					</div><?
				$curTypeGroupName = '';
				$controlsByScope = array();
				foreach($arResult['TYPE_INFOS'] as $extTypeID => &$typeInfo)
				{
					$parts = explode('|', $extTypeID, 2);
					$typeID = $parts[0];
					$scope = isset($parts[1]) ? $parts[1] : '';
					unset($parts);
					$typeLayoutName = $typeInfo['LAYOUT_NAME'];
					$typeGroupName = $typeInfo['GROUP_NAME'];
					if($curTypeGroupName !== $typeGroupName)
					{
						if($curTypeGroupName !== '')
						{
							?></div><?
						}
						if($typeGroupName !== '')
						{
							?><div class="bx-sl-crm-input-container-<?=$typeGroupName?>"><?
						}
						$curTypeGroupName = $typeGroupName;
					}

					$isSelected = $typeInfo['IS_SELECTED'];
					$isUnderstated = $typeInfo['IS_UNDERSTATED'];

					$scopePostfix = (isset($typeInfo['SCOPE']) && !empty($typeInfo['SCOPE'])) ?
						'_'.mb_strtolower($typeInfo['SCOPE']) : '';
					$controlID = $listID.'_'.mb_strtolower($typeInfo['NAME']).$scopePostfix;
					if (!isset($controlsByScope[$scope]))
						$controlsByScope[$scope] = array();
					$controlsByScope[$scope][] = $controlID;
					$displayNone = ($scope !== $arResult['CURRENT_SCOPE']) ? ' style="display: none;"' : '';
					?><input id="<?=htmlspecialcharsbx($controlID)?>" class="crm-double-set-checkbox" value="<?=htmlspecialcharsbx($extTypeID)?>" type="checkbox"<?=$isSelected ? ' checked="checked"' : ''?><?=$displayNone?>/>&nbsp;
					<label<?=$isUnderstated ? ' disabled="disabled"' : ''?> for="<?=htmlspecialcharsbx($controlID)?>" class="crm-double-set-label"<?=$displayNone?>><?=htmlspecialcharsbx($typeInfo['DESCRIPTION'])?></label><?
				}
				unset($typeInfo);
				if($curTypeGroupName !== '')
				{
					?></div><?
				}

			?></div>
			</div>
		</div>
	</div>
</div><?
if($arResult['NEED_FOR_REBUILD_DUP_INDEX'])
{
	//CRM_DEDUPE_LIST_LEAD_REBUILD_DUP_INDEX, CRM_DEDUPE_LIST_CONTACT_REBUILD_DUP_INDEX, CRM_DEDUPE_LIST_COMPANY_REBUILD_DUP_INDEX
	?><div id="rebuildDupIndexMsg" class="crm-view-message">
		<?=GetMessage("CRM_DEDUPE_LIST_{$entityTypeName}_REBUILD_DUP_INDEX", array('#ID#' => 'rebuildDupIndexLink', '#URL#' => '#'))?>
	</div><?
}
if(!empty($arResult['MESSAGES']))
{
	foreach($arResult['MESSAGES'] as $msg)
	{
		?><div class="crm-view-message"><?=htmlspecialcharsbx($msg)?></div><?
	}
}
?><div class="bx-crm-interface-grid"><table id="<?=htmlspecialcharsbx($listID)?>" class="bx-interface-grid-double" cellspacing="0"><tbody>
<tr class="bx-grid-head">
<td class="bx-checkbox-col" style="width: 10px;"></td>
<td class="bx-checkbox-col" style="width: 10px;"></td><?
foreach($arResult['COLUMNS'] as $columnID => &$column)
{
	$isSorted = $columnID === $sortBy;
	$colspan = isset($column['COLSPAN']) ? $column['COLSPAN'] : 1;
	?><td class="bx-grid-sortable<?=$isSorted ? ' bx-sorted' : ''?>" data-column-id="<?=htmlspecialcharsbx($columnID)?>" <?=$colspan > 1 ? ' colspan="'.$colspan.'"' : ''?>><?
	if(!$column['SORTABLE'])
	{
		echo htmlspecialcharsbx($column['TITLE']);
	}
	else
	{
		$sortClass = $isSorted ? ($sortOrder === SORT_DESC ? ' bx-sort-down' : ' bx-sort-up') : '';
		?><table class="bx-grid-sorting" border="0" cellspacing="0" cellpadding="0"><tbody>
			<tr>
				<td><?=htmlspecialcharsbx($column['TITLE'])?></td>
				<td class="bx-sort-sign<?=$sortClass?>"><div class="empty"></div></td>
			</tr>
		</tbody></table><?
	}
	?></td><?
}
unset($column);
?></tr><?
$itemData = array();
$itemNum = 0;
/** @var Bitrix\Crm\Integrity\Duplicate $item **/
foreach($arResult['ITEMS'] as $item)
{
	//$className = ((++$itemNum) % 2) > 0 ? "bx-odd" : "bx-even bx-over";
	$itemID = uniqid('dupId', true);
	?><tr class="bx-dupe-item" data-dupe-id="<?=htmlspecialcharsbx($itemID)?>">
		<td class="bx-left"><span class="bx-scroller-control plus"></span></td>
		<td class="bx-checkbox-col bx-left">
			<input type="checkbox" id="<?=htmlspecialcharsbx($itemID)?>_chkbx" title="<?=GetMessage('CRM_DEDUPE_LIST_SELECT_ALL')?>" alt="" />
		</td><?
	//$isJunk = $item->isJunk();
	$rootEntityID = $item->getRootEntityID();
	$rootEntityInfo = isset($entityInfos[$rootEntityID]) ? $entityInfos[$rootEntityID] : array();

	$criterion = $item->getCriterion();
	$criterionTypeID = $criterion->getIndexTypeID();
	$criterionMatches = $criterion->getMatches();

	$itemData[$itemID] = array(
		'ROOT_ENTITY_ID' => $rootEntityID,
		'INDEX_TYPE_NAME' => DuplicateIndexType::resolveName($criterionTypeID),
		'INDEX_MATCHES' => $criterionMatches
	);

	$rqColSpan = 0;
	foreach($arResult['COLUMNS'] as &$column)
	{
		$colspan = isset($column['COLSPAN']) ? $column['COLSPAN'] : 1;
		?><td<?=$colspan > 1 ? ' colspan="'.$colspan.'"' : ''?>><?

		$colName = $column['NAME'];
		if($colName === 'ORGANIZATION' || $colName === 'PERSON')
		{
			$imageUrl = '';
			$imageID = isset($rootEntityInfo['IMAGE_FILE_ID']) ? $rootEntityInfo['IMAGE_FILE_ID'] : 0;
			if($imageID > 0)
			{
				$imageInfo = CFile::ResizeImageGet(
					$imageID,
					array('width' => 100, 'height' => 100),
					BX_RESIZE_IMAGE_EXACT
				);
				$imageUrl = $imageInfo['src'];
			}

			$rootEntityShowUrl = isset($rootEntityInfo['SHOW_URL']) ? $rootEntityInfo['SHOW_URL'] : '';
			$rootEntityTitle = isset($rootEntityInfo['TITLE']) ? $rootEntityInfo['TITLE'] : '';
			if($rootEntityTitle === '')
			{
				$rootEntityTitle = $layoutID === CCrmOwnerType::Contact
					? $item->getRootPersonName() : $item->getRootOrganizationTitle();
			}
			if($rootEntityTitle === '')
			{
				$rootEntityTitle = $untitled;
			}
			$itemData[$itemID]['TITLE'] = $rootEntityTitle;
			$rootEntityLegend = isset($rootEntityInfo['LEGEND']) ? $rootEntityInfo['LEGEND'] : '';
			?><div class="crm-client-summary-wrapper">
			<? if($imageUrl === '') { ?>
				<div class="crm-client-photo-wrapper empty">
					<div class="ui-icon ui-icon-common-user crm-avatar crm-avatar-user">
						<i></i>
					</div>
				</div>
			<?
				}
				else
				{
			?>
				<div class="crm-client-photo-wrapper">
					<img width="50" height="50" border="0" src="<?= Uri::urnEncode(htmlspecialcharsbx($imageUrl))?>" alt="" />
				</div>
			<? 	}; ?>
				<div class="crm-client-info-wrapper">
					<div class="crm-client-title-wrapper"><?
						if($rootEntityShowUrl !== '')
						{
							?><a target="_blank" href="<?=htmlspecialcharsbx($rootEntityShowUrl)?>"><?=htmlspecialcharsbx($rootEntityTitle)?></a><?
						}
						else
						{
							echo htmlspecialcharsbx($rootEntityTitle);
						}
					?></div>
					<div class="crm-client-description-wrapper"><?=htmlspecialcharsbx($rootEntityLegend)?></div>
				</div>
				<div id="<?=htmlspecialcharsbx($itemID)?>_summary" style="visibility:hidden;" class="crm-double-result-search"><?=htmlspecialcharsbx($item->getSummary())?></div>
				<div style="clear:both;"></div>
			</div><?
		}
		if($colName === 'PHONE' || $colName === 'EMAIL')
		{
			$commType = $colName;
			$rootEntityCommInfo = isset($rootEntityInfo[$commType]) ? $rootEntityInfo[$commType] : null;
			if(is_array($rootEntityCommInfo))
			{
				$rootEntityCommValue = isset($rootEntityCommInfo['FIRST_VALUE']) ? $rootEntityCommInfo['FIRST_VALUE'] : '';
				$rootEntityCommTotal = isset($rootEntityCommInfo['TOTAL']) ? $rootEntityCommInfo['TOTAL'] : 0;
			}
			else
			{
				$rootEntityCommValue = '';
				$rootEntityCommTotal = 0;
			}

			if($rootEntityCommValue !== '')
			{
				$itemData[$itemID][$colName] = $rootEntityCommValue;
				?><div class="crm-client-contacts-block">
					<div class="crm-client-contacts-block-text" style="white-space:nowrap;"><?=htmlspecialcharsbx($rootEntityCommValue)?></div><?
				if($rootEntityCommTotal > 1)
				{
					?><div class="crm-multi-field-popup-wrapper">
						<span id="<?=htmlspecialcharsbx($itemID)?>_show_<?= mb_strtolower($colName)?>" class="crm-multi-field-popup-button"><?=GetMessage('CRM_DEDUPE_LIST_SHOW_MORE_MULTI_FIELD_VALUES')?> <?=($rootEntityCommTotal - 1)?></span>
					</div><?
				}
				?></div><?
			}
		}
		if(($column['TYPE_ID'] & DuplicateIndexType::REQUISITE) === $column['TYPE_ID']
			|| ($column['TYPE_ID'] & DuplicateIndexType::BANK_DETAIL) === $column['TYPE_ID'])
		{
			$rqColSpan += $colspan;
			$rootEntityFieldInfo = isset($rootEntityInfo[$colName]) ? $rootEntityInfo[$colName] : null;
			if(is_array($rootEntityFieldInfo))
			{
				$rootEntityFieldValue = isset($rootEntityFieldInfo['FIRST_VALUE']) ? $rootEntityFieldInfo['FIRST_VALUE'] : '';
				$rootEntityFieldTotal = isset($rootEntityFieldInfo['TOTAL']) ? $rootEntityFieldInfo['TOTAL'] : 0;
			}
			else
			{
				$rootEntityFieldValue = '';
				$rootEntityFieldTotal = 0;
			}

			if($rootEntityFieldValue !== '')
			{
				$itemData[$itemID][$colName] = $rootEntityFieldValue;
				?><div class="crm-client-contacts-block">
				<div class="crm-client-contacts-block-text" style="white-space:nowrap;"><?=htmlspecialcharsbx($rootEntityFieldValue)?></div><?
				if($rootEntityFieldTotal > 1)
				{
					?><div class="crm-multi-field-popup-wrapper">
					<span id="<?=htmlspecialcharsbx($itemID)?>_show_<?= mb_strtolower($colName)?>" class="crm-multi-field-popup-button"><?=GetMessage('CRM_DEDUPE_LIST_SHOW_MORE_MULTI_FIELD_VALUES')?> <?=($rootEntityFieldTotal - 1)?></span>
					</div><?
				}
				?></div><?
			}
		}
		if($colName === 'RESPONSIBLE')
		{
			$itemData[$itemID]['RESPONSIBLE_ID'] = isset($rootEntityInfo['RESPONSIBLE_ID']) ? $rootEntityInfo['RESPONSIBLE_ID'] : '';
			$rootEntityResponsibleName = isset($rootEntityInfo['RESPONSIBLE_FULL_NAME']) ? $rootEntityInfo['RESPONSIBLE_FULL_NAME'] : '';
			$rootEntityResponsibleEmail = isset($rootEntityInfo['RESPONSIBLE_EMAIL']) ? $rootEntityInfo['RESPONSIBLE_EMAIL'] : '';
			$rootEntityResponsiblePhone = isset($rootEntityInfo['RESPONSIBLE_PHONE']) ? $rootEntityInfo['RESPONSIBLE_PHONE'] : '';
			if($rootEntityResponsibleName !== '')
			{
				echo htmlspecialcharsbx($rootEntityResponsibleName);
			}
			$itemData[$itemID]['RESPONSIBLE_FULL_NAME'] = $rootEntityResponsibleName;
			?><br/><?
			$itemData[$itemID]['RESPONSIBLE_EMAIL'] = $rootEntityResponsibleEmail;
			?><a id="<?=htmlspecialcharsbx($itemID)?>_mail_to_user" href="mailto:<?=htmlspecialcharsbx($rootEntityResponsibleEmail)?>"><?=GetMessage('CRM_DEDUPE_LIST_MAIL_TO')?></a><?
			if($rootEntityResponsiblePhone !== '')
			{
				$itemData[$itemID]['RESPONSIBLE_PHONE'] = $rootEntityResponsiblePhone;
				?><a id="<?=htmlspecialcharsbx($itemID)?>_call_to_user" style="margin-left: 20px;" href="callto:<?=htmlspecialcharsbx($rootEntityResponsiblePhone)?>"><?=GetMessage('CRM_DEDUPE_LIST_CALL_TO')?></a><?
			}
		}
		?></td><?
	}
	unset($column);
	?></tr>
	<tr id="<?=htmlspecialcharsbx($itemID)?>_btn_wrapper" class="bx-dupe-item-buttons" style="display:none;">
		<td colspan="2"></td>
		<td colspan="<?= (5 + $rqColSpan) ?>">
			<span id="crm-l-space" class="crm-items-table-bar-l-wtax" >
				<span id="<?=htmlspecialcharsbx($itemID)?>_merge_btn" class="webform-small-button webform-small-button-accept" style="line-height:25px!important;">
					<span class="webform-small-button-left"></span>
					<span class="webform-small-button-text"><?=GetMessage('CRM_DEDUPE_LIST_BUTTON_MERGE')?></span>
					<span class="webform-small-button-right"></span>
				</span>
				<span id="<?=htmlspecialcharsbx($itemID)?>_skip_btn" class="webform-small-button" style="line-height:25px!important;">
					<span class="webform-small-button-left"></span>
					<span class="webform-small-button-text"><?=GetMessage('CRM_DEDUPE_LIST_BUTTON_IGNORE')?></span>
					<span class="webform-small-button-right"></span>
				</span>
			</span>
		</td>
	</tr><?
}
?>
<tr class="bx-grid-footer bx-double-pagination">
	<td colspan="12">
		<table class="bx-grid-footer" border="0" cellpadding="0" cellspacing="0">
			<tbody>
				<tr><?
				if($arResult['HAS_PREV_PAGE'])
				{
					?><td><a href="<?=htmlspecialcharsbx($arResult['PREV_PAGE_URL'])?>">&#8592; <?=GetMessage('CRM_DEDUPE_LIST_PREV_PAGE')?></a></td><?
				}
				if($arResult['HAS_PREV_PAGE'] && $arResult['HAS_NEXT_PAGE'])
				{
					?><td><a class="modern-page-dots"></a></td><?
				}
				if($arResult['HAS_NEXT_PAGE'])
				{
					?><td><a href="<?=htmlspecialcharsbx($arResult['NEXT_PAGE_URL'])?>"><?=GetMessage('CRM_DEDUPE_LIST_NEXT_PAGE')?> &#8594;</a></td><?
				}
				?></tr>
			</tbody>
		</table>
	</td>
</tr>
</tbody></table></div>
<script>
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_REQUEST_ERR')?>"
			};
			BX.CrmDedupeList.messages =
			{
				typeNotSelectedError: "<?=GetMessageJS('CRM_DEDUPE_LIST_TYPE_NOT_SELECTED')?>",
				rebuildIndexDlgTitle: "<?=GetMessageJS('CRM_DEDUPE_LIST_REBUILD_DEDUPE_INDEX_DLG_TITLE')?>",
				rebuildIndexDlgSummary: "<?=GetMessageJS('CRM_DEDUPE_LIST_REBUILD_DEDUPE_INDEX_DLG_SUMMARY')?>"
			};
			BX.CrmDedupeItem.messages =
			{
				noEntitySelectedError: "<?=GetMessageJS('CRM_DEDUPE_LIST_NO_ENTITY_SELECTED')?>",
				entityMergeDeniedError: "<?=GetMessageJS('CRM_DEDUPE_LIST_ENTITY_MERGE_DENIED')?>",
				entityUpdateDeniedError: "<?=GetMessageJS('CRM_DEDUPE_LIST_ENTITY_UPDATE_DENIED')?>",
				entityDeleteDeniedError: "<?=GetMessageJS('CRM_DEDUPE_LIST_ENTITY_DELETE_DENIED')?>"
			};
			BX.CrmDedupeEntity.messages =
			{
				untitled: "<?=CUtil::JSEscape($untitled)?>",
				select: "<?=GetMessageJS('CRM_DEDUPE_LIST_SELECT')?>",
				showMoreMultiFieldValues: "<?=GetMessageJS('CRM_DEDUPE_LIST_SHOW_MORE_MULTI_FIELD_VALUES')?>",
				mailTo: "<?=GetMessageJS('CRM_DEDUPE_LIST_MAIL_TO')?>",
				callTo: "<?=GetMessageJS('CRM_DEDUPE_LIST_CALL_TO')?>",
				entityAccessDenied: "<?=GetMessageJS('CRM_DEDUPE_LIST_ENTITY_ACCESS_DENIED')?>"
			};

			BX.CrmDedupeCollisionDialog.messages =
			{
				title: "<?=GetMessageJS('CRM_DEDUPE_LIST_COLLISION_DLG_TITLE')?>",
				cancelButtonTitle: "<?=GetMessageJS('CRM_DEDUPE_LIST_COLLISION_DLG_BTN_CANCEL')?>",
				ignoreButtonTitle: "<?=GetMessageJS('CRM_DEDUPE_LIST_COLLISION_DLG_BTN_IGNORE')?>",
				cancellationRecomendation: "<?=GetMessageJS('CRM_DEDUPE_LIST_MERGE_CANCELLATION_RECOMMENDATION')?>"
			};

			BX.CrmDedupeCollisionData.messages =
			{
				leadReadCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_LEAD_MERGE_READ_COLLISION')?>",
				leadUpdateCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_LEAD_MERGE_UPDATE_COLLISION')?>",
				leadReadUpdateCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_LEAD_MERGE_READ_UPDATE_COLLISION')?>",
				leadSeedExternalOwnershipCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_LEAD_MERGE_SEED_EXTERNAL_OWNERSHIP_COLLISION')?>",
				contactReadCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_CONTACT_MERGE_READ_COLLISION')?>",
				contactUpdateCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_CONTACT_MERGE_UPDATE_COLLISION')?>",
				contactReadUpdateCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_CONTACT_MERGE_READ_UPDATE_COLLISION')?>",
				contactSeedExternalOwnershipCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_CONTACT_MERGE_SEED_EXTERNAL_OWNERSHIP_COLLISION')?>",
				companyReadCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_COMPANY_MERGE_READ_COLLISION')?>",
				companyUpdateCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_COMPANY_MERGE_UPDATE_COLLISION')?>",
				companyReadUpdateCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_COMPANY_MERGE_READ_UPDATE_COLLISION')?>",
				companySeedExternalOwnershipCollision: "<?=GetMessageJS('CRM_DEDUPE_LIST_COMPANY_MERGE_SEED_EXTERNAL_OWNERSHIP_COLLISION')?>"
			};

			var list = BX.CrmDedupeList.create(
				"<?=CUtil::JSEscape($listID)?>",
				{
					userId: <?=$arResult['USER_ID']?>,
					entityTypeName: "<?=CCrmOwnerType::ResolveName($entityTypeID)?>",
					typeData: <?=CUtil::PhpToJSObject($arResult['TYPE_INFOS'])?>,
					colData: <?=CUtil::PhpToJSObject(array_values($arResult['COLUMNS']))?>,
					layoutName: "<?=CUtil::JSEscape(CCrmOwnerType::ResolveName($layoutID))?>",
					enableLayout: <?=$enableLayout ? 'true' : 'false'?>,
					tableId: "<?=CUtil::JSEscape($listID)?>",
					serviceUrl: "<?='/bitrix/components/bitrix/crm.dedupe.list/ajax.php?'.bitrix_sessid_get()?>",
					itemData: <?=CUtil::PhpToJSObject($itemData)?>,
					sortColumnId: "<?=CUtil::JSEscape($sortBy)?>",
					sortOrder: "<?=$sortOrder === SORT_DESC ? 'desc' : 'asc'?>",
					controlsByScope: <?=CUtil::PhpToJSObject($controlsByScope)?>,
					scopeSelectorId: "<?=CUtil::JSEscape($scopeSelectorID)?>",
					currentScope: "<?=CUtil::JSEscape($arResult['CURRENT_SCOPE'])?>",
					typeContainerId: "<?=CUtil::JSEscape($typeContainerID)?>"
				}
			);
		}
	);
</script><?
if($arResult['NEED_FOR_REBUILD_DUP_INDEX'])
{
// CRM_DEDUPE_LIST_LEAD_REBUILD_DUP_INDEX_DLG_TITLE, CRM_DEDUPE_LIST_LEAD_REBUILD_DUP_INDEX_DLG_SUMMARY
// CRM_DEDUPE_LIST_CONTACT_REBUILD_DUP_INDEX_DLG_TITLE, CRM_DEDUPE_LIST_CONTACT_REBUILD_DUP_INDEX_DLG_SUMMARY
// CRM_DEDUPE_LIST_COMPANY_REBUILD_DUP_INDEX_DLG_TITLE, CRM_DEDUPE_LIST_COMPANY_REBUILD_DUP_INDEX_DLG_SUMMARY
?><script>
	BX.ready(
		function()
		{
			BX.CrmDuplicateManager.messages =
			{
				"rebuild<?=ucfirst(mb_strtolower($entityTypeName))?>IndexDlgTitle": "<?=GetMessageJS("CRM_DEDUPE_LIST_{$entityTypeName}_REBUILD_DUP_INDEX_DLG_TITLE")?>",
				"rebuild<?=ucfirst(mb_strtolower($entityTypeName))?>IndexDlgSummary": "<?=GetMessageJS("CRM_DEDUPE_LIST_{$entityTypeName}_REBUILD_DUP_INDEX_DLG_SUMMARY")?>"
			};
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_DEDUPE_LIST_LRP_DLG_REQUEST_ERR')?>"
			};

			var mgr = BX.CrmDuplicateManager.create(
				"mgr",
				{
					entityTypeName: "<?=CUtil::JSEscape($entityTypeName)?>",
					serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.<?=mb_strtolower($entityTypeName)?>.list/list.ajax.php?&<?=bitrix_sessid_get()?>"
				}
			);
			BX.addCustomEvent(
				mgr,
				"<?="ON_{$entityTypeName}_INDEX_REBUILD_COMPLETE"?>",
				function()
				{
					var msg = BX("rebuildDupIndexMsg");
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var link = BX("rebuildDupIndexLink");
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
</script><?
}

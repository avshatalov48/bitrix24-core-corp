<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION, $USER;

Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/themes/.default/crm-entity-show.css");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
$arResult['PREFIX'] = isset($arResult['PREFIX']) ? strval($arResult['PREFIX']) : 'activity_list';
$editorCfg = array(
	'OWNER_TYPE' => $arResult['OWNER_TYPE'],
	'OWNER_ID' => $arResult['OWNER_ID'],
	'READ_ONLY' => $arResult['READ_ONLY'],
	'ENABLE_UI' => true,
	'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK_ADD'],
	'ENABLE_CALENDAR_EVENT_ADD' => $arResult['ENABLE_CALENDAR_EVENT_ADD'],
	'ENABLE_EMAIL_ADD' => $arResult['ENABLE_EMAIL_ADD']
);

if(!function_exists('__CrmActivityListRenderItems'))
{
	function __CrmActivityListRenderItems($items, $showMode, $showTop, &$editorCfg)
	{
		$editorItems = array();
		$count = count($items);
		$now = time() + CTimeZone::GetOffset();

	$toolbarID = '';
	if($editorCfg['ENABLE_TOOLBAR']):
		$toolbarID = $editorCfg['EDITOR_ID'].'_toolbar';
		?><ul id="<?= htmlspecialcharsbx($toolbarID)?>" class="crm-view-actions">
			<?if($editorCfg['ENABLE_TASK_ADD']):?>
			<li class="crm-activity-command-add-task">
				<i></i>
				<span><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_LIST_ADD_TASK'))?></span>
			</li>
			<?endif;?>
			<?if($editorCfg['ENABLE_CALENDAR_EVENT_ADD']):?>
			<li class="crm-activity-command-add-call">
				<i></i>
				<span><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_LIST_ADD_CALL'))?></span>
			</li>
			<li class="crm-activity-command-add-meeting">
				<i></i>
				<span><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_LIST_ADD_MEETING'))?></span>
			</li>
			<?endif;?>
			<?if($editorCfg['ENABLE_EMAIL_ADD']):?>
			<li class="crm-activity-command-add-email">
				<i></i>
				<span><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_LIST_ADD_EMAIL'))?></span>
			</li>
			<?endif;?>
		</ul>
	<?endif;?>
	<table class="crm-view-table crm-activity-table">
		<thead>
			<tr class="crm-activity-table-head" style="<?= $count > 0 ? '' : 'display:none;' ?>" >
				<td>&nbsp;</td>
				<td><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_ROW_COL_TTL_TYPE'))?></td>
				<td><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_ROW_COL_TTL_SUBJECT'))?></td>
				<td><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_ROW_COL_TTL_DEAD_LINE'))?></td>
				<td><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_ROW_COL_TTL_RESPONSIBLE'))?></td>
			</tr>
		</thead>
		<tbody>
			<?
			$processed = 0;
			for($i = 0; $i < $count; $i++)
			{
				$item = &$items[$i];

				if(($showMode == 'NOT_COMPLETED' && $item['COMPLETED'] == 'Y')
					|| $showMode == 'COMPLETED' && $item['COMPLETED'] == 'N')
				{
					continue;
				}

				$processed++;

				$commData = array();

				if(isset($item['COMMUNICATIONS']))
				{
					foreach($item['COMMUNICATIONS'] as &$arComm)
					{
						CCrmActivity::PrepareCommunicationInfo($arComm);
						$commData[] = array(
							'id' => $arComm['ID'],
							'type' => $arComm['TYPE'],
							'value' => $arComm['VALUE'],
							'entityId' => $arComm['ENTITY_ID'],
							'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
							'entityTitle' => $arComm['TITLE'],
						);
					}
					unset($arComm);
				}

				$rowID = $editorCfg['PREFIX'];
				if($rowID !== '')
				{
					$rowID .= '_';
				}
				$rowID .= '_row_'.strval($processed);

				$editorItem = array(
					'ID' => $item['~ID'],
					'rowID' => $rowID,
					'typeID' => $item['~TYPE_ID'],
					'subject' => strval($item['~SUBJECT']),
					'description' => strval($item['~DESCRIPTION']),
					'direction' => intval($item['~DIRECTION']),
					'location' => strval($item['~LOCATION']),
					'start' => isset($item['~START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($item['~START_TIME']), 'FULL', SITE_ID) : '',
					'end' => isset($item['~END_TIME']) ? ConvertTimeStamp(MakeTimeStamp($item['~END_TIME']), 'FULL', SITE_ID) : '',
					'deadline' => isset($item['~DEADLINE']) ? ConvertTimeStamp(MakeTimeStamp($item['~DEADLINE']), 'FULL', SITE_ID) : '',
					'completed' => strval($item['~COMPLETED']) == 'Y',
					'notifyType' => intval($item['~NOTIFY_TYPE']),
					'notifyValue' => intval($item['~NOTIFY_VALUE']),
					'priority' => intval($item['~PRIORITY']),
					'responsibleID' => isset($item['~RESPONSIBLE_ID'][0]) ? intval($item['~RESPONSIBLE_ID']) : 0,
					'responsibleName' => isset($item['~RESPONSIBLE_FULL_NAME'][0]) ? $item['~RESPONSIBLE_FULL_NAME'] : GetMessage('CRM_UNDEFINED_VALUE'),
					'storageTypeID' => intval($item['STORAGE_TYPE_ID']),
					'files' => $item['FILES'],
					'webdavelements' => $item['WEBDAV_ELEMENTS'],
					'associatedEntityID' => isset($item['~ASSOCIATED_ENTITY_ID']) ? intval($item['~ASSOCIATED_ENTITY_ID']) : 0,
					'communications' => $commData
				);

				if(isset($item['OWNER_TYPE_ID']) && isset($item['OWNER_ID']))
				{
					$editorItem['ownerType'] = CCrmOwnerType::ResolveName($item['OWNER_TYPE_ID']);
					$editorItem['ownerID'] = $item['OWNER_ID'];
					$editorItem['ownerTitle'] = CCrmOwnerType::GetCaption($item['OWNER_TYPE_ID'], $item['OWNER_ID']);
					$editorItem['ownerUrl'] = CCrmOwnerType::GetEntityShowPath($item['OWNER_TYPE_ID'], $item['OWNER_ID']);
				}

				$editorItems[] = $editorItem;

				$rowClass = 'crm-activity-row';
				if($processed % 2 === 0)
				{
					$rowClass .= ' crm-activity-row-even';
				}

				if(intval($item['~PRIORITY']) === CCrmActivityPriority::High)
				{
					$rowClass .= ' crm-activity-row-important';
				}
				?>
			<tr id="<?= htmlspecialcharsbx($rowID) ?>" class="<?=htmlspecialcharsbx($rowClass)?>" style="<?= $showTop > 0 && $processed > $showTop ? 'display:none;' : '' ?>">
				<td><!--Delete-->
					<?if(!$arResult['READ_ONLY']):?>
						<span class="crm-view-table-column-delete"></span>
					<?endif;?>
				</td>
				<td> <!--Type-->
					<a class="crm-activity-type" href="#"><?= $item['TYPE_NAME'] ?></a>
				</td>
				<td> <!--Subject-->
					<a class="crm-activity-subject" href="#"><?= $item['SUBJECT']?></a>
				</td>
				<td> <!--End time-->
					<? $deadline = isset($item['~DEADLINE']) ? MakeTimeStamp($item['~DEADLINE']) : null; ?>
					<span <?= $item['~COMPLETED'] !== 'Y' && $deadline !== null && $deadline < $now ? 'style="color:#ff0000;"' : '' ?>>
						<?= $deadline !== null ? htmlspecialcharsbx(CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', $deadline))) : '' ?>
					</span>
				</td>
				<td> <!--Responsible-->
			<span>
				<?= isset($item['RESPONSIBLE_FULL_NAME'][0]) ? $item['RESPONSIBLE_FULL_NAME'] : GetMessage('CRM_UNDEFINED_VALUE') ?>
			</span>
				</td>
			</tr>
				<?
			}
			unset($item);
			?>
		</tbody>
	</table>
	<?if($showTop > 0 && $processed > $showTop)
	{?>
	<div class="crm-activity-show-all-wrapper">
		<a href="#" class="crm-activity-command-show-all" ><?= str_replace('#COUNT#', strval($processed), GetMessage('CRM_ACTIVITY_SHOW_ALL'))?></a>
	</div>
	<?}

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:crm.activity.editor',
			'',
			array(
				'CONTAINER_ID' => $editorCfg['CONTAINER_ID'],
				'EDITOR_ID' => $editorCfg['EDITOR_ID'],
				'EDITOR_TYPE' => $editorCfg['EDITOR_TYPE'],
				'PREFIX' => $editorCfg['PREFIX'],
				'OWNER_TYPE' => $editorCfg['OWNER_TYPE'],
				'OWNER_ID' => $editorCfg['OWNER_ID'],
				'ENABLE_TASK_ADD' => $editorCfg['ENABLE_TASK_ADD'],
				'ENABLE_CALENDAR_EVENT_ADD' => $editorCfg['ENABLE_CALENDAR_EVENT_ADD'],
				'ENABLE_EMAIL_ADD' => $editorCfg['ENABLE_EMAIL_ADD'],
				'READ_ONLY' => $editorCfg['READ_ONLY'],
				'ENABLE_UI' => $editorCfg['ENABLE_UI'],
				'ENABLE_TOOLBAR' => $editorCfg['ENABLE_TOOLBAR'],
				'TOOLBAR_ID' => $toolbarID,
				'BUTTON_ID' => $editorCfg['BUTTON_ID'],
				'EDITOR_ITEMS' => $editorItems

			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
}
?>
<div class="crm-activity-container">
<?
	$selectorContainerID =  $arResult['PREFIX'].'_selector_container';
	$showRecentButtonID = $arResult['PREFIX'].'_show_recent_btn';
	$showHistoryButtonID = $arResult['PREFIX'].'_show_history_btn';
	$recentEditorID = $arResult['PREFIX'].'_recent_editor';
	$historyEditorID = $arResult['PREFIX'].'_history_editor';
?>
<div class="crm-activity-selector" style="display:none;">
	<ul id="<?=htmlspecialcharsbx($selectorContainerID)?>" class="bx-crm-view-fieldset-title">
		<li id="<?=htmlspecialcharsbx($showRecentButtonID)?>" class="bx-crm-view-fieldset-title-selected" onclick="BX.CrmActivityEditor.display('<?=$historyEditorID?>', false); BX.CrmActivityEditor.display('<?=$recentEditorID?>', true);">
			<span><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_CURRENT'))?></span>
		</li>
		<li id="<?=htmlspecialcharsbx($showHistoryButtonID)?>" onclick="BX.CrmActivityEditor.display('<?=$recentEditorID?>', false); BX.CrmActivityEditor.display('<?=$historyEditorID?>', true);">
			<span><?=htmlspecialcharsbx(GetMessage('CRM_ACTIVITY_HISTORY'))?></span>
		</li>
	</ul>
</div>
<?
	$prefixUpper = strtoupper($arResult['PREFIX']);
	$prefixLower = strtolower($arResult['PREFIX']);

	$editorCfg['PREFIX'] = $prefixUpper.'_RECENT';
	$editorCfg['EDITOR_ID'] = $recentEditorID;
	$editorCfg['EDITOR_TYPE'] = 'RECENT';
	$editorCfg['CONTAINER_ID'] = $prefixLower.'_recent_container';
	$editorCfg['ENABLE_TOOLBAR'] = true;
	$editorCfg['BUTTON_ID'] = $showRecentButtonID;
?>
<div id="<?= htmlspecialcharsbx($editorCfg['CONTAINER_ID']) ?>" class="crm-activity-current" style="">
<? __CrmActivityListRenderItems($arResult['ITEMS'], 'NOT_COMPLETED', $arResult['SHOW_TOP'], $editorCfg); ?>
</div>
<?
	$editorCfg['PREFIX'] = $prefixUpper.'_HISTORY';
	$editorCfg['EDITOR_ID'] = $historyEditorID;
	$editorCfg['EDITOR_TYPE'] = 'HISTORY';
	$editorCfg['CONTAINER_ID'] = $prefixLower.'_history_container';
	$editorCfg['ENABLE_TOOLBAR'] = false;
	$editorCfg['BUTTON_ID'] = $showHistoryButtonID;
	?>
<div id="<?= htmlspecialcharsbx($editorCfg['CONTAINER_ID']) ?>" class="crm-activity-history" style="display:none;">
	<? __CrmActivityListRenderItems($arResult['ITEMS'], 'COMPLETED', $arResult['SHOW_TOP'], $editorCfg); ?>
</div>
</div>
<script type="text/javascript">
BX.ready(function()
{
	var selector = BX('<?=CUtil::JSEscape($selectorContainerID)?>');
	if(selector)
	{
		var title;

		var fieldset = BX.findParent(selector, { "className": "bx-crm-view-fieldset" });
		if(fieldset)
		{
			title = BX.findChild(fieldset, { "className": "bx-crm-view-fieldset-title" });
			fieldset.replaceChild(selector, title)
		}
		else
		{
			// supporting of old templates
			var container = BX.findParent(selector, { "className": "bx-after-heading" });
			if(container)
			{

				title = BX.findChild(BX.findPreviousSibling(container, { "tagName": "TR" }), { "className": "bx-heading" }, true, false);
				BX.cleanNode(title, false);
				title.appendChild(selector);
			}
		}
	}

	BX.CrmActivityEditor.setDefault(BX.CrmActivityEditor.items['<?= CUtil::JSEscape($recentEditorID) ?>']);
});

</script>




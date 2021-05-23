<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$rubric = $arResult['RUBRIC'];
$rubricPresets = isset($rubric['FILTER_PRESETS']) ? $rubric['FILTER_PRESETS'] : null;
$rubricPresetQty = $rubricPresets ? count($rubricPresets) : 0;

$isFiltered = $arResult['IS_FILTERED'];
$searchTitle = ($arResult['GRID_FILTER_NAME'] !== '' ? $arResult['GRID_FILTER_NAME'] : GetMessage('M_CRM_ACTIVITY_LIST_FILTER_CUSTOM'));

$searchValue = $arResult['SEARCH_VALUE'];

$UID = $arResult['UID'];
$searchContainerID = $UID.'_search';
$filterContainerID = $UID.'_filter';
$stubID = $UID.'_stub';
$dispatcherData = array();

$filterPresets = $arResult['FILTER_PRESETS'];
$currentFilterPresetID = isset($arResult['GRID_FILTER_ID']) ? $arResult['GRID_FILTER_ID'] : '';
?>

<?if(!$rubric['ENABLED']):?>
<div id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm_search<?=$searchValue !== '' ? ' active' : ''?>">
	<div class="crm_input_container">
		<span class="crm_lupe"></span>
		<input class="crm_search_input" type="text" value="<?=htmlspecialcharsbx($searchValue)?>" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_LIST_SEARCH_PLACEHOLDER'))?>" />
	</div>
	<a class="crm_button"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_LIST_SEARCH_BUTTON'))?></a>
	<span class="crm_clear"></span>
</div>
<div class="crm_toppanel">
	<div id="<?=htmlspecialcharsbx($filterContainerID)?>" class="crm_filter">
		<span class="crm_filter_icon"></span>
		<?=htmlspecialcharsbx($searchTitle)?>
		<span class="crm_arrow_bottom"></span>
	</div>
</div>
<?endif;?>

<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
<?if($rubric['ENABLED']):?>
	<div class="crm_head_title tal m0" style="padding: 10px 5px 20px;"><?=htmlspecialcharsbx($rubric['TITLE'])?><span style="font-size: 13px;color: #87949b;"> <?=GetMessage('M_CRM_ACTIVITY_LIST_RUBRIC_LEGEND')?></span></div>
	<?if($rubricPresetQty > 0):?>
		<div class="crm_top_nav col<?=$rubricPresetQty?>">
			<ul>
				<?foreach($rubricPresets as $presetKey):
					$presetName = '';
					$isCurrent = false;
					if($presetKey === 'clear_filter'):
						$presetName = GetMessage('M_CRM_ACTIVITY_LIST_RUBRIC_FILTER_NONE');
						$isCurrent = $currentFilterPresetID === '';
					elseif(isset($filterPresets[$presetKey])):
						$presetName = $filterPresets[$presetKey]['name'];
						$isCurrent = $currentFilterPresetID === $presetKey;
					endif;
					if($presetName === '')
						continue;
					?>

					<li class="crm-filter-preset-button-container<?=$isCurrent ? ' current' : ''?>">
						<a class="crm-filter-preset-button" href="#"><?=htmlspecialcharsbx($presetName)?></a>
						<input type="hidden" class="crm-filter-preset-data" value="<?=htmlspecialcharsbx($presetKey)?>"/>
					</li>
				<?endforeach;?>
			</ul>
			<div class="clb"></div>
		</div>
	<?endif;?>
<?endif;?>
<?$qty = count($arResult['ITEMS'])?>
<div id="<?=htmlspecialcharsbx($stubID)?>" class="crm_contact_info tac"<?=$qty > 0 ? ' style="display:none;"' : ''?>>
	<strong style="color: #9ca9b6;font-size: 15px;display: inline-block;margin: 30px 0;"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_LIST_NOTHING_FOUND'))?></strong>
</div>
<ul class="crm_company_list"<?=$qty === 0 ? ' style="display:none;"' : ''?>>
<?for($i = 0; $i < $qty; $i++):
		$item = &$arResult['ITEMS'][$i];
		$dataItem = CCrmMobileHelper::PrepareActivityData($item);
		$dispatcherData[] = $dataItem;

		$isCompleted = $dataItem['COMPLETED'];
		$isExpired = $dataItem['IS_EXPIRED'];
		$isImportant = $dataItem['IS_IMPORTANT'];

		$wrapperStyle = ($isCompleted ? 'background:#f5f6f8;' : ($isExpired ? 'background:#fae9e7;' : ''));

		if ($dataItem['TYPE_ID'] === CCrmActivityType::Task)
		{
			$taskId = $item['ASSOCIATED_ENTITY_ID'];
			$onClick = CMobileHelper::getTaskLink($taskId);
		}
		else
		{
			$redirectParams = ['url' => $item['SHOW_URL']];
			$onClick = 'BX.CrmMobileContext.redirect('.CUtil::PhpToJSObject($redirectParams).');';
		}
		?><li class="crm_company_list_item" data-entity-id="<?=$item['ID']?>" style="<?=$wrapperStyle?>" onclick="<?=$onClick?>">
			<?if($dataItem['LIST_IMAGE_URL'] !== ''):?>
				<img src="<?=htmlspecialcharsbx($dataItem['LIST_IMAGE_URL'])?>" style="width:20px;padding:10px 15px 0 8px;float:left;" />
			<?endif;?>
			<a class="crm_company_title"<?=$isCompleted ? ' style="text-decoration:line-through; color:#7c8182;"' : ''?>><?=htmlspecialcharsbx($dataItem['SUBJECT'])?><?if($isImportant):?><span class="crm_important"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_LIST_IMPORTANT'))?></span><?endif;?></a>
			<div class="crm_company_company">
				<?if($isExpired):?>
					<span class="fwb"<?=$isExpired ? ' style="color:#e20707;"' : ''?>><?=htmlspecialcharsbx($dataItem['DEAD_LINE'])?></span>
				<?else:?>
					<?=htmlspecialcharsbx($dataItem['DEAD_LINE'] !== '' ? $dataItem['DEAD_LINE'] : GetMessage('M_CRM_ACTIVITY_LIST_TIME_NOT_DEFINED'))?>
				<?endif;?>
				<?if($dataItem['OWNER_TITLE'] !== ''):?>
				&nbsp;-&nbsp;<span class="fwb"><?=htmlspecialcharsbx($dataItem['OWNER_TITLE'])?></span>
				<?endif;?>
			</div>
			<div class="clb"<?=$isImportant ? ' style="margin-bottom:10px;"' : ''?>></div>
		</li><?php
		unset($item);
	endfor;

if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):
?><li class="crm_company_list_item crm_company_list_item_wait"></li><?
endif;
?></ul></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_ACTIVITY_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_ACTIVITY_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_ACTIVITY_LIST_LOAD_TEXT')?>'
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'ACTIVITY',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'ACTIVITY_SHOW_URL_TEMPLATE' => $arParams['ACTIVITY_SHOW_URL_TEMPLATE'],
							'ACTIVITY_EDIT_URL_TEMPLATE' => $arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			var filterPresets = [];
			<?foreach($arResult['FILTER_PRESETS'] as $key => &$preset):
			?>filterPresets.push(
				{
					id: '<?=CUtil::JSEscape($key)?>',
					name: '<?=CUtil::JSEscape($preset['name'])?>',
					fields: <?=CUtil::PhpToJSObject($preset['fields'])?>
				});<?
			echo "\n";
			endforeach;
			unset($preset);
			echo "\n";
			?>
			BX.CrmActivityListView.messages =
			{
				customFilter: '<?=GetMessageJS('M_CRM_ACTIVITY_LIST_FILTER_CUSTOM')?>',
				important: '<?=GetMessageJS('M_CRM_ACTIVITY_LIST_IMPORTANT')?>',
				emptyTime: '<?=GetMessageJS('M_CRM_ACTIVITY_LIST_TIME_NOT_DEFINED')?>',
				menuCreateCall: '<?=GetMessageJS('M_CRM_ACTIVITY_LIST_CREATE_CALL')?>',
				menuCreateMeeting: '<?=GetMessageJS('M_CRM_ACTIVITY_LIST_CREATE_MEETING')?>',
				menuCreateEmail: '<?=GetMessageJS('M_CRM_ACTIVITY_LIST_CREATE_EMAIL')?>'

			};

			BX.CrmActivityListView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					dispatcher: dispatcher,
					wrapperId: '<?=CUtil::JSEscape($UID)?>',
					searchContainerId: '<?=CUtil::JSEscape($searchContainerID)?>',
					filterContainerId: '<?=CUtil::JSEscape($filterContainerID)?>',
					stubId: '<?=CUtil::JSEscape($stubID)?>',
					nextPageUrl: '<?=CUtil::JSEscape($arResult['NEXT_PAGE_URL'])?>',
					searchPageUrl: '<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>',
					callEditUrl: '<?=CUtil::JSEscape($arResult['CREATE_CALL_URL'])?>',
					meetingEditUrl: '<?=CUtil::JSEscape($arResult['CREATE_MEETING_URL'])?>',
					emailEditUrl: '<?=CUtil::JSEscape($arResult['CREATE_EMAIL_URL'])?>',
					reloadUrl: '<?=CUtil::JSEscape($arResult['RELOAD_URL'])?>',
					filterPresets: filterPresets,
					enablePresetButtons: <?=$rubricPresetQty > 0 ? 'true' : 'false'?>,
					permissions: <?=CUtil::PhpToJSObject($arResult['PERMISSIONS'])?>,
					isFiltered: <?=$arResult['IS_FILTERED'] ? 'true' : 'false'?>
				}
			);
		}
	);
</script>


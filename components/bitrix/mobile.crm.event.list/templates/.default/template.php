<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

$rubric = $arResult['RUBRIC'];
$rubricPresets = isset($rubric['FILTER_PRESETS']) ? $rubric['FILTER_PRESETS'] : null;
$rubricPresetQty = $rubricPresets ? count($rubricPresets) : 0;

$UID = $arResult['UID'];
$dispatcherData = array();
$filterPresets = $arResult['FILTER_PRESETS'];
$currentFilterPresetID = isset($arResult['GRID_FILTER_ID']) ? $arResult['GRID_FILTER_ID'] : '';
?>

<div id="<?=htmlspecialcharsbx($UID)?>" class="crm_wrapper">
<?if($rubric['ENABLED']):?>
	<div class="crm_head_title tal m0" style="padding: 10px 5px 20px;"><?=htmlspecialcharsbx($rubric['TITLE'])?><span style="font-size: 13px;color: #87949b;"> <?=GetMessage('M_CRM_EVENT_LIST_RUBRIC_LEGEND')?></span></div>
	<?if($rubricPresetQty > 0):?>
		<div class="crm_top_nav col<?=$rubricPresetQty?>">
			<ul>
				<?foreach($rubricPresets as $presetKey):
					$presetName = '';
					$isCurrent = false;
					if($presetKey === 'clear_filter'):
						$presetName = GetMessage('M_CRM_EVENT_LIST_RUBRIC_FILTER_NONE');
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
<ul class="crm_dealings_list">
	<?foreach($arResult['ITEMS'] as &$item):?>
		<?
		$dataItem = CCrmMobileHelper::PrepareEventData($item);
		$dispatcherData[] = $dataItem;
		?>
		<li class="crm_history_list_item" data-entity-id="<?=$item['ID']?>">
			<div class="crm_history_title"><?=htmlspecialcharsbx($dataItem['EVENT_NAME'])?></div>
			<div class="crm_history_descr">
				<pre>
					<?if($dataItem['EVENT_TEXT_2'] !== ''):?>
						<?=$dataItem['EVENT_TEXT_1']?> &rarr; <?=$dataItem['EVENT_TEXT_2']?>
					<?else:?>
						<?=$dataItem['EVENT_TEXT_1']?>
					<?endif;?>
				</pre>
			</div>
			<div class="crm_history_cnt"><?=htmlspecialcharsbx($dataItem['DATE_CREATE'])?>, <?=htmlspecialcharsbx($dataItem['CREATED_BY_FORMATTED_NAME'])?></div>
			<div class="clb"></div>
		</li>
	<?endforeach;?>
	<?unset($item);?>

	<?if($arResult['PAGE_NEXT_NUMBER'] <= $arResult['PAGE_NAVCOUNT']):?>
		<li class="crm_history_list_item crm_history_list_item_wait"></li>
	<?endif;?>
</ul></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmMobileContext.getCurrent().enableReloadOnPullDown(
				{
					pullText: '<?= GetMessage('M_CRM_EVENT_LIST_PULL_TEXT')?>',
					downText: '<?= GetMessage('M_CRM_EVENT_LIST_DOWN_TEXT')?>',
					loadText: '<?= GetMessage('M_CRM_EVENT_LIST_LOAD_TEXT')?>'
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'EVENT',
					data: <?=CUtil::PhpToJSObject($dispatcherData)?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			var filterPresets = [];
			<?foreach($filterPresets as $key => &$preset):
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
			?>filterPresets.push(
				{
					id: 'clear_filter',
					name: '<?=CUtil::JSEscape(GetMessage('M_CRM_EVENT_LIST_FILTER_NONE'))?>',
					fields: {}
				});

			BX.CrmEventListView.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					dispatcher: dispatcher,
					wrapperId: '<?=CUtil::JSEscape($UID)?>',
					searchContainerId: '<?=CUtil::JSEscape($searchContainerID)?>',
					filterContainerId: '<?=CUtil::JSEscape($filterContainerID)?>',
					nextPageUrl: '<?=CUtil::JSEscape($arResult['NEXT_PAGE_URL'])?>',
					searchPageUrl: '<?=CUtil::JSEscape($arResult['SEARCH_PAGE_URL'])?>',
					filterPresets: filterPresets,
					enablePresetButtons: <?=$rubricPresetQty > 0 ? 'true' : 'false'?>
				}
			);
		}
	);
</script>

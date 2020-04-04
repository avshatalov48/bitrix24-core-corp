<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'DEAL_FUNNEL',
			'ACTIVE_ITEM_ID' => 'DEAL_FUNNEL',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_CATEGORY' => isset($arParams['PATH_TO_DEAL_CATEGORY']) ? $arParams['PATH_TO_DEAL_CATEGORY'] : '',
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

for ($i=0, $filterLength = sizeof($arResult['FILTER']); $i < $filterLength; $i++)
{
	$filterField = &$arResult['FILTER'][$i];
	$filterID = $filterField['id'];
	$filterType = $filterField['type'];
	$enable_settings = $filterField['enable_settings'];

	if ($filterType === 'user')
	{
		$userID = isset($_REQUEST[$filterID]) ? intval($_REQUEST[$filterID]) : 0;
		$userName = $userID > 0 ? CCrmViewHelper::GetFormattedUserName($userID) : '';

		ob_start();

		CCrmViewHelper::RenderUserCustomSearch(
			array(
				'ID' => "{$filterID}_SEARCH",
				'SEARCH_INPUT_ID' => "{$filterID}_NAME",
				'SEARCH_INPUT_NAME' => "{$filterID}_name",
				'DATA_INPUT_ID' => $filterID,
				'DATA_INPUT_NAME' => $filterID,
				'COMPONENT_NAME' => "{$filterID}_SEARCH",
				'SITE_ID' => SITE_ID,
				'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
				'USER' => array('ID' => $userID, 'NAME' => $userName),
				'DELAY' => 100
			)
		);

		$arResult['FILTER'][$i]['value'] = ob_get_clean();
		$arResult['FILTER'][$i]['type'] = 'custom';
	}
}
unset($filterField);

$bUseAmCharts = $arResult['USE_AMCHARTS'];

$arResult['GRID_DATA'] = array();
$arResult['GRID_DATA_NO'] = array();
$bafterWON = false;
$i = 0;

$arColor = array();

$processColor = \CCrmViewHelper::PROCESS_COLOR;
$successColor = \CCrmViewHelper::SUCCESS_COLOR;
$failureColor = \CCrmViewHelper::FAILURE_COLOR;

$stageInfos = \CCrmViewHelper::GetDealStageInfos($arResult['CATEGORY_ID']);

foreach ($arResult['FUNNEL'] as $aData){
	$stageID = $aData['STAGE_ID'];
	$stageSemanticID = CCrmDeal::GetSemanticID($stageID, $arResult['CATEGORY_ID']);
	$isSuccess = $stageSemanticID === Bitrix\Crm\PhaseSemantics::SUCCESS;
	$isFailure = $stageSemanticID === Bitrix\Crm\PhaseSemantics::FAILURE;

	$stageInfo = isset($stageInfos[$stageID]) ? $stageInfos[$stageID] : null;
	$backgroundColor = is_array($stageInfo) && isset($stageInfo['COLOR'])
		? $stageInfo['COLOR'] :
		($isSuccess ? $successColor : ($isFailure ? $failureColor : $processColor));
	$arColor[] = $backgroundColor;

	foreach ($arResult['CURRENCY_LIST'] as $k => $v)
		$aData[$k] = CCrmCurrency::MoneyToString($aData['OPPORTUNITY_FUNNEL_'.$k], $k, '<nobr>#</nobr>');

	$str = '';
	if ($i == 0)
		$str = '<div style="margin:auto; width: 250px"></div>';

	if(!$bUseAmCharts)
		$aData['FUNNEL'] = $str.'<div class="funnel-cell" style="margin:auto; width: '.$aData['PROCENT'].'%; height: 20px; background-color: '.$backgroundColor.'"></div>';
	else
		$aData['PROCENT_ORIG'] = $aData['PROCENT'];
	$aData['PROCENT'] = $aData['PROCENT'].'%';
	$aData['TITLE_ORIG'] = $aData['TITLE'];
	$aData['TITLE'] = '<a href="'.
		CComponentEngine::MakePathFromTemplate(
			isset($arResult['PATH_TO_DEAL_CATEGORY']) ?
				$arResult['PATH_TO_DEAL_CATEGORY'] : $arResult['PATH_TO_DEAL_LIST']
		).'?STAGE_ID[0]='.$stageID.'&apply_filter=Y">'.htmlspecialcharsbx($aData['TITLE']).'</a>';

	$arResult['GRID_DATA'.($bafterWON ? '_NO' : '')][] = array(
		'id' => $i++,
		'data' => $aData
	);

	if ($isSuccess)
	{
		$bafterWON = true;
		$i = 0;
	}
}

$containerID = strtolower($arResult['GRID_ID']).'_container';


$configFormID = strtolower($arResult['GRID_ID']).'_config';
$typeInputID = strtolower($arResult['GRID_ID']).'_type_id';
$categoryInputID = strtolower($arResult['GRID_ID']).'_category_id';

?><div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-deal-funnel-wrapper">
<form method="POST"  action="<?=POST_FORM_ACTION_URI?>" name="<?=htmlspecialcharsbx($configFormID)?>" id="<?=htmlspecialcharsbx($configFormID)?>">
<?=bitrix_sessid_post();?>
	<input type="hidden" name="CATEGORY_ID" id="<?=htmlspecialcharsbx($categoryInputID)?>" value="<?=htmlspecialcharsbx($arResult['CATEGORY_ID'])?>"/>
	<input type="hidden" name="FUNNEL_TYPE" id="<?=htmlspecialcharsbx($typeInputID)?>" value="<?=htmlspecialcharsbx($arResult['FUNNEL_TYPE'])?>"/>
</form>

<div class="crm-deal-funnel-wrapper crm-deal-funnel-wrapper-won"><?
$toolbarID = strtolower($arResult['GRID_ID']).'_toolbar';
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.toolbar',
	'',
	array(
		'TOOLBAR_ID' => $toolbarID,
		'BUTTONS' => array(
			array(
				'TEXT' => GetMessage('CRM_DEAL_FUNNEL_SHOW_FILTER_SHORT'),
				'TITLE' => GetMessage('CRM_DEAL_FUNNEL_SHOW_FILTER'),
				'ICON' => 'crm-filter-light-btn',
				'ALIGNMENT' => 'right',
				'ONCLICK' => "BX.InterfaceGridFilterPopup.toggle('{$arResult['GRID_ID']}', this)"
			)
		)
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>
<div class="crm-deal-funnel-title"><?=htmlspecialcharsbx(GetMessage("DEAL_STAGES_WON"))?></div>
<? if ($bUseAmCharts): ?>
<?php
// amCharts
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/amcharts/3.3/amcharts.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/amcharts/3.3/funnel.js');

$funnelData = array(
	'FUNNEL' => array('rowNum' => 0, 'data' => array()),
	'FUNNEL_NO' => array('rowNum' => 0, 'data' => array())
);

$grids = array($arResult['GRID_ID'] => '', $arResult['GRID_ID'].'_NO' => '_NO');
foreach ($grids as $gridID => $postfix)
{
	$funnelData['FUNNEL'.$postfix]['gridId'] = $gridID;
	if (isset($arResult['GRID_DATA'.$postfix]) && is_array($arResult['GRID_DATA'.$postfix]))
	{
		// number of rows
		$funnelData['FUNNEL'.$postfix]['rowNum'] = count($arResult['GRID_DATA'.$postfix]);

		// get viewed columns
		$gridColumns = CCrmViewHelper::GetGridOptionalColumns($gridID);
		$dataColumns = array();
		$bEmptyCols = empty($gridColumns);
		foreach($arResult['HEADERS'] as $hdr)
		{
			if(($bEmptyCols && $hdr['default']==true) || in_array($hdr['id'], $gridColumns))
				$dataColumns[$hdr['id']] = $hdr['name'];
		}
		$nRows = $sumValues = 0;
		foreach ($arResult["GRID_DATA{$postfix}"] as $index => $row)
		{
			$dataRow = array('title' => '', 'value' => 0);
			$n = 0;
			foreach ($dataColumns as $colIndex => $colName)
			{
				if ($colIndex !== 'FUNNEL' && isset($row['data'][$colIndex]))
				{
					$title = $row['data'][(($colIndex === 'TITLE') ? 'TITLE_ORIG' : $colIndex)];
					$dataRow['title'] .= '<div>'.$colName.': '.htmlspecialcharsbx($title).'</div>';
					$n++;
				}
			}
			$dataRow['title'] = '<span>'.$dataRow['title'].'</span>';
			$dataRow['label'] = $row['data']['TITLE_ORIG'].' ('.
				$row['data']['PROCENT'].')';
			if ($n > 0 && isset($row['data']['PROCENT_ORIG']))
				$dataRow['value'] = $row['data']['PROCENT_ORIG'];
			$sumValues += $dataRow['value'];
			if ($n > 0)
				$funnelData["FUNNEL{$postfix}"]['data'][] = $dataRow;
			unset($dataRow);
			$nRows++;

			$arResult["GRID_DATA{$postfix}"][$index] = $row;
		}
		$funnelData["FUNNEL{$postfix}"]['sumValues'] = $sumValues;
		$arResult['GRID_ROWS_NUMBER'.$postfix] = $nRows;
	}
}
?>
<style type="text/css">
	div.funnel-chart {
		background-color: white;
		border: 1px solid #D0D8D9;
		box-shadow: 1px 1px 2px 0 rgba(88, 112, 118, 0.1);
		border-radius: 2px;
		color: gray;
		font-size: 14px;
		margin: 0 3px 23px;
		overflow: auto;
	}
	div.funnel-chart>div {
		height: <?=CUtil::JSEscape($arResult['GRID_ROWS_NUMBER']*60)?>px;
		width: 776px;
		margin-left: auto;
		margin-right: auto;
	}
	div.funnel-chart>div.funnel-chart-no {
		height: <?=CUtil::JSEscape($arResult['GRID_ROWS_NUMBER_NO']*60)?>px;
	}
	a.funnel-chart-show:before {
		background:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAYAAAADCAIAAAA/Y+msAAAABnRSTlMA/wD/AP83WBt9AAAAJklEQVQImVXHwQ0AMAzCQKf7sSwMSH6R6td52gJJAEnAuz+Mbf4WzdgMSstcwD0AAAAASUVORK5CYII=") no-repeat;
	}
	a.funnel-chart-hide:before {
		background:url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAYAAAADCAIAAAA/Y+msAAAABnRSTlMA/wD/AP83WBt9AAAAH0lEQVQImWPctGkTAypg8vX1Reb7+voyQSg4n4GBAQC4MgW4jUjKCwAAAABJRU5ErkJggg==") no-repeat;
	}
	a.funnel-chart-show:before,
	a.funnel-chart-hide:before {
		content: "";
		height: 3px;
		width: 6px;
		display: inline-block;
		margin: 0 4px 2px 0;
	}
	a.funnel-chart-show,
	a.funnel-chart-hide {
		font-size: 12px;
		color: gray;
		cursor: pointer;
		border-bottom: 1px dashed gray;
		margin-left: 4px;
		text-decoration: none;
	}
	div.crm-deal-funnel-title { margin-top: 20px; }
</style>
<div style="margin-bottom: 14px;"><a id="funnel-chart-showhide" class="funnel-chart-show"><?= GetMessage('FUNNEL_CHART_HIDE') ?></a></div>
<div class="funnel-chart"><div id="<?php echo 'chart_'.$arResult['GRID_ID']; ?>"></div></div>
<? endif; // if ($bUseAmCharts): ?><?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'ROWS' => $arResult['GRID_DATA'],
		'EDITABLE' => 'N',
		'ACTION_ALL_ROWS' => false,
		'AJAX_MODE' => 'N',
		'FILTER' => $arResult['FILTER'],
		'FILTER_TEMPLATE' => 'popup',
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS']
	),
	$component
);
?></div>
<div class="crm-deal-funnel-wrapper crm-deal-funnel-wrapper-lose">
<div class="crm-deal-funnel-title"><?=htmlspecialcharsbx(GetMessage("DEAL_STAGES_LOSE"))?></div>
<div style="margin-bottom: 14px;"><a id="funnel-chart-showhide-no" class="funnel-chart-hide"><?= GetMessage('FUNNEL_CHART_SHOW') ?></a></div>
<div class="funnel-chart"><div id="<?php echo 'chart_'.$arResult['GRID_ID'].'_NO'; ?>" class="funnel-chart-no"></div></div><?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'].'_NO',
		'HEADERS' => $arResult['HEADERS'],
		'ROWS' => $arResult['GRID_DATA_NO'],
		'EDITABLE' => 'N',
		'ACTION_ALL_ROWS' => false,
		'AJAX_MODE' => 'N'
	),
	$component
);
?></div>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var categoryList = BX.CrmDealCategory.getListItems(<?=CUtil::PhpToJSObject($arResult['CATEGORY_INFO'])?>);
			var categorySelector = BX.CrmSelector.create(
				'<?= CUtil::JSEscape(strtolower($arResult['GRID_ID']).'_category_selector') ?>',
				{
					'container': BX('<?=CUtil::JSEscape($toolbarID)?>'),
					'title': '<?=GetMessageJS('CRM_DEAL_CATEGORY_SELECTOR_TITLE') ?>',
					'selectedValue': '<?= CUtil::JSEscape($arResult['CATEGORY_ID'])?>',
					'items': categoryList,
					'layout': { 'position': 'first' }
				}
			);
			categorySelector.layout();
			categorySelector.addOnSelectListener(
				function(selector, item)
				{
					var input = BX('<?=CUtil::JSEscape($categoryInputID)?>');
					var form = BX('<?=CUtil::JSEscape($configFormID)?>');
					if(item && input && form)
					{
						var value = item.getValue();
						if(input.value != value)
						{
							input.value = value;
							BX.submit(form);
						}
					}
				}
			);
		}
	);
</script>
<?if($arResult['ALLOW_FUNNEL_TYPE_CHANGE'] === 'Y'):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var typeSelector = BX.CrmSelector.create(
					'<?= CUtil::JSEscape(strtolower($arResult['GRID_ID']).'_type_selector') ?>',
					{
						'container': BX('<?=CUtil::JSEscape($toolbarID)?>'),
						'title': '<?=GetMessageJS('CRM_DEAL_FUNNEL_TYPE_SELECTOR_TITLE') ?>',
						'selectedValue': '<?= CUtil::JSEscape($arResult['FUNNEL_TYPE'])?>',
						'items': <?=CUtil::PhpToJSObject($arResult['FUNNEL_TYPE_VALUES'])?>,
						'layout': { 'offset': { 'left': '7px' } }
					}
			);
			typeSelector.layout();
			typeSelector.addOnSelectListener(
					function(selector, item)
					{
						var input = BX('<?=CUtil::JSEscape($typeInputID)?>');
						var form = BX('<?=CUtil::JSEscape($configFormID)?>');
						if(item && input && form)
						{
							var value = item.getValue();
							if(input.value != value)
							{
								input.value = value;
								BX.submit(form);
							}
						}
					}
			);
		}
	);
</script>
<?endif;?>
<? if ($bUseAmCharts): ?>
<script type="text/javascript">
	function reportChartShowHide()
	{
		var chartContainer = BX.findNextSibling(BX.findParent(this, {"tag": "div"}), {"tag": "div", "class": "funnel-chart"});
		if (chartContainer)
		{
			if (chartContainer.style.display === "none")
			{
				chartContainer.style.display = "";
				this.innerHTML = BX.util.htmlspecialchars("<?= CUtil::JSEscape(GetMessage('FUNNEL_CHART_HIDE')) ?>");
				this.className = "funnel-chart-show";
				if (chartContainer.bxFunnelChart)
					chartContainer.bxFunnelChart.invalidateSize();
			}
			else
			{
				chartContainer.style.display = "none";
				this.innerHTML = BX.util.htmlspecialchars("<?= CUtil::JSEscape(GetMessage('FUNNEL_CHART_SHOW')) ?>");
				this.className = "funnel-chart-hide";
			}
		}
	}

	function drawChart()
	{
		var chartLinks = [BX("funnel-chart-showhide"), BX("funnel-chart-showhide-no")];
		var funnelData = <?=CUtil::PhpToJSObject($funnelData)?>;
		var colors = <?=CUtil::PhpToJSObject($arColor)?>;
		var i;
		for (i = 0; i < chartLinks.length; i++)
		{
			if (chartLinks[i])
				BX.bind(chartLinks[i], "click", reportChartShowHide);
		}
		for (i in funnelData)
		{
			var info = funnelData[i];
			var funnelDiv = BX("chart_" + info["gridId"]);
			if (funnelDiv)
			{
				var chartContainer = BX.findParent(funnelDiv, {"tag": "div"});
				if (info['sumValues'] > 0)
				{
					var chart = new AmCharts.AmFunnelChart();
					chart.titleField = "title";
					chart.balloon.cornerRadius = 0;
					chart.numberFormatter = {
						precision: -1,
						decimalSeparator: '.',
						thousandsSeparator:' '
					};
					chart.percentFormatter = {
						precision: 2,
						decimalSeparator: '.',
						thousandsSeparator:' '
					};
					chart.marginRight = 300;
					chart.marginLeft = 15;
					chart.labelPosition = "right";
					chart.funnelAlpha = 0.9;
					chart.valueField = "value";
					chart.dataProvider = info["data"];
					chart.startX = 0;
					chart.balloon.animationTime = 0.2;
					chart.neckWidth = "40%";
					chart.startAlpha = 0;
					chart.neckHeight = "30%";
					chart.balloonText = "<div>[[title]]</div>";
					chart.labelText = "[[label]]";
					chart.colors = colors;
					chart.write(funnelDiv.id);
					if (chartContainer)
						chartContainer.bxFunnelChart = chart;
				}
				else
				{
					chartContainer.innerHTML = "<?=CUtil::JSEscape(GetMessage('FUNNEL_CHART_NO_DATA'))?>";
				}
				var id = funnelDiv.id;
				var l = id.length;
				if (id.substr(l-3,3) === "_NO")
					chartContainer.style.display = "none";
			}
		}
	}

	<? if (\Bitrix\Main\Page\Frame::isAjaxRequest()):?>
		drawChart();
	<? else: ?>
		AmCharts.ready(drawChart);
	<? endif ?>

</script>
<? endif; // if ($bUseAmCharts): ?>
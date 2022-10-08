<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \CrmVolumeComponent $component */



use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

\Bitrix\Main\UI\Extension::load(['ui.design-tokens']);

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'CONFIG',
			'ACTIVE_ITEM_ID' => '',
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



if ($isBitrix24Template && !$arParams['IS_AJAX_REQUEST'] && $arResult['DATA_COLLECTED'])
{
	$this->SetViewTarget("inside_pagetitle", 10);

	?>
	<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
		<?

		$APPLICATION->IncludeComponent(
			'bitrix:main.ui.filter',
			'',
			array(
				'DISABLE_SEARCH' => true,
				'GRID_ID' => $arResult['GRID_ID'],
				'FILTER_ID' => $arResult['FILTER_ID'],
				'FILTER' => $arResult["FILTER"],
				'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
				'ENABLE_LIVE_SEARCH' => true,
				'ENABLE_LABEL' => true,
				'RESET_TO_DEFAULT_MODE' => false,
				'MESSAGES' => array(
					'MAIN_UI_FILTER__DATE_PREV_DAYS_LABEL' => Loc::getMessage('CRM_VOLUME_DATE_PERIOD_PREV_DAYS_LABEL'),
				),
			),
			$component
		);

		?>
	</div>
	<?

	$this->EndViewTarget();
}


if ($isBitrix24Template)
{
	$this->SetViewTarget("inside_pagetitle", 10);

	if (!$arResult['QUEUE_RUNNING'] && $arResult['DATA_COLLECTED'])
	{
		?>
		<a href="<?= $component->getActionUrl(array('reload' => 'Y')); ?>" class="ui-btn ui-btn-primary crm-volume-reload-link">
			<?= Loc::getMessage('CRM_VOLUME_MEASURE_DATA_REPEAT') ?>
		</a>
		<?
	}

	$this->EndViewTarget();
}


?>
<div id="bx-crm-volume-main-block" class="crm-volume-wrap <? if ($arResult['QUEUE_RUNNING']): ?>crm-volume-running<? endif; ?>">
	<div id="bx-crm-volume-stepper" class="crm-volume-stepper bx-ui-crm-volume-stepper" <?
		if ((isset($arResult['HAS_WORKER_IN_PROCESS']) && $arResult['HAS_WORKER_IN_PROCESS'] !== true) || empty($arResult["PROCESS_BAR"])): ?>style="display: none"<? endif; ?>><?

		if (isset($arResult['HAS_WORKER_IN_PROCESS']) && $arResult['HAS_WORKER_IN_PROCESS'] && $arResult["RELOAD"] !== true)
		{
			echo $arResult['PROCESS_BAR'];
		}
	?></div>

	<div id="bx-crm-volume-message-alert" class="crm-volume-alert ui-alert ui-alert-warning ui-alert-xs" style="display: none">
		<span class="ui-btn-message"><?= Loc::getMessage('CRM_VOLUME_CLOSE_WARNING'); ?></span>
	</div>

	<div id="bx-crm-volume-reload-warning" class="crm-volume-info-control-panel ui-alert ui-alert-warning ui-alert-xs" <? if ($arResult["NEED_RELOAD"] === 0 || $arResult['QUEUE_RUNNING'] || !$arResult['DATA_COLLECTED']): ?>style="display: none" <? endif; ?>>
		<span class="ui-btn-message">
			<? if ($arResult["NEED_RELOAD"] === 2):?>
				<?= Loc::getMessage('CRM_VOLUME_NEED_RELOAD_COMMENT'); ?>
			<? else:?>
				<?= Loc::getMessage('CRM_VOLUME_AGENT_FINISHED_COMMENT'); ?>
			<? endif ?>
		</span>
		&nbsp;
		<a href="<?= $component->getActionUrl(array('reload' => 'Y')); ?>" class="crm-volume-info-control-panel-link crm-volume-reload-link">
			<?= Loc::getMessage('CRM_VOLUME_MEASURE_DATA_REPEAT'); ?>
		</a>
	</div>

	<div class="crm-volume-wrap">
<?

if(!$arResult['DATA_COLLECTED'] || $arResult['QUEUE_RUNNING'])
{
	?>
	<div id="bx-crm-volume-starter" class="crm-volume-container">
		<div class="crm-volume-left-block">
			<h1 class="crm-volume-main-title"><?= Loc::getMessage('CRM_VOLUME_START_TITLE')?></h1>
			<div class="crm-volume-text">
				<div class="crm-volume-item"><?= Loc::getMessage('CRM_VOLUME_START_COMMENT'); ?></div>
			</div>
		</div>
		<div class="crm-volume-right-block">
			<div class="crm-volume-logo-main"></div>
		</div>
	</div>
	<div id="bx-crm-volume-buttons" class="crm-volume-button-container">
		<button id="bx-crm-volume-link-measure" class="ui-btn ui-btn-success"><?= Loc::getMessage("CRM_VOLUME_MEASURE_DATA"); ?></button>
	</div>

	<div id="bx-crm-volume-process" class="crm-volume-container crm-volume-wrap-state">
		<h1 id="bx-crm-volume-process-title" class="crm-volume-title"><?= Loc::getMessage('CRM_VOLUME_MEASURE_TITLE')?></h1>
		<div id="bx-crm-volume-loader" class="crm-volume-loader">
			<div class="crm-volume-loader-logo"></div>
			<svg class="crm-volume-circular" viewBox="25 25 50 50">
				<circle class="crm-volume-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
			</svg>
		</div>
		<div id="bx-crm-volume-loader-progress-bar" class="crm-volume-loader-progress-bar">
			<div class="crm-volume-loader-progress-bar-number">0%</div>
			<div class="crm-volume-loader-progress-bar-line">
				<div class="crm-volume-loader-progress-bar-line-active"></div>
			</div>
		</div>
		<div class="crm-volume-info" >
			<?= Loc::getMessage("CRM_VOLUME_MEASURE_PROCESS"); ?>
			<span id="bx-crm-volume-process-cancel" class="crm-volume-cancel-link" >
				<?= Loc::getMessage('CRM_VOLUME_CANCEL') ?>
			</span>
		</div>
	</div>
	<?
}
else
{
	?>
	<div class="crm-volume-header-container">
		<div class="crm-volume-header-icon crm-volume-header-icon-default-disk"></div>
		<div class="crm-volume-header-block">
			<div class="crm-volume-header-title"><?= Loc::getMessage("CRM_VOLUME_CLEARING") ?></div>

			<div id="bx-crm-volume-total-size" class="crm-volume-header-amount-info">
				<? if ($arResult['TOTALS']['TOTAL_SIZE'] > 0): ?>
					<?= Loc::getMessage("CRM_VOLUME_TOTAL_USEAGE", array('#FILE_SIZE#' => $arResult['TOTALS']['TOTAL_SIZE_FORMAT'])) ?>
				<? endif ?>
			</div>

			<div id="bx-crm-volume-file-size" class="crm-volume-header-amount-info" <? if ($arResult['TOTALS']['FILE_SIZE'] == 0): ?>style="display:none"<? endif ?>>
				<? if ($arResult['TOTALS']['FILE_SIZE'] > 0): ?>
					<?= Loc::getMessage('CRM_VOLUME_TOTAL_FILES', array('#FILE_SIZE#' => $arResult['TOTALS']['FILE_SIZE_FORMAT'])); ?>
				<? endif ?>
			</div>

			<?/*
			<div id="bx-crm-volume-total-disk-count" class="crm-volume-header-amount-info" <? if ($arResult['TOTALS']['ALLOW_DROP'] == 0): ?>style="display:none"<? endif ?>>
				<? if ($arResult['TOTALS']['ALLOW_DROP'] > 0): ?>
					<?= Loc::getMessage("CRM_VOLUME_ALLOW_DROP", array('#FILE_SIZE#' => $arResult['TOTALS']['ALLOW_DROP_FORMAT'])) ?>
				<? endif ?>
			</div>
			*/
			?>
		</div>
	</div>


	<div id="bx-crm-volume-percent-diagram">
		<div class="crm-volume-percent-diagram">
			<?
			$legend = array();
			foreach ($arResult['PERCENT_DATA'] as $inx => $row)
			{
				$color = $component->pastelColors();
				$width = ($row['PERCENT'] > .1 ? $row['PERCENT'].'' : '0');
				$percent = ($row['PERCENT'] < .1 ? '&lt; 0.1%' : $row['PERCENT'].'%');
				$title = $row['TITLE']." ".$row['SIZE_FORMAT']." ({$percent})";
				$legend["$inx"] = array(
					'id' => $row['id'],
					'width' => $width,
					'title' => $title,
					'color' => $color,
					'name' => $row['TITLE'],
					'alt' => $row['SIZE_FORMAT']." ({$percent})",
				);
				?>
				<div data-indicator="<?= $row['id'] ?>" class="crm-volume-percent-part" style="flex:<?= $width ?>; background-color:<?= $color ?>;" title="<?= $title ?>"></div>
				<?
			}
			?>
		</div>

		<div class="crm-volume-percent-legend">
			<?
			$shown = 0;
			foreach ($legend as $i => $row)
			{
				$isShown = ($row['width'] >= 2 && $i < 8);
				if ($isShown) $shown ++;
				?>
				<div class="crm-volume-percent-legend-part" <?if(!$isShown):?>style="display:none"<?endif?>>
					<span class="crm-volume-percent-legend-part-circle" data-indicator="<?= $row['id'] ?>" style="background-color:<?= $row['color'] ?>;"></span>
					<span class="crm-volume-percent-legend-part-text" data-indicator="<?= $row['id'] ?>" title="<?= $row['alt'] ?>"><?= $row['name'] ?></span>
				</div>
				<?
			}
			if (count($legend) > $shown)
			{
				?>
				<div id="bx-crm-volume-show-full-legend" class="crm-volume-percent-legend-part">
					<span class="crm-volume-percent-legend-part-text">...</span>
				</div>
				<?
			}
			?>
		</div>

	</div>
	<?

	if ($arParams['IS_AJAX_REQUEST'])
	{
		?>
		<script type="text/javascript">
			<? if ($arResult['TOTALS']['TOTAL_SIZE'] > 0): ?>
			BX.Crm.measureManager.updateTotalSize({
				size: <?= $arResult['TOTALS']['TOTAL_SIZE'] ?>,
				format: '<?= Loc::getMessage("CRM_VOLUME_TOTAL_USEAGE", array('#FILE_SIZE#' => $arResult['TOTALS']['TOTAL_SIZE_FORMAT'])) ?>'
			});
			<? endif ?>

			<? if ($arResult['TOTALS']['FILE_SIZE'] > 0): ?>
			BX.Crm.measureManager.updateFileSize({
				size: <?= $arResult['TOTALS']['FILE_SIZE'] ?>,
				format: '<?= Loc::getMessage('CRM_VOLUME_TOTAL_FILES', array('#FILE_SIZE#' => $arResult['TOTALS']['FILE_SIZE_FORMAT'])); ?>'
			});
			<? endif ?>

		</script>
		<?
	}
	?>


	<div class="crm-volume-border"></div>
	<?

	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.grid',
		'',
		array(
			'GRID_ID' => $arResult['GRID_ID'],
			'HEADERS' => $arResult['HEADERS'],
			'SORT' => isset($arResult['SORT']) ? $arResult['SORT'] : array(),
			'SORT_VARS' => isset($arResult['SORT_VARS']) ? $arResult['SORT_VARS'] : array(),
			'ROWS' => isset($arResult['GRID_DATA']) ? $arResult['GRID_DATA'] : array(),

			'AJAX_MODE' => 'N',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_STYLE' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',

			'ALLOW_COLUMNS_SORT' => true,
			'ALLOW_ROWS_SORT' => false,
			'ALLOW_COLUMNS_RESIZE' => true,
			'ALLOW_HORIZONTAL_SCROLL' => true,
			'ALLOW_SORT' => true,
			'ALLOW_PIN_HEADER' => true,
			'SHOW_ACTION_PANEL' => false,
			'ACTION_PANEL' => false,

			'SHOW_CHECK_ALL_CHECKBOXES' => false,
			'SHOW_ROW_CHECKBOXES' => false,
			'SHOW_ROW_ACTIONS_MENU' => true,
			'SHOW_GRID_SETTINGS_MENU' => true,
			'SHOW_NAVIGATION_PANEL' => false,
			'SHOW_PAGINATION' => false,
			'SHOW_SELECTED_COUNTER' => false,
			'SHOW_TOTAL_COUNTER' => true,
			'SHOW_PAGESIZE' => true,

			'ENABLE_COLLAPSIBLE_ROWS' => false,

			'SHOW_MORE_BUTTON' => false,
			'NAV_OBJECT' => false,
			'TOTAL_ROWS_COUNT' => false,
			'CURRENT_PAGE' => false,
			'DEFAULT_PAGE_SIZE' => 20,
			'PAGE_SIZES' => array(),
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);

}

?>
	</div>
</div>
<?



if (!$arParams['IS_AJAX_REQUEST'])
{
	?>
	<script type="text/javascript">
		BX.message({
			CRM_VOLUME_DELETE: '<?= GetMessageJS("CRM_VOLUME_DELETE") ?>',
			CRM_VOLUME_CANCEL: '<?= GetMessageJS("CRM_VOLUME_CANCEL") ?>',
			CRM_VOLUME_CONFIRM: '<?= GetMessageJS("CRM_VOLUME_CONFIRM") ?>',
			CRM_VOLUME_CONFIRM_FILTER: '<?= GetMessageJS("CRM_VOLUME_CONFIRM_FILTER") ?>',
			CRM_VOLUME_SUCCESS: '<?= GetMessageJS("CRM_VOLUME_SUCCESS") ?>',
			CRM_VOLUME_ERROR: '<?= GetMessageJS("CRM_VOLUME_ERROR") ?>',
			CRM_VOLUME_CANCEL_WORKERS: '<?= GetMessageJS("CRM_VOLUME_CANCEL_WORKERS") ?>',
			CRM_VOLUME_CLOSE_WARNING: '<?= GetMessageJS("CRM_VOLUME_CLOSE_WARNING") ?>',
			CRM_VOLUME_PERFORMING_MEASURE_DATA: '<?= GetMessageJS("CRM_VOLUME_PERFORMING_MEASURE_DATA") ?>',
			CRM_VOLUME_PERFORMING_MEASURE_QUEUE: '<?= GetMessageJS("CRM_VOLUME_PERFORMING_QUEUE", array(
				'#QUEUE_STEP#' => 1,
				'#QUEUE_LENGTH#' => count($arResult["SCAN_ACTION_LIST"]),
			)) ?>',
			CRM_VOLUME_PERFORMING_CANCEL_MEASURE: '<?= GetMessageJS("CRM_VOLUME_PERFORMING_CANCEL_MEASURE") ?>',
			CRM_VOLUME_SETUP_CLEANER: '<?= GetMessageJS("CRM_VOLUME_SETUP_CLEANER") ?>',
			CRM_VOLUME_PERFORMING_CANCEL_WORKERS: '<?= GetMessageJS("CRM_VOLUME_PERFORMING_CANCEL_WORKERS") ?>',
			CRM_VOLUME_MEASURE_DATA_REPEAT: '<?= GetMessageJS("CRM_VOLUME_MEASURE_DATA_REPEAT") ?>',
			CRM_VOLUME_MEASURE_CONFIRM: '<?= GetMessageJS("CRM_VOLUME_MEASURE_CONFIRM") ?>',
			CRM_VOLUME_MEASURE_CONFIRM_QUESTION: '<?= GetMessageJS("CRM_VOLUME_MEASURE_CONFIRM_QUESTION") ?>',
			CRM_VOLUME_MEASURE_ACCEPT: '<?= GetMessageJS("CRM_VOLUME_MEASURE_ACCEPT") ?>'
		});

		BX(function () {

			BX.Crm.measureManager = new BX.Crm.MeasureClass({
				ajaxUrl: '<?= \CUtil::JSEscape($arParams['AJAX_PATH']) ?>',
				relUrl: '<?= \CUtil::JSEscape($arParams['RELATIVE_PATH']) ?>',
				filterId: '<?= $arResult['FILTER_ID'] ?>',
				gridId: '<?= $arResult['GRID_ID'] ?>',
				sefMode: '<?=($arParams['SEF_MODE'] === 'Y' ? 'Y' : 'N')?>',
				hasWorkerInProcess: <?= (isset($arResult["HAS_WORKER_IN_PROCESS"]) && $arResult["HAS_WORKER_IN_PROCESS"] ? 'true' : 'false') ?>,
				suppressStepperAlert: <?= (isset($arResult["WORKER_USES_CRONTAB"]) && $arResult["WORKER_USES_CRONTAB"] ? 'true' : 'false') ?>
			});

			<? foreach ($arResult['SCAN_ACTION_LIST'] as $item): ?>
			BX.Crm.measureManager.addQueueItem(<?= \Bitrix\Main\Web\Json::encode((object)$item) ?>);
			<? endforeach; ?>

			<? if ($arResult['QUEUE_RUNNING']): ?>
			<? if ($arResult['RUN_QUEUE'] === 'full'): ?>
			BX.ready( function() {
				BX.Crm.measureManager.progressBarShow(0);
				BX.Crm.measureManager.runQueue(1);
			});
			<? elseif ($arResult['RUN_QUEUE'] === 'continue'): ?>
			BX.ready( function() {
				var percent = Math.round(<?= $arResult['QUEUE_STEP']['queueStep'] ?> * 100 / <?= $arResult['QUEUE_STEP']['queueLength'] ?>);
				BX.Crm.measureManager.progressBarShow(percent);
				BX.Crm.measureManager.runQueue(<?= $arResult['QUEUE_STEP']['queueStep'] ?>, <?= \Bitrix\Main\Web\Json::encode($arResult['QUEUE_STEP']) ?>);
			});
			<? endif; ?>
			<? endif; ?>



			var startMeasureButton = BX('bx-crm-volume-link-measure');

			BX.bind(startMeasureButton, 'click', BX.proxy(BX.Crm.measureManager.repeatMeasure, BX.Crm.measureManager));

			BX.bind(BX('bx-crm-volume-process-cancel'), 'click', function(e)
			{
				BX.Crm.measureManager.stopQueue();

				BX.removeClass(startMeasureButton, 'ui-btn-wait');

				BX.Crm.measureManager.modalWindow({
					content: BX.message('CRM_VOLUME_PERFORMING_CANCEL_MEASURE'),
					overlay: true,
					showLoaderIcon: true,
					autoHide: false
				});

				BX.Crm.measureManager.callAction({
					action: '<?= $component::ACTION_PURIFY ?>',
					queueStop: 'Y',
					doNotShowModalAlert: true,
					doNotFollowRedirect: true,
					after: function(){
						BX.Crm.measureManager.progressBarHide();

						BX.removeClass(startMeasureButton, 'ui-btn-disabled');
						startMeasureButton.disabled = false;

						var w = BX.PopupWindowManager.getCurrentPopup();
						if (w) {
							w.close();
							w.destroy();
						}
					}
				});

				return BX.PreventDefault(e);
			});

			BX.bind(BX('bx-crm-volume-show-full-legend'), 'click', function(e)
			{
				var percentLegendPart = BX.findChildrenByClassName(BX('bx-crm-volume-percent-diagram'), 'crm-volume-percent-legend-part');
				for (var i in percentLegendPart)
				{
					BX.adjust(percentLegendPart[i], {style: {display: 'inline-block'}});
				}
				BX.hide(BX('bx-crm-volume-show-full-legend'));

				return BX.PreventDefault(e);
			});


			BX.addCustomEvent(window, "OnCrmVolumeStepperFinished", function(){
				var blockInProcess = BX.findChildrenByClassName(BX('bx-crm-volume-main-block'), 'crm-volume-inprocess');
				for (var i in blockInProcess)
				{
					BX.removeClass(blockInProcess[i], 'crm-volume-inprocess');
				}
			});

		});
	</script>
	<?


}

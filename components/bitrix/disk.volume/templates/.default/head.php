<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
/** @var \CDiskVolumeComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

CJSCore::Init(array('disk', 'ui.viewer', 'disk.viewer.document-item', 'ui.fonts.opensans'));

Loc::loadMessages(__FILE__);

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

$isQueueRunning = isset($arResult['RUN_QUEUE']) && ($arResult['RUN_QUEUE'] === 'full' || $arResult['RUN_QUEUE'] === 'continue');

$isTrashcan = ($arResult['INDICATOR'] === \Bitrix\Disk\Volume\FileDeleted::getIndicatorId());



if ($isBitrix24Template)
{
	$this->SetViewTarget("inside_pagetitle", 10);

	?>
	<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
		<?

		// Filter
		if (in_array(
			$arResult['ACTION'],
			array(
				$component::ACTION_DISKS,
				$component::ACTION_STORAGE,
				$component::ACTION_FILES,
				$component::ACTION_FOLDER,
			))
		)
		{
			$APPLICATION->IncludeComponent(
				'bitrix:main.ui.filter',
				'',
				array(
					'GRID_ID' => $arResult['GRID_ID'],
					'FILTER_ID' => $arResult['FILTER_ID'],
					'FILTER' => $arResult["FILTER"],
					'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
					'ENABLE_LIVE_SEARCH' => true,
					'ENABLE_LABEL' => true,
					'RESET_TO_DEFAULT_MODE' => false,
				),
				$component
			);
		}

		// Menu
		?>
	</div>

	<div id="bx-disk-volume-menu" class="pagetitle-container pagetitle-align-right-container">
		<? if ($arResult["ADMIN_MODE_ALLOW"] || $arResult["WORKER_COUNT"] > 0)
		{
			?>
			<div id="bx-disk-volume-popupMenuOptions" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting task-list-toolbar-lightning"></div>
			<?
		}

		if (!$arResult['DISK_EMPTY'] && !$isQueueRunning && $arResult['DATA_COLLECTED'])
		{
			?>
			<a href="<?= $component->getActionUrl(array('reload' => 'Y', 'action' => $component::ACTION_DEFAULT)); ?>" class="ui-btn ui-btn-primary disk-volume-reload-link"><?= Loc::getMessage('DISK_VOLUME_MEASURE_DATA_REPEAT') ?></a>
			<?
		}

		if ($arResult["ADMIN_MODE_ALLOW"] || $arResult["WORKER_COUNT"] > 0)
		{
			?>
			<script type="text/javascript">
				BX.ready(function()
				{
					var menuItemsOptions = [];

					<? if ($arResult["WORKER_COUNT"] > 0): ?>
					menuItemsOptions.push({
						text: '<?=GetMessageJS('DISK_VOLUME_CANCEL_WORKERS')?>',
						onclick : function()
						{
							BX.PopupMenu.getMenuById("popupMenuOptions").close();
							BX.Disk.showActionModal({text: BX.message('DISK_VOLUME_PERFORMING_CANCEL_WORKERS'), showLoaderIcon:true, autoHide:false});
							BX.Disk.measureManager.callAction({action: '<?= $component::ACTION_CANCEL_WORKERS ?>', after: BX.Disk.measureManager.stepperHide, doNotShowModalAlert: true});
						}
					});
					<? endif; ?>

					<? if ($arResult["ADMIN_MODE_ALLOW"]): ?>
					<? if (!$arResult["ADMIN_MODE"]): ?>
					menuItemsOptions.push({
						text: '<?=GetMessageJS('DISK_VOLUME_ADMIN_MODE')?>',
						href: '<?= $component->getActionUrl(array('admin' => 'on', 'action' => $component::ACTION_DEFAULT)); ?>'
					});
					<? else: ?>
					menuItemsOptions.push({
						text: '<?=GetMessageJS('DISK_VOLUME_ADMIN_MODE_EXIT')?>',
						href: '<?= $component->getActionUrl(array('admin' => 'off', 'expert' => 'off', 'action' => $component::ACTION_DEFAULT, 'storageId' => '')); ?>'
					});
					<? endif; ?>
					<? endif; ?>

					var menu = BX.PopupMenu.create(
						"popupMenuOptions",
						BX("bx-disk-volume-popupMenuOptions"),
						menuItemsOptions,
						{
							closeByEsc: true,
							offsetLeft: 20,
							angle: true
						}
					);

					BX.bind(BX("bx-disk-volume-popupMenuOptions"), "click", BX.delegate(function () {
						menu.popupWindow.show();
					}, this));
				});
			</script>
			<?
		}
	?>
	</div>
	<?
	$this->EndViewTarget();
}



if ($isBitrix24Template && !$arResult['DISK_EMPTY'] && !$isQueueRunning && $arResult['DATA_COLLECTED'])
{
	$this->SetViewTarget("below_pagetitle");

	$menuItems = array();

	if ($arResult['EXPERT_MODE'] === true)
	{
		$menuItems[] = array(
			//'TEXT' => Loc::getMessage('DISK_VOLUME_EXPERT_MODE_OFF'),
			'TEXT' => Loc::getMessage('DISK_VOLUME_EXPERT_MODE_EXIT'),
			'URL' => $component->getActionUrl(array('expert' => 'off', 'action' => $component::ACTION_DEFAULT, 'storageId' => '')),
			'ID' => 'expertOff',
			'IS_ACTIVE' => (bool)($arResult['EXPERT_MODE'] === false),
			'CLASS' => '',
		);
	}
	else
	{
		$menuItems[] = array(
			//'TEXT' => Loc::getMessage('DISK_VOLUME_EXPERT_MODE'),
			'TEXT' => Loc::getMessage('DISK_VOLUME_EXPERT_MODE_ON'),
			'URL' => $component->getActionUrl(array(
				'expert' => 'on',
				'action' => ($arResult['ADMIN_MODE'] === true ? $component::ACTION_DISKS : $component::ACTION_STORAGE),
				'storageId' => '',
			)),
			'ID' => 'expertOn',
			'IS_ACTIVE' => (bool)($arResult['EXPERT_MODE'] === true),
			'CLASS' => '',
		);
	}

	?>
	<div class="tasks-view-switcher pagetitle-align-right-container">
		<div class="tasks-view-switcher-list">
			<? foreach ($menuItems as $item):
				if ($item['IS_ACTIVE'])
				{
					$item['CLASS'] .= ' tasks-view-switcher-list-item-active';
				}
				?>
				<a href="<?= $item['URL'] ?>" id="<?= $item['ID'] ?>" title="<?= $item['COMMENT'] ?>" class="tasks-view-switcher-list-item <?= $item['CLASS'] ?>"><?= $item['TEXT'] ?></a>
			<? endforeach; ?>
		</div>
	</div>
	<?


	$this->EndViewTarget();
}


?>
<script type="text/javascript">
	BX.message({
		DISK_VOLUME_PERFORMING_MEASURE_DATA: '<?= GetMessageJS("DISK_VOLUME_PERFORMING_MEASURE_DATA") ?>',
		DISK_VOLUME_PERFORMING_MEASURE_QUEUE: '<?= GetMessageJS("DISK_VOLUME_PERFORMING_QUEUE", array(
			'#QUEUE_STEP#' => 1,
			'#QUEUE_LENGTH#' => count($arResult["FULL_SCAN_INDICATOR_LIST"]),
		)) ?>',
		DISK_VOLUME_PERFORMING_CANCEL_MEASURE: '<?= GetMessageJS("DISK_VOLUME_PERFORMING_CANCEL_MEASURE") ?>',
		DISK_VOLUME_CANCEL_BUTTON: '<?= GetMessageJS("DISK_VOLUME_CANCEL_BUTTON") ?>',
		DISK_VOLUME_DELETE_CONFIRM_TITLE: '<?= GetMessageJS("DISK_VOLUME_DELETE_CONFIRM_TITLE") ?>',
		DISK_VOLUME_DELETE_FILE_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_FILE_CONFIRM") ?>',
		DISK_VOLUME_DELETE_FILE_UNNECESSARY_VERSION_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_FILE_UNNECESSARY_VERSION_CONFIRM") ?>',
		DISK_VOLUME_DELETE_FOLDER_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_FOLDER_CONFIRM") ?>',
		DISK_VOLUME_EMPTY_FOLDER_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_EMPTY_FOLDER_CONFIRM") ?>',
		DISK_VOLUME_EMPTY_ROOT_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_EMPTY_ROOT_CONFIRM") ?>',
		DISK_VOLUME_DELETE_FOLDER_UNNECESSARY_VERSION_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_FOLDER_UNNECESSARY_VERSION_CONFIRM") ?>',
		DISK_VOLUME_DELETE_ROOT_UNNECESSARY_VERSION_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_ROOT_UNNECESSARY_VERSION_CONFIRM") ?>',
		DISK_VOLUME_DELETE_STORAGE_SAFE_CLEAR_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_STORAGE_SAFE_CLEAR_CONFIRM") ?>',
		DISK_VOLUME_DELETE_UPLOADED_SAFE_CLEAR_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_UPLOADED_SAFE_CLEAR_CONFIRM") ?>',
		DISK_VOLUME_DELETE_TRASHCAN_SAFE_CLEAR_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_DELETE_TRASHCAN_SAFE_CLEAR_CONFIRM") ?>',
		DISK_VOLUME_DELETE_BUTTON: '<?= GetMessageJS("DISK_VOLUME_DELETE_BUTTON") ?>',
		DISK_VOLUME_GROUP_DISK_SAFE_CLEAR_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_DISK_SAFE_CLEAR_CONFIRM") ?>',
		DISK_VOLUME_GROUP_UPLOADED_SAFE_CLEAR_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_UPLOADED_SAFE_CLEAR_CONFIRM") ?>',
		DISK_VOLUME_GROUP_TRASHCAN_SAFE_CLEAR_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_TRASHCAN_SAFE_CLEAR_CONFIRM") ?>',
		DISK_VOLUME_GROUP_FOLDER_SAFE_CLEAR_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_FOLDER_SAFE_CLEAR_CONFIRM") ?>',
		DISK_VOLUME_GROUP_FOLDER_EMPTY_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_FOLDER_EMPTY_CONFIRM") ?>',
		DISK_VOLUME_GROUP_FOLDER_DROP_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_FOLDER_DROP_CONFIRM") ?>',
		DISK_VOLUME_GROUP_DELETE_FILE_UNNECESSARY_VERSION_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_DELETE_FILE_UNNECESSARY_VERSION_CONFIRM") ?>',
		DISK_VOLUME_GROUP_DELETE_FILE_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_GROUP_DELETE_FILE_CONFIRM") ?>',
		DISK_VOLUME_PERFORMING_CANCEL_WORKERS: '<?= GetMessageJS("DISK_VOLUME_PERFORMING_CANCEL_WORKERS") ?>',
		DISK_VOLUME_SETUP_CLEANER: '<?= GetMessageJS("DISK_VOLUME_SETUP_CLEANER") ?>',
		DISK_VOLUME_UNNECESSARY_VERSION_HINT: '<?= GetMessageJS("DISK_VOLUME_UNNECESSARY_VERSION_HINT") ?>',
		DISK_VOLUME_DROPPED_TRASHCAN_HINT: '<?= GetMessageJS("DISK_VOLUME_DROPPED_TRASHCAN_HINT") ?>',
		DISK_VOLUME_STEPPER_STEPS_HINT: '<?= GetMessageJS("DISK_VOLUME_STEPPER_STEPS_HINT") ?>',
		<?/*
		// hints
		DISK_VOLUME_UNNECESSARY_VERSION_SIZE_HINT: '<?= GetMessageJS("DISK_VOLUME_UNNECESSARY_VERSION_SIZE_HINT") ?>',
		DISK_VOLUME_UNNECESSARY_VERSION_COUNT_HINT: '<?= GetMessageJS("DISK_VOLUME_UNNECESSARY_VERSION_COUNT_HINT") ?>',
		DISK_VOLUME_TRASHCAN_SIZE_HINT: '<?= GetMessageJS("DISK_VOLUME_TRASHCAN_SIZE_HINT") ?>',
		DISK_VOLUME_FILE_COUNT_HINT: '<?= GetMessageJS("DISK_VOLUME_FILE_COUNT_HINT") ?>',
		DISK_VOLUME_FILE_SIZE_HINT: '<?= GetMessageJS("DISK_VOLUME_FILE_SIZE_HINT") ?>',
		DISK_VOLUME_PERCENT_HINT: '<?= GetMessageJS("DISK_VOLUME_PERCENT_HINT") ?>',
		DISK_VOLUME_UPDATE_TIME_HINT: '<?= GetMessageJS("DISK_VOLUME_UPDATE_TIME_HINT") ?>',
		DISK_VOLUME_VERSION_COUNT_HINT: '<?= GetMessageJS("DISK_VOLUME_VERSION_COUNT_HINT") ?>',
		DISK_VOLUME_VERSION_SIZE_HINT: '<?= GetMessageJS("DISK_VOLUME_VERSION_SIZE_HINT") ?>',
		DISK_VOLUME_USING_COUNT_HINT: '<?= GetMessageJS("DISK_VOLUME_USING_COUNT_HINT") ?>',
		DISK_VOLUME_TOTAL_USAGE_HINT: '<?= GetMessageJS("DISK_VOLUME_TOTAL_USAGE_HINT") ?>',
		DISK_VOLUME_TOTAL_COUNT_HINT: '<?= GetMessageJS("DISK_VOLUME_TOTAL_COUNT_HINT") ?>',
		DISK_VOLUME_TOTAL_UNNECESSARY_VERSION_HINT: '<?= GetMessageJS("DISK_VOLUME_TOTAL_UNNECESSARY_VERSION_HINT") ?>',
		DISK_VOLUME_TOTAL_TRASHCAN_HINT: '<?= GetMessageJS("DISK_VOLUME_TOTAL_TRASHCAN_HINT") ?>',
		DISK_VOLUME_DISK_TOTAL_USAGE_HINT: '<?= GetMessageJS("DISK_VOLUME_DISK_TOTAL_USAGE_HINT") ?>',
		DISK_VOLUME_DISK_TOTAL_COUNT_HINT: '<?= GetMessageJS("DISK_VOLUME_DISK_TOTAL_COUNT_HINT") ?>',
		DISK_VOLUME_DISK_TOTAL_UNNECESSARY_VERSION_HINT: '<?= GetMessageJS("DISK_VOLUME_DISK_TOTAL_UNNECESSARY_VERSION_HINT") ?>',
		DISK_VOLUME_DISK_TOTAL_TRASHCAN_HINT: '<?= GetMessageJS("DISK_VOLUME_DISK_TOTAL_TRASHCAN_HINT") ?>',
		DISK_VOLUME_SAFE_CLEAR_HINT: '<?= GetMessageJS("DISK_VOLUME_SAFE_CLEAR_HINT") ?>',
		DISK_VOLUME_SIZE_FILE_HINT: '<?= GetMessageJS("DISK_VOLUME_SIZE_FILE_HINT") ?>',
		*/?>
		DISK_VOLUME_DISK_TOTAL_USEAGE: '<?= GetMessageJS("DISK_VOLUME_DISK_TOTAL_USEAGE") ?>',
		DISK_VOLUME_VERSION_FILES: '<?= GetMessageJS("DISK_VOLUME_VERSION_FILES") ?>',
		DISK_VOLUME_TRASHCAN: '<?= GetMessageJS("DISK_VOLUME_TRASHCAN") ?>',
		DISK_VOLUME_DISK_TOTAL_COUNT: '<?= GetMessageJS("DISK_VOLUME_DISK_TOTAL_COUNT") ?>',
		DISK_VOLUME_MEASURE: '<?= GetMessageJS("DISK_VOLUME_MEASURE") ?>',
		DISK_VOLUME_MEASURE_CONFIRM: '<?= GetMessageJS("DISK_VOLUME_MEASURE_CONFIRM") ?>',
		DISK_VOLUME_MEASURE_ACCEPT: '<?= GetMessageJS("DISK_VOLUME_MEASURE_ACCEPT") ?>',
		DISK_VOLUME_MEASURE_CONFIRM_QUESTION: '<?= GetMessageJS("DISK_VOLUME_MEASURE_CONFIRM_QUESTION") ?>',
		DISK_VOLUME_CANCEL_WORKERS: '<?= GetMessageJS("DISK_VOLUME_CANCEL_WORKERS") ?>',
		DISK_VOLUME_CLOSE_WARNING: '<?= GetMessageJS("DISK_VOLUME_CLOSE_WARNING") ?>',
		DISK_VOLUME_COUNT: '<?= GetMessageJS("DISK_VOLUME_COUNT") ?>'
	});


	BX(function () {

		BX.Disk.measureManager = new BX.Disk.MeasureClass({
			ajaxUrl: '<?= \CUtil::JSEscape($arParams['AJAX_PATH']) ?>',
			relUrl: '<?= \CUtil::JSEscape($arParams['RELATIVE_PATH']) ?>',
			componentParams: '<?= \Bitrix\Main\Component\ParameterSigner::signParameters($component->getName(), array(
				'relUrl' => $arParams['RELATIVE_PATH'],
				'restrictStorageId' => ($arParams['STORAGE_ID'] > 0 ? $arParams['STORAGE_ID'] : 0),
				'sefMode' => ($arParams['SEF_MODE'] === 'Y' ? 'Y' : 'N'),
			)) ?>',
			filterId: '<?= $arResult['FILTER_ID'] ?>',
			gridId: '<?= $arResult['GRID_ID'] ?>',
			storageId: <?=($arResult['STORAGE_ID'] > 0 ? $arResult['STORAGE_ID'] : 0)?>,
			hasWorkerInProcess: <?= ($arResult["HAS_WORKER_IN_PROCESS"] ? 'true' : 'false') ?>,
			suppressStepperAlert: <?= ($arResult["WORKER_USES_CRONTAB"] ? 'true' : 'false') ?>
		});

		<? foreach ($arResult['SCAN_ACTION_LIST'] as $item): ?>
			BX.Disk.measureManager.addQueueItem(<?= \Bitrix\Main\Web\Json::encode((object)$item) ?>);
		<? endforeach; ?>

		<? if ($isQueueRunning): ?>
			<? if ($arResult['RUN_QUEUE'] === 'full'): ?>
				BX.ready( function() {
					BX.Disk.measureManager.progressBarShow(0);
					BX.Disk.measureManager.runQueue(1);
				});
			<? elseif ($arResult['RUN_QUEUE'] === 'continue'): ?>
				BX.ready( function() {
					var percent = Math.round(<?= $arResult['QUEUE_STEP']['queueStep'] ?> * 100 / <?= $arResult['QUEUE_STEP']['queueLength'] ?>);
					BX.Disk.measureManager.progressBarShow(percent);
					BX.Disk.measureManager.runQueue(<?= $arResult['QUEUE_STEP']['queueStep'] ?>, <?= \Bitrix\Main\Web\Json::encode($arResult['QUEUE_STEP']) ?>);
				});
			<? endif; ?>
		<? endif; ?>

		// metric
		BX.Disk.measureManager.addMetricMark({
			GLOBAL_SCAN: '<?= $component::METRIC_MARK_GLOBAL_SCAN ?>',
			GLOBAL_UNNECESSARY_CLEAN: '<?= $component::METRIC_MARK_GLOBAL_UNNECESSARY_CLEAN ?>',
			GLOBAL_TRASHCAN_CLEAN: '<?= $component::METRIC_MARK_GLOBAL_TRASHCAN_CLEAN ?>',
			CERTAIN_DISK_CLEAN: '<?= $component::METRIC_MARK_CERTAIN_DISK_CLEAN ?>',
			CERTAIN_FOLDER_CLEAN: '<?= $component::METRIC_MARK_CERTAIN_FOLDER_CLEAN ?>'
		});

		// hints
		BX.Disk.helperHint = new BX.Disk.HintClass({});
		BX.ready(BX.proxy(BX.Disk.helperHint.initHints, BX.Disk.helperHint));
	});

</script>


<div id="bx-disk-volume-stepper" class="disk-volume-stepper disk-volume-stepper-<?=$arResult['ACTION']?>" <? if ($isQueueRunning || !$arResult["HAS_WORKER_IN_PROCESS"]): ?>style="display:none"<? endif; ?>>
	<?
	if (!$isQueueRunning)
	{
		echo $component->getWorkerProgressBar();
	}
	?>
	<div id="bx-disk-volume-stepper-alert" class="disk-volume-stepper-alert">
		<span class="disk-volume-stepper-alert-icon"></span>
		<span class="disk-volume-stepper-alert-text"><?= Loc::getMessage('DISK_VOLUME_CLOSE_WARNING'); ?></span>
	</div>
</div>
<?


if (!$arResult['DISK_EMPTY'])
{
	?>
	<div id="bx-disk-volume-reload-warning" class="disk-volume-info-control-panel" <? if ($arResult["NEED_RELOAD"] !== true || $isQueueRunning): ?>style="display: none" <? endif; ?> >
		<div
			class="disk-volume-info-control-panel-warning"><?= Loc::getMessage('DISK_VOLUME_NEED_RELOAD_COMMENT'); ?></div>
		<a href="<?= $component->getActionUrl(array('action' => $component::ACTION_DEFAULT, 'reload' => 'Y')); ?>" class="disk-volume-info-control-panel-link disk-volume-reload-link"><?= Loc::getMessage('DISK_VOLUME_MEASURE_DATA_REPEAT'); ?></a>
	</div>
	<?
}


if (!empty($arResult['ERROR_MESSAGE']))
{
	?>
	<div class="bx-disk-volume-errors">
		<div class="bx-disk-volume-error-text">
			<?= $arResult['ERROR_MESSAGE'] ?>
		</div>
	</div>
	<?
	return;
}




if (
	$component::ACTION_DISKS === $arResult['ACTION'] ||
	$component::ACTION_STORAGE === $arResult['ACTION'] ||
	$component::ACTION_FILES === $arResult['ACTION']
)
{

	?>
	<div class="disk-volume-wrap">
		<?

		if ($component::ACTION_DISKS === $arResult['ACTION'] && $arResult["ADMIN_MODE"])
		{
			?>
			<div class="disk-volume-header-container">
				<div class="disk-volume-header-icon disk-volume-header-icon-default-disk"></div>
				<div class="disk-volume-header-block">
					<div class="disk-volume-header-title"><?= Loc::getMessage("DISK_VOLUME_DISK_B24") ?></div>
					<div id="bx-disk-volume-total-disk-size" class="disk-volume-header-amount-info">
						<? if ($arResult['TOTAL_FILE_SIZE'] > 0): ?>
							<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_USEAGE", array('#FILE_SIZE#' => $arResult['TOTAL_FILE_SIZE_FORMAT'])) ?>
						<? endif ?>
					</div>

					<div id="bx-disk-volume-total-unnecessary" class="disk-volume-header-amount-info disk-volume-hint" data-hint="unnecessary_version" <? if ($arResult['Storage']['UNNECESSARY_VERSION_SIZE_FORMAT'] == 0): ?>style="display:none"<? endif ?>>
						<? if ($arResult['Storage']['UNNECESSARY_VERSION_SIZE'] > 0): ?>
							<?= Loc::getMessage('DISK_VOLUME_VERSION_FILES', array('#FILE_SIZE#' => $arResult['Storage']['UNNECESSARY_VERSION_SIZE_FORMAT'])); ?>
						<? endif ?>
					</div>

					<div id="bx-disk-volume-total-trashcan" class="disk-volume-header-amount-info disk-volume-hint" data-hint="dropped_trashcan" <? if ($arResult['TrashCan']['FILE_SIZE'] == 0): ?>style="display:none"<? endif ?>>
						<? if ($arResult['TrashCan']['FILE_SIZE'] > 0): ?>
							<?= Loc::getMessage('DISK_VOLUME_TRASHCAN', array('#FILE_SIZE#' => $arResult['TrashCan']['FILE_SIZE_FORMAT'])); ?>
						<? endif ?>
					</div>

					<div id="bx-disk-volume-total-disk-count" class="disk-volume-header-amount-info">
						<? if ($arResult['TOTAL_FILE_COUNT'] > 0): ?>
							<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_COUNT", array('#FILE_COUNT#' => $arResult['TOTAL_FILE_COUNT'])) ?>
						<? endif ?>
					</div>
				</div>
			</div>
			<?
		}
		else
		{
			?>
			<div class="disk-volume-header-container <?=($arResult['Storage']['IS_EXTRANET'] ? 'disk-volume-header-container-extranet' : '')?>">
				<? if ($arResult["ADMIN_MODE"]): ?>
					<? if ($arResult['Storage']['STYLE'] === 'User'): ?>
						<div class="disk-volume-header-icon disk-volume-header-icon-default-user" <?
						if (isset($arResult['Storage']['PICTURE'])): ?>style="background-image: url('<?= Uri::urnEncode($arResult['Storage']['PICTURE']) ?>')"<? endif; ?>></div>
					<? elseif ($arResult['Storage']['STYLE'] === 'Group'): ?>
						<div class="disk-volume-header-icon disk-volume-header-icon-default-group" <?
						if (isset($arResult['Storage']['PICTURE'])): ?>style="background-image: url('<?= Uri::urnEncode($arResult['Storage']['PICTURE']) ?>')"<? endif; ?>></div>
					<? else: ?>
						<div class="disk-volume-header-icon disk-volume-header-icon-default-disk"></div>
					<? endif; ?>
				<? endif; ?>

				<div class="disk-volume-header-block">
					<? if ($isTrashcan):?>
						<div class="disk-volume-header-title"><?= Loc::getMessage("DISK_VOLUME_DISK_TRASHCAN") ?>
							<? if ($arResult["ADMIN_MODE"]): ?>&quot;<?= $arResult['Storage']['TITLE']; ?>&quot; <? endif ?>
						</div>

						<div id="bx-disk-volume-total-trashcan" class="disk-volume-header-amount-info disk-volume-hint" data-hint="dropped_trashcan" <? if ($arResult['TrashCan']['FILE_SIZE'] == 0): ?>style="display:none"<? endif ?>>
							<? if ($arResult['TrashCan']['FILE_SIZE'] > 0): ?>
								<?= Loc::getMessage('DISK_VOLUME_SIZE', array('#FILE_SIZE#' => $arResult['TrashCan']['FILE_SIZE_FORMAT'])); ?>
							<? endif ?>
						</div>

						<div id="bx-disk-volume-total-disk-count" class="disk-volume-header-amount-info">
							<?= Loc::getMessage("DISK_VOLUME_COUNT", array('#FILE_COUNT#' => $arResult['TrashCan']['FILE_COUNT'])) ?>
						</div>

					<? else: ?>
						<div class="disk-volume-header-title">
							<? if ($arResult["ADMIN_MODE"]): ?><?= Loc::getMessage("DISK_VOLUME_DISK") ?><? endif ?>
							<?= $arResult['Storage']['TITLE']; ?>
						</div>

						<div id="bx-disk-volume-total-disk-size" class="disk-volume-header-amount-info">
							<? if ($arResult['TOTAL_FILE_SIZE'] > 0): ?>
								<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_USEAGE", array('#FILE_SIZE#' => $arResult['TOTAL_FILE_SIZE_FORMAT'])) ?>
							<? endif ?>
						</div>

						<div id="bx-disk-volume-total-unnecessary" class="disk-volume-header-amount-info disk-volume-hint" data-hint="unnecessary_version" <? if ($arResult['Storage']['UNNECESSARY_VERSION_SIZE'] == 0): ?>style="display:none"<? endif ?>>
							<? if ($arResult['Storage']['UNNECESSARY_VERSION_SIZE'] > 0): ?>
								<?= Loc::getMessage('DISK_VOLUME_VERSION_FILES', array('#FILE_SIZE#' => $arResult['Storage']['UNNECESSARY_VERSION_SIZE_FORMAT'])); ?>
							<? endif ?>
						</div>

						<div id="bx-disk-volume-total-trashcan" class="disk-volume-header-amount-info disk-volume-hint" data-hint="dropped_trashcan" <? if ($arResult['TrashCan']['FILE_SIZE'] == 0): ?>style="display:none"<? endif ?>>
							<? if ($arResult['TrashCan']['FILE_SIZE'] > 0): ?>
								<a href="<?= $component->getActionUrl(array('action' => $component::ACTION_TRASH_FILES, 'storageId' => $arResult['STORAGE_ID'])); ?>">
									<?= Loc::getMessage('DISK_VOLUME_TRASHCAN', array('#FILE_SIZE#' => $arResult['TrashCan']['FILE_SIZE_FORMAT'])); ?>
								</a>
							<? endif ?>
						</div>

						<div id="bx-disk-volume-total-disk-count" class="disk-volume-header-amount-info">
							<? if ($arResult['TOTAL_FILE_COUNT'] > 0): ?>
								<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_COUNT", array('#FILE_COUNT#' => $arResult['TOTAL_FILE_COUNT'])) ?>
							<? endif ?>
						</div>
					<? endif ?>
				</div>
			</div>

			<? if(count($arResult['ACTION_MENU']) > 0):?>
				<span id="bx-disk-volume-popupMenuStorage" class="ui-btn ui-btn-light-border ui-btn-icon-list"></span>
				<script type="text/javascript">
					BX.ready(function()
					{
						var menuItemsOptions = [];

						<?foreach($arResult['ACTION_MENU'] as $menuItem):?>
						menuItemsOptions.push({
							text: '<?= $menuItem['text'] ?>',
							onclick : function(e){
								BX.eventCancelBubble(e);
								BX.fireEvent(document, 'click');
								<?= $menuItem['onclick'] ?>
							}
						});
						<? endforeach; ?>

						var menu = BX.PopupMenu.create(
							"popupMenuStorage",
							BX("bx-disk-volume-popupMenuStorage"),
							menuItemsOptions,
							{
								closeByEsc: true,
								offsetLeft: -120
							}
						);

						BX.bind(BX("bx-disk-volume-popupMenuStorage"), "click", BX.delegate(function () {
							menu.popupWindow.show();
						}, this));

					});
				</script>
			<? endif; ?>
			<?
		}

		if (!$isTrashcan)
		{
			$plotIndicatorId = \Bitrix\Disk\Volume\FileType::getIndicatorId();

			if (count($arResult[$plotIndicatorId]['LIST']) > 0)
			{
				?>
				<div class="bx-disk-interface-percent-diagram">
					<?
					$pp = 0;
					$tt = 0;
					$legend = array();
					foreach ($arResult[$plotIndicatorId]['LIST'] as $inx => $row)
					{
						$pp += $row['PERCENT'];
						$tt += $row['FILE_SIZE'];
						$color = $component->pastelColors();
						$width = ($row['PERCENT'] > .1 ? $row['PERCENT'].'' : '0');
						$percent = ($row['PERCENT'] < .1 ? '&lt; 0.1%' : $row['PERCENT'].'%');
						$title = $row['TITLE']." ".$row['FILE_SIZE_FORMAT']." ({$percent})";
						$alt = $row['FILE_SIZE_FORMAT']." ({$percent})";
						$legend["$inx"] = array(
							'color' => $color,
							'title' => $row['TITLE'],
							'alt' => $alt,
						);
						?>
						<div class="bx-disk-interface-percent-part" style="flex:<?= $width ?>; background-color:<?= $color ?>;" title="<?= $title ?>"></div>
						<?
					}
					if (isset($arResult[$plotIndicatorId]['OTHER']))
					{
						$row = $arResult[$plotIndicatorId]['OTHER'];
						$pp += $row['PERCENT'];
						$tt += $row['FILE_SIZE'];
						$color = $component->pastelColors(true);
						$width = ($row['PERCENT'] > .1 ? $row['PERCENT'].'' : '0');
						$percent = ($row['PERCENT'] < .1 ? '&lt; 0.1%' : $row['PERCENT'].'%');
						$title = $row['TITLE']." ".$row['FILE_SIZE_FORMAT']." ({$percent})";
						$alt = $row['FILE_SIZE_FORMAT']." ({$percent})";
						$legend['other'] = array(
							'color' => $color,
							'title' => $row['TITLE'],
							'alt' => $alt,
						);
						?>
						<div class="bx-disk-interface-percent-part" style="flex:<?= $width ?>; background-color:<?= $color ?>;" title="<?= $title ?>"></div>
						<?
					}
					?>
				</div>
				<!--<?
					echo "\n".$pp;
					echo "\n".$tt;
					echo "\n".\Cfile::FormatSize($tt);
				?>-->
				<div class="bx-disk-interface-percent-legend">
					<div class="bx-disk-interface-percent-legend-title"><?= Loc::getMessage('DISK_VOLUME_DISK_USAGE'); ?></div>
					<?
					foreach ($legend as $row)
					{
						?>
						<div class="bx-disk-interface-percent-legend-part">
							<span class="bx-disk-interface-percent-legend-part-circle" style="background-color:<?= $row['color'] ?>;"></span>
							<span class="bx-disk-interface-percent-legend-part-text" title="<?= $row['alt'] ?>"><?= $row['title'] ?></span>
						</div>
						<?
					}
					?>
				</div>
				<?
			}
		}
		?>
	</div>
	<?
}



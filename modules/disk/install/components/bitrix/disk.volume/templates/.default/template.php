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
\Bitrix\Main\UI\Extension::load(["ui.buttons", "ui.fonts.opensans"]);

Loc::loadMessages(__FILE__);

include_once("head.php");

if (!empty($arResult['ERROR_MESSAGE']))
{
	return;
}


$filterIdsTrashCan = array();
$filterIdsStorage = array();

$workerInProcesTrashCan = false;
$workerInProcesUnnecessaryVersion = false;

if ($arResult['TrashCan']['FILE_COUNT'] > 0)
{
	foreach($arResult['TrashCan']['LIST'] as $row)
	{
		$filterIdsTrashCan[] = $row['ID'];
	}
	$workerInProcesTrashCan = ($arResult['TrashCan']['WORKER_COUNT_DROP_TRASHCAN'] > 0);
}
if ($arResult['Storage']['FILE_COUNT'] > 0)
{
	foreach($arResult['Storage']['LIST'] as $row)
	{
		$filterIdsStorage[] = $row['ID'];
	}
	$workerInProcesUnnecessaryVersion = ($arResult['Storage']['WORKER_COUNT_DROP_UNNECESSARY_VERSION'] > 0);
}

?>
<div id="bx-disk-volume-main-block" class="disk-volume-wrap <? if ($arResult['QUEUE_RUNNING']): ?>disk-volume-running<? endif; ?>">
<?

	if($arResult['DISK_EMPTY'])
	{
		?>
		<div id="bx-disk-volume-starter" class="disk-volume-container">
			<div class="disk-volume-left-block">
				<h1 class="disk-volume-main-title"><?= Loc::getMessage('DISK_VOLUME_START_TITLE')?></h1>
				<div id="disk-volume-description" class="disk-volume-text">
					<div class="disk-volume-item"><?= Loc::getMessage('DISK_VOLUME_START_FILES_NONE'); ?></div>
					<div class="disk-volume-item"><?= Loc::getMessage('DISK_VOLUME_START_COMMENT_EXPERT'); ?></div>
				</div>
			</div>
			<div class="disk-volume-right-block">
				<div class="disk-volume-logo-main"></div>
			</div>
		</div>
		<?
	}
	elseif(!$arResult['DATA_COLLECTED'] || $arResult['QUEUE_RUNNING'])
	{
		?>
		<div id="bx-disk-volume-starter" class="disk-volume-container">
			<div class="disk-volume-left-block">
				<h1 class="disk-volume-main-title"><?= Loc::getMessage('DISK_VOLUME_START_TITLE')?></h1>
				<div id="disk-volume-description" class="disk-volume-text">
					<div class="disk-volume-item"><?= Loc::getMessage('DISK_VOLUME_START_COMMENT'); ?></div>
					<div class="disk-volume-item"><?= Loc::getMessage('DISK_VOLUME_START_COMMENT_EXPERT'); ?></div>
				</div>
			</div>
			<div class="disk-volume-right-block">
				<div class="disk-volume-logo-main"></div>
			</div>
		</div>
		<div id="bx-disk-volume-buttons" class="webform-buttons pinable-block disk-volume-button-container">
			<button id="bx-disk-volume-link-measure" class="webform-small-button webform-small-button-accept disk-volume-button-margin-top">
				<span class="webform-small-button-text"><?= Loc::getMessage("DISK_VOLUME_MEASURE_DATA"); ?></span>
			</button>
		</div>

		<div id="bx-disk-volume-process" class="disk-volume-container disk-volume-wrap-state">
			<h1 id="bx-disk-volume-process-title" class="disk-volume-title"><?= Loc::getMessage('DISK_VOLUME_MEASURE_TITLE')?></h1>
			<div id="bx-disk-volume-loader" class="disk-volume-loader">
				<div class="disk-volume-loader-logo"></div>
				<svg class="disk-volume-circular" viewBox="25 25 50 50">
					<circle class="disk-volume-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
				</svg>
			</div>
			<div id="bx-disk-volume-loader-progress-bar" class="disk-volume-loader-progress-bar">
				<div class="disk-volume-loader-progress-bar-number">0%</div>
				<div class="disk-volume-loader-progress-bar-line">
					<div class="disk-volume-loader-progress-bar-line-active"></div>
				</div>
			</div>
			<div class="disk-volume-info" >
				<?= Loc::getMessage("DISK_VOLUME_MEASURE_PROCESS"); ?>
				<span id="bx-disk-volume-process-cancel" class="disk-volume-cancel-link" >
					<?= Loc::getMessage('DISK_VOLUME_CANCEL') ?>
				</span>
			</div>
		</div>
		<?
	}
	else
	{
		if ($arResult["ADMIN_MODE"])
		{
			?>
			<div class="disk-volume-header-container">
				<div class="disk-volume-header-icon disk-volume-header-icon-default-disk"></div>
				<div class="disk-volume-header-block">
					<div class="disk-volume-header-title"><?= Loc::getMessage("DISK_VOLUME_DISK_B24") ?></div>
					<div id="bx-disk-volume-total-disk-size" class="disk-volume-header-amount-info">
						<? if ($arResult['TOTAL_FILE_SIZE'] > 0): ?>
							<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_USEAGE", array('#FILE_SIZE#' => $arResult['TOTAL_FILE_SIZE_FORMAT'])) ?>
						<? endif; ?>
					</div>
					<div id="bx-disk-volume-total-disk-count" class="disk-volume-header-amount-info">
						<? if ($arResult['TOTAL_FILE_COUNT'] > 0): ?>
							<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_COUNT", array('#FILE_COUNT#' => $arResult['TOTAL_FILE_COUNT'])) ?>
						<? endif; ?>
					</div>
				</div>
			</div>
			<?
		}
		else
		{
			?>
			<div class="disk-volume-header-container">
				<div class="disk-volume-header-block">
					<div class="disk-volume-header-title"><?= $arResult['Storage']['TITLE']; ?></div>
					<div id="bx-disk-volume-total-disk-size" class="disk-volume-header-amount-info">
						<? if ($arResult['TOTAL_FILE_SIZE'] > 0): ?>
							<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_USEAGE", array('#FILE_SIZE#' => $arResult['TOTAL_FILE_SIZE_FORMAT'])) ?>
						<? endif; ?>
					</div>
					<div id="bx-disk-volume-total-disk-count" class="disk-volume-header-amount-info">
						<? if ($arResult['TOTAL_FILE_COUNT'] > 0): ?>
							<?= Loc::getMessage("DISK_VOLUME_DISK_TOTAL_COUNT", array('#FILE_COUNT#' => $arResult['TOTAL_FILE_COUNT'])) ?>
						<? endif; ?>
					</div>
				</div>
			</div>
			<?
		}

		if ($arResult["ADMIN_MODE"])
		{
			$plotIndicatorId = 'MODULES';
		}
		else
		{
			$plotIndicatorId = \Bitrix\Disk\Volume\FileType::getIndicatorId();
		}

		if (count($arResult[$plotIndicatorId]['LIST']) > 0)
		{
			?>
			<div class="bx-disk-interface-percent-diagram">
			<?
			if ($arResult['ONLY_DISK_MODE'])
			{
				$sizeField = 'DISK_SIZE';
			}
			else
			{
				$sizeField = 'FILE_SIZE';
			}
			$pp = 0;
			$tt = 0;
			$legend = array();
			foreach ($arResult[$plotIndicatorId]['LIST'] as $inx => $row)
			{
				if ($row[$sizeField] <= 0)
				{
					continue;
				}
				$indicatorId = $row['INDICATOR'];
				$color = $component->pastelColors();
				$width = ($row['PERCENT'] > .1 ? $row['PERCENT'].'' : '0');
				$percent = ($row['PERCENT'] < .1 ? '&lt; 0.1%' : $row['PERCENT'].'%');
				$title = $row['TITLE']." ".$row["{$sizeField}_FORMAT"]." ({$percent})";
				$alt = $row["{$sizeField}_FORMAT"]." ({$percent})";

				$pp += $row['PERCENT'];
				$tt += $row[$sizeField];

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
				$color = $component->pastelColors(true);
				$width = ($row['PERCENT'] > .1 ? $row['PERCENT'].'' : '0');
				$percent = ($row['PERCENT'] < .1 ? '&lt; 0.1%' : $row['PERCENT'].'%');
				$title = $row['TITLE']." ".$row['DISK_SIZE_FORMAT']." ({$percent})";
				$alt = $row['DISK_SIZE_FORMAT']." ({$percent})";
				if ($arResult['ONLY_DISK_MODE'])
				{
					$pp += $row['PERCENT'];
					$tt += $row['DISK_SIZE'];
				}
				else
				{
					$pp += $row['PERCENT'];
					$tt += $row['FILE_SIZE'];
				}
				?>
				<div class="bx-disk-interface-percent-part" style="flex:<?= $width ?>; background-color:<?= $color ?>;" title="<?= $title ?>"></div>
				<?
				$legend['other'] = array(
					'color' => $color,
					'title' => $row['TITLE'],
					'alt' => $alt,
				);
			}
			?>
			</div>
			<!--<?
				echo "\n". $pp;
				echo "\n". $tt;
				echo "\n". \Cfile::FormatSize($tt);
			?>-->
			<div class="bx-disk-interface-percent-legend">
				<div class="bx-disk-interface-percent-legend-title"><?= Loc::getMessage('DISK_VOLUME_DISK_USAGE'); ?></div>
				<?
				foreach ($legend as $row)
				{
					?>
					<div class="bx-disk-interface-percent-legend-part">
						<span class="bx-disk-interface-percent-legend-part-circle" style="background-color:<?=$row['color']?>;"></span>
						<span class="bx-disk-interface-percent-legend-part-text" title="<?= $row['alt'] ?>"><?= $row['title'] ?></span>
					</div>
					<?
				}
				?>
			</div>
			<?
		}


		if ($arResult['DROP_TOTAL_COUNT'] > 0)
		{
			?>
			<h1 class="disk-volume-title"><?= Loc::getMessage('DISK_VOLUME_MAY_DROP_TITLE') ?></h1>
			<div class="disk-volume-space-amount">
				<span id="bx-disk-volume-drop-size-digit"><?= $arResult['DROP_TOTAL_SIZE_DIGIT'] ?></span>
				<span id="bx-disk-volume-drop-size-units" class="disk-volume-space-amount-item"><?= $arResult['DROP_TOTAL_SIZE_UNITS'] ?></span>
			</div>
			<? if($arResult['DROP_TOTAL_SIZE'] == 0): ?>
				<div class="disc-volume-file-count">(<?= Loc::getMessage('DISK_VOLUME_COUNT', array('#FILE_COUNT#' => $arResult['DROP_TOTAL_COUNT']))?>)</div>
			<? endif; ?>
			<div class="disc-volume-notice"><?= Loc::getMessage("DISK_VOLUME_MAY_DROP"); ?></div>
			<div class="webform-buttons pinable-block ">
				<div id="disc-volume-space-selector" class="disc-volume-space-entity-container">
					<? if ($arResult['DROP_UNNECESSARY_VERSION_COUNT'] > 0): ?>
						<div id="disc-volume-space-unnecessaryVersion" data-checked="N" data-type="unnecessaryVersion" class="disc-volume-space-entity-block <?
										if ($workerInProcesUnnecessaryVersion): ?>disc-volume-space-entity-block-inprocess<? endif ?>">
							<div class="disc-volume-space-entity-checkbox"></div>
							<div class="disc-volume-space-entity-inprocess">
								<svg class="disc-volume-circular" viewBox="25 25 50 50">
									<circle class="disc-volume-circular-path" cx="50" cy="50" r="20"  stroke-miterlimit="10"></circle>
									<circle class="disc-volume-circular-inner-path" cx="50" cy="50" r="20"  stroke-miterlimit="10" ></circle>
								</svg>
							</div>
							<div class="disc-volume-space-entity-image disc-volume-space-entity-image-shredder"></div>
							<div class="disc-volume-space-entity-inner">
								<div class="disc-volume-space-text"><?= Loc::getMessage('DISK_VOLUME_MAY_DROP_UNNECESSARY_VERSION') ?></div>
								<div id="bx-disk-volume-total-unnecessary-format" class="disc-volume-space-text-grey">
									<?= $arResult['DROP_UNNECESSARY_VERSION_FORMAT'] ?>
									<? if($arResult['DROP_UNNECESSARY_VERSION'] == 0): ?>
										(<?= Loc::getMessage('DISK_VOLUME_COUNT', array('#FILE_COUNT#' => $arResult['DROP_UNNECESSARY_VERSION_COUNT']))?>)
									<? endif; ?>
								</div>
								<div class="disc-volume-notice-small disk-volume-hint" data-hint="unnecessary_version"><?= Loc::getMessage('DISK_VOLUME_MAY_DROP_UNNECESSARY_VERSION_NOTE') ?></div>
							</div>
						</div>
					<? endif ?>

					<? if ($arResult['DROP_TRASHCAN_COUNT'] > 0): ?>
						<div id="disc-volume-space-trashCan" data-checked="N" data-type="trashcan" class="disc-volume-space-entity-block <?
									if ($workerInProcesTrashCan): ?>disc-volume-space-entity-block-inprocess<? endif ?>">
							<div class="disc-volume-space-entity-checkbox"></div>
							<div class="disc-volume-space-entity-inprocess">
								<svg class="disc-volume-circular" viewBox="25 25 50 50">
									<circle class="disc-volume-circular-path" cx="50" cy="50" r="20"  stroke-miterlimit="10"/>
									<circle class="disc-volume-circular-inner-path" cx="50" cy="50" r="20"  stroke-miterlimit="10"/>
								</svg>
							</div>
							<div class="disc-volume-space-entity-image disc-volume-space-entity-image-basket"></div>
							<div class="disc-volume-space-entity-inner">
								<div class="disc-volume-space-text"><?= Loc::getMessage('DISK_VOLUME_MAY_DROP_TRASHCAN') ?></div>
								<div  id="bx-disk-volume-total-trashcan-format" class="disc-volume-space-text-grey">
									<?= $arResult['DROP_TRASHCAN_FORMAT'] ?>
									<? if($arResult['DROP_TRASHCAN'] == 0): ?>
										(<?= Loc::getMessage('DISK_VOLUME_COUNT', array('#FILE_COUNT#' => $arResult['DROP_TRASHCAN_COUNT']))?>)
									<? endif; ?>
								</div>
								<div class="disc-volume-notice-small disk-volume-hint" data-hint="dropped_trashcan"><?= Loc::getMessage('DISK_VOLUME_MAY_DROP_TRASHCAN_NOTE') ?></div>
							</div>
						</div>
					<? endif ?>
				</div>

				<div class="disk-volume-button-container">
					<button id="bx-disk-volume-link-run-cleaner" class="webform-button webform-button-blue disc-volume-button webform-button-disable"><?= Loc::getMessage("DISK_VOLUME_RUN_CLEANER"); ?></button>
				</div>

				<div class="disc-volume-notice-small">
					<span id="bx-disk-volume-space" style="height:0;display:none">
						<span id="bx-disk-volume-space-amount" class="disk-volume-space-amount-small"></span>
						<?= Loc::getMessage('DISK_VOLUME_AVAILABLE_SPACE'); ?>
					</span>
				</div>



			</div>
			<?
		}
		else
		{
			?>
			<h1 class="disk-volume-title"><?= Loc::getMessage('DISK_VOLUME_MAY_DROP_TITLE') ?></h1>
			<div class="disk-volume-space-amount">
				<?= $arResult['DROP_TOTAL_SIZE_DIGIT'] ?>
				<span class="disk-volume-space-amount-item"><?= $arResult['DROP_TOTAL_SIZE_UNITS'] ?></span>
			</div>
			<div class="disc-volume-notice"><?= Loc::getMessage("DISK_VOLUME_CANNT_DROP"); ?></div>
			<div class="webform-buttons pinable-block ">

				<div class="disk-volume-button-container">
					<a href="<?= $component->getActionUrl(array('reload' => 'Y', 'action' => $component::ACTION_DEFAULT)); ?>" class="ui-btn ui-btn-lg ui-btn-light-border disk-volume-reload-link"><?= Loc::getMessage("DISK_VOLUME_MEASURE_DATA_REPEAT");?></a>
				</div>

			</div>
			<?
		}
	}

?>
</div>

<script>
	BX(function () {

		var drop = {trashcan: false, unnecessaryVersion: false},
			startMeasureButton = BX('bx-disk-volume-link-measure'),
			buttonRunCleaner = BX('bx-disk-volume-link-run-cleaner'),
			spaceLabel = BX('bx-disk-volume-space'),
			spaceAmountLabel = BX('bx-disk-volume-space-amount'),
			spaceSelectorUnnecessaryVersion = BX('disc-volume-space-unnecessaryVersion'),
			spaceSelectorTrashCan = BX('disc-volume-space-trashCan');

		BX.bind(startMeasureButton, 'click', BX.proxy(BX.Disk.measureManager.repeatMeasure, BX.Disk.measureManager));

		BX.bind(BX('bx-disk-volume-process-cancel'), 'click', function(e)
		{
			BX.Disk.measureManager.stopQueue();

			BX.removeClass(buttonRunCleaner, 'webform-button-wait');
			BX.removeClass(startMeasureButton, 'webform-button-wait');

			BX.Disk.showActionModal({
				text: BX.message('DISK_VOLUME_PERFORMING_CANCEL_MEASURE'),
				showLoaderIcon: true,
				autoHide: false
			});

			BX.Disk.measureManager.callAction({
				action: '<?= $component::ACTION_PURIFY ?>',
				queueStop: 'Y',
				doNotShowModalAlert: true,
				//doNotFollowRedirect: true,
				after: function(){
					BX.Disk.measureManager.progressBarHide();

					BX.removeClass(buttonRunCleaner, 'webform-button-wait');
					BX.removeClass(startMeasureButton, 'webform-button-wait');
					startMeasureButton.disabled = false;

					BX.addClass(spaceSelectorUnnecessaryVersion, 'disc-volume-space-entity-block-inprocess');
					BX.addClass(spaceSelectorTrashCan, 'disc-volume-space-entity-block-inprocess');

					var w = BX.PopupWindowManager.getCurrentPopup();
					if (w) {
						w.close();
						w.destroy();
					}
				}
			});

			return BX.PreventDefault(e);
		});



		BX.Disk.measureManager.amount.DROP_TRASHCAN_FORMAT = '<?= $arResult['DROP_TRASHCAN_FORMAT'] ?>';
		BX.Disk.measureManager.amount.DROP_UNNECESSARY_VERSION_FORMAT = '<?= $arResult['DROP_UNNECESSARY_VERSION_FORMAT'] ?>';
		BX.Disk.measureManager.amount.DROP_TOTAL_SIZE_FORMAT = '<?= $arResult['DROP_TOTAL_SIZE_FORMAT'] ?>';


		BX.bindDelegate(
			BX('disc-volume-space-selector'),
			'click',
			{className: 'disc-volume-space-entity-block'},
			function()
			{
				var inProcess = BX.hasClass(this, 'disc-volume-space-entity-block-inprocess');
				if(inProcess)
				{
					return;
				}

				var checked = (BX.data(this, 'checked') === 'Y');
				var type = BX.data(this, 'type');
				if(checked)
				{
					drop[type] = false;
					BX.data(this, 'checked', 'N');
					BX.removeClass(this, 'disc-volume-space-entity-checkbox-active');
				}
				else
				{
					drop[type] = true;
					BX.data(this, 'checked', 'Y');
					BX.addClass(this, 'disc-volume-space-entity-checkbox-active');
				}
				if(drop.unnecessaryVersion || drop.trashcan)
				{
					BX.removeClass(buttonRunCleaner, 'webform-button-disable');
				}
				else
				{
					BX.addClass(buttonRunCleaner, 'webform-button-disable');
				}

				if(drop.unnecessaryVersion || drop.trashcan)
				{
					if(drop.unnecessaryVersion && drop.trashcan)
					{
						spaceAmountLabel.innerHTML = BX.Disk.measureManager.amount.DROP_TOTAL_SIZE_FORMAT;
					}
					else if(drop.unnecessaryVersion)
					{
						spaceAmountLabel.innerHTML = BX.Disk.measureManager.amount.DROP_UNNECESSARY_VERSION_FORMAT;
					}
					else if(drop.trashcan)
					{
						spaceAmountLabel.innerHTML = BX.Disk.measureManager.amount.DROP_TRASHCAN_FORMAT;
					}
					if(spaceLabel.style.display !== "block")
					{
						spaceLabel.style.height = 0;
						spaceLabel.style.opacity = 0;
						spaceLabel.style.display = "block";

						setTimeout(function (){
							spaceLabel.style.opacity = 1;
							spaceLabel.style.height = spaceLabel.scrollHeight + "px";
						}, 10);
					}
				}
				else
				{
					spaceLabel.style.opacity = 0;
					spaceLabel.style.height = 0;

					setTimeout( function(){
						spaceLabel.style.display = "none";
					}, 10);
				}
			}
		);

		BX.bind(buttonRunCleaner, 'click', function()
		{
			if(drop.unnecessaryVersion || drop.trashcan)
			{
				var param = {};
				if(drop.unnecessaryVersion)
				{
					param.deleteUnnecessaryVersion = 'Y';
					param.metric = BX.Disk.measureManager.getMetricMark('GLOBAL_UNNECESSARY_CLEAN');
				}

				<? if(count($filterIdsTrashCan) > 0): ?>param.filterIdsTrashCan = [<?= implode(',', $filterIdsTrashCan); ?>];<? endif ?>
				if(drop.trashcan)
				{
					param.emptyTrashcan = 'Y';
					param.metric1 = BX.Disk.measureManager.getMetricMark('GLOBAL_TRASHCAN_CLEAN');
				}

				<? if(count($filterIdsStorage) > 0): ?>param.filterIdsStorage = [<?= implode(',', $filterIdsStorage); ?>];<? endif ?>
				<? if($arResult['STORAGE_ID'] > 0): ?>param.storageId = '<?= $arResult['STORAGE_ID'] ?>';<? endif ?>

				BX.addClass(startMeasureButton, 'webform-button-wait');
				BX.addClass(buttonRunCleaner, 'webform-button-wait');
				buttonRunCleaner.disabled = true;

				BX.Disk.measureManager.callAction(BX.merge(
					{
						'action': '<?= $component::ACTION_SETUP_CLEANER_JOB ?>',
						'after': function () {
							buttonRunCleaner.disabled = false;
							BX.removeClass(startMeasureButton, 'webform-button-wait');
							BX.removeClass(buttonRunCleaner, 'webform-button-wait');
							if (param.deleteUnnecessaryVersion === 'Y')
							{
								BX.addClass(spaceSelectorUnnecessaryVersion, 'disc-volume-space-entity-block-inprocess');
							}
							if (param.emptyTrashcan === 'Y')
							{
								BX.addClass(spaceSelectorTrashCan, 'disc-volume-space-entity-block-inprocess');
							}
						}
					},
					param
				));
			}
		});


		BX.addCustomEvent(window, "OnDiskVolumeStepperFinished", function(){
			var blockInProcess = BX.findChildrenByClassName(BX('bx-disk-volume-main-block'), 'disc-volume-space-entity-block-inprocess');
			for (var i in blockInProcess)
			{
				BX.removeClass(blockInProcess[i], 'disc-volume-space-entity-block-inprocess');
			}
		});

	});
</script>

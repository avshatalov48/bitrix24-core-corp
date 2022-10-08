<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

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


$currentUri = new Main\Web\Uri(Bitrix\Main\Context::getCurrent()->getRequest()->getRequestUri());

CJSCore::Init([
	'disk',
	'core',
	'ui.buttons',
	'viewer',
	'socnetlogdest',
	'bp_starter',
	'ui.design-tokens',
]);

Loc::loadMessages(__DIR__ . '/template.php');
$APPLICATION->setTitle(Loc::getMessage('DISK_FILE_VIEW_FILE_TITLE_BP', ['#NAME#' => $arResult['FILE']['NAME'],]));
$sortBpLog = false;
?>

<div>
	<? if($arParams['STATUS_BIZPROC']) {
	$bizProcIndex = 0;
	?>
	<form action="<?= $arResult['PATH_TO_FILE_VIEW'] ?>" method="POST" class="bizproc-form" name="start_workflow_form1" id="start_workflow_form1">
	<?= bitrix_sessid_post();
		if (!empty($arResult["ERROR_MESSAGE"]))
		{
			ShowError($arResult["ERROR_MESSAGE"]);
		}
		if(!isset($_GET['log_workflow'])) { ?>
			<ul class="bizproc-list bizproc-document-states">

				<? $this->setViewTarget("inside_pagetitle", 10); ?>
				<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
					<div class="pagetitle-container pagetitle-align-right-container">
						<span id="bx-disk-run-bp" class="ui-btn ui-btn-primary ui-btn-dropdown"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_BIZPROC_START') ?></span>
					</div>
				</div>
				<? $this->endViewTarget(); ?>
			<?
			if(!empty($arResult['BIZPROC_LIST']))
			{
				foreach($arResult['BIZPROC_LIST'] as $key => $bizProcArray)
				{
					$checkAction = true;
					$bizProcIndex++;
					$strDelBizProc = "javascript:BX.Disk['FileViewClass_{$component->getComponentId()}'].deleteBizProc('{$bizProcArray['ID']}');";
					$strStopBizProc = "javascript:BX.Disk['FileViewClass_{$component->getComponentId()}'].stopBizProc('{$bizProcArray['ID']}');";
					$strLogBizProc = "javascript:BX.Disk['FileViewClass_{$component->getComponentId()}'].showLogBizProc('{$key}');";
					$trClass = "bizproc-document-process";
					if(!mb_strlen($bizProcArray["WORKFLOW_STATUS"]) > 0)
					{
						$trClass = "bizproc-document-finished";
					}
					elseif(!empty($bizProcArray['TASK']))
					{
						$trClass = "bizproc-document-hastasks";
					}
					?>
				<li class="bizproc-list-item <?= $trClass ?>">
					<table class="bizproc-table-main" id="<?= $bizProcArray['ID'] ?>">
						<tr>
							<th class="bizproc-field-name"><?=htmlspecialcharsbx($bizProcArray['TEMPLATE_NAME'])?></th>
							<th class="bizproc-field-value">
								<? if($bizProcArray["WORKFLOW_STATUS"] <> '') { ?>
									<a class="webform-small-button webform-button-transparent" href="<?= $strStopBizProc ?>"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_STOP_BUTTON') ?></a>
								<? }else if($arResult['BIZPROC_PERMISSION']['DROP']) { $checkAction = false; ?>
									<a class="webform-small-button webform-button-transparent" href="<?= $strDelBizProc ?>"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_DELETE_BUTTON') ?></a>
								<? } ?>
								<a class="webform-small-button webform-button-transparent" href="<?= $strLogBizProc ?>"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_LOG_BUTTON') ?></a>
							</th>
						</tr>
						<? if(!empty($bizProcArray['STATE_MODIFIED'])) { ?>
							<tr class="bizproc-item-row-first">
								<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_STATE_MODIFIED') ?></td>
								<td class="bizproc-field-value"><?= $bizProcArray['STATE_MODIFIED'] ?></td>
							</tr>
						<? } ?>
						<tr <? if(empty($bizProcArray['EVENTS']) && empty($bizProcArray['TASK']) && !$checkAction){?>class="bizproc-item-row-last" <?}?>>
							<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_STATE_TITLE') ?></td>
							<td class="bizproc-field-value"><?= $bizProcArray['STATE_TITLE'] ?></td>
						</tr>
						<? if(!empty($bizProcArray['DUMP_WORKFLOW']) && $checkAction) { ?>
							<tr <? if(empty($bizProcArray['EVENTS']) && empty($bizProcArray['TASK'])){?>class="bizproc-item-row-last" <?}?>>
								<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_MODIFICATION') ?></td>
								<td class="bizproc-field-value"><?=implode("<br />", $bizProcArray['DUMP_WORKFLOW'])?></td>
							</tr>
						<? } ?>
						<? if(!empty($bizProcArray['TASK'])) { ?>
							<tr <? if(empty($bizProcArray['EVENTS'])){?>class="bizproc-item-row-last" <?}?>>
								<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_TASKS_BIZPROC') ?></td>
								<td class="bizproc-field-value">
									<a href="<?= $bizProcArray['TASK']['URL'] ?>"><?= $bizProcArray['TASK']['TASK_NAME'] ?></a>
								</td>
							</tr>
						<? } ?>
						<? if(!empty($bizProcArray['EVENTS'])) { ?>
							<tr class="bizproc-item-row-last">
								<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_RUN_CMD') ?></td>
								<td class="bizproc-field-value">
									<input type="hidden" name="bizproc_id_<?= $bizProcIndex ?>" value="<?= $bizProcArray['ID'] ?>">
									<input type="hidden" name="bizproc_template_id_<?= $bizProcIndex ?>" value="<?= $bizProcArray["TEMPLATE_ID"] ?>">
									<select name="bizproc_event_<?= $bizProcIndex ?>">
										<option value=""><?= Loc::getMessage("DISK_FILE_VIEW_BIZPROC_RUN_CMD_NO") ?></option>
										<? foreach ($bizProcArray['EVENTS'] as $event) { ?>
											<option value="<?= htmlspecialcharsbx($event["NAME"]) ?>">
												<?= htmlspecialcharsbx($event["TITLE"]) ?>
											</option>
										<? } ?>
									</select>
									<input type="hidden" name="bizproc_index" value="<?= $bizProcIndex ?>" />
									<input type="submit" name="save" value="<?= Loc::getMessage("DISK_FILE_VIEW_BIZPROC_APPLY")?>" />
								</td>
							</tr>
						<? } ?>
					</table>
				</li>
				<?}
			}?>
			</ul>
		<?}else{ $sortBpLog = true; ?>
			<ul class="bizproc-list bizproc-document-states">
			<?
			$checkAction = true;
			$idWorkflow = $arResult['BIZPROC_LIST'][$_GET['log_workflow']]['ID'];
			$strDelBizProc = "javascript:BX.Disk['FileViewClass_{$component->getComponentId()}'].deleteBizProc('{$idWorkflow}');";
			$strStopBizProc = "javascript:BX.Disk['FileViewClass_{$component->getComponentId()}'].stopBizProc('{$idWorkflow}');";
			$trClass = "bizproc-document-process";
			if(!mb_strlen($arResult['BIZPROC_LIST'][$_GET['log_workflow']]["WORKFLOW_STATUS"]) > 0)
			{
				$trClass = "bizproc-document-finished";
			}
			elseif(!empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TASK']))
			{
				$trClass = "bizproc-document-hastasks";
			}
			?>
				<li class="bizproc-list-item <?= $trClass ?>">
				<table class="bizproc-table-main" id="<?= $idWorkflow ?>">
					<tr>
						<th class="bizproc-field-name"><?=htmlspecialcharsbx($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TEMPLATE_NAME'])?></th>
						<th class="bizproc-field-value">
							<? if($arResult['BIZPROC_LIST'][$_GET['log_workflow']]["WORKFLOW_STATUS"] <> '') { ?>
								<a class="webform-small-button webform-button-transparent" href="<?= $strStopBizProc ?>"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_STOP_BUTTON') ?></a>
							<? }else if($arResult['BIZPROC_PERMISSION']['DROP']) { $checkAction = false; ?>
								<a class="webform-small-button webform-button-transparent" href="<?= $strDelBizProc ?>"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_DELETE_BUTTON') ?></a>
							<? } ?>
						</th>
					</tr>
					<tr class="bizproc-item-row-first">
						<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_STATE_MODIFIED') ?></td>
						<td class="bizproc-field-value"><?= $arResult['BIZPROC_LIST'][$_GET['log_workflow']]['STATE_MODIFIED'] ?></td>
					</tr>
					<tr <? if(empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['EVENTS']) && empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TASK']) && !$checkAction){?>class="bizproc-item-row-last" <?}?>>
						<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_STATE_TITLE') ?></td>
						<td class="bizproc-field-value"><?= $arResult['BIZPROC_LIST'][$_GET['log_workflow']]['STATE_TITLE'] ?></td>
					</tr>
					<? if(!empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['DUMP_WORKFLOW']) && $checkAction) { ?>
						<tr <? if(empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['EVENTS']) && empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TASK'])){?>class="bizproc-item-row-last" <?}?>>
							<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_MODIFICATION') ?></td>
							<td class="bizproc-field-value"><?=implode("<br />", $arResult['BIZPROC_LIST'][$_GET['log_workflow']]['DUMP_WORKFLOW'])?></td>
						</tr>
					<? } ?>
					<? if(!empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TASK'])) { ?>
						<tr <? if(empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['EVENTS'])){?>class="bizproc-item-row-last" <?}?>>
							<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_TASKS_BIZPROC') ?></td>
							<td class="bizproc-field-value">
								<a href="<?= $arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TASK']['URL'] ?>"><?= $arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TASK']['TASK_NAME'] ?></a>
							</td>
						</tr>
					<? } ?>
					<? if(!empty($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['EVENTS'])) { ?>
						<tr class="bizproc-item-row-last">
							<td class="bizproc-field-name"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_RUN_CMD') ?></td>
							<td class="bizproc-field-value">
								<input type="hidden" name="bizproc_id_1" value="<?= $idWorkflow ?>">
								<input type="hidden" name="bizproc_template_id_1" value="<?= $arResult['BIZPROC_LIST'][$_GET['log_workflow']]['TEMPLATE_ID'] ?>">
								<select name="bizproc_event_1">
									<option value=""><?= Loc::getMessage("DISK_FILE_VIEW_BIZPROC_RUN_CMD_NO") ?></option>
									<? foreach ($arResult['BIZPROC_LIST'][$_GET['log_workflow']]['EVENTS'] as $event) { ?>
										<option value="<?= htmlspecialcharsbx($event["NAME"]) ?>">
											<?= htmlspecialcharsbx($event["TITLE"]) ?>
										</option>
									<? } ?>
								</select>
								<input type="hidden" name="bizproc_index" value="1" />
								<input type="submit" name="save" value="<?= Loc::getMessage("DISK_FILE_VIEW_BIZPROC_APPLY")?>" />
							</td>
						</tr>
					<? } ?>
					<tr class="bizproc-item-row-last">
						<td colspan="2">
							<?
							$APPLICATION->IncludeComponent("bitrix:bizproc.log", "", Array(
									"MODULE_ID" => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
									"ENTITY" => \Bitrix\Disk\BizProcDocument::className(),
									"DOCUMENT_TYPE" => $arResult['STORAGE_ID'],
									"COMPONENT_VERSION" => 2,
									"DOCUMENT_ID" => $arResult['FILE']['ID'],
									"ID" => $idWorkflow,
									'INLINE_MODE' => 'Y',
								),$component,
								array("HIDE_ICONS" => "Y")
							);
							?>
						</td>
					</tr>
				</table>
				</li>
			</ul>
		<?
		} ?>
	</form>
</div>
<? } ?>

<script type="text/javascript">
	BX(function () {
		const location = "<?= $arResult['PATH_TO_FILE_VIEW'] ?>";
		BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'] = new BX.Disk.FileViewClass({
			withoutEventBinding: true,
			object: {
				id: <?= $arResult['FILE']['ID'] ?>
			},
			urls: {
				fileShowBp: BX.util.add_url_param(location, {action: 'showBp'})
			}
		});

		BX.bind(BX('bx-disk-run-bp'), 'click', function(event){
			BX.PopupMenu.show('BizprocList-run', BX.getEventTarget(event), <?= Main\Web\Json::encode($arResult['BP_ITEMS_FOR_START'])?>,
				{
					angle: {
						position: 'top',
						offset: 45
					},
					autoHide: true,
					overlay: {
						opacity: 0.01
					}
				}
			);
		});

		BX.SidePanel.Instance.bindAnchors({
			rules: [
				{
					condition: [
						'/'
					],
					handler: function (event, link) {
						top.document.location = link.url;
						event.preventDefault();
					}
				}
			]
		});

		if('<?= $sortBpLog ?>')
		{
			BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'].sortBizProcLog();
		}
	});
</script>
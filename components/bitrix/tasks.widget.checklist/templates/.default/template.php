<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be
?>

<?//$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks<?if(!$arParams['CAN_REORDER']):?> nodrag<?endif?> tasks-wg-checklist">

		<?//$helper->displayWarnings();?>

		<?$inputPrefix = htmlspecialcharsbx($arParams["INPUT_PREFIX"]);?>
		<?$tData = $arResult['TEMPLATE_DATA'];?>

		<div class="task-checklist-title"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_TEMPLATE_BLOCK_TITLE')?><span class="js-id-checklist-top-counter-set task-checklist-status<?=($tData['COUNTER_CHECKED'] > 0 ? '' : ' invisible')?>">&nbsp;(<?=Loc::getMessage('TASKS_TTDP_CHECKLIST_COMPLETE')?> <span class="js-id-checklist-complete-counter"><?=$tData['COUNTER_CHECKED']?></span> <?=Loc::getMessage('TASKS_TTDP_CHECKLIST_OF')?> <span class="js-id-checklist-total-counter"><?=$tData['COUNTER_TOTAL']?></span>)</span></div>

		<div class="js-id-checklist-is-items js-id-checklist-items-ongoing tasks-checklist-dropzone">

			<div class="tasks-checklist-zone-marker"></div>
			<script data-bx-id="checklist-is-item" type="text/html">

				<?ob_start();?>
				<div data-item-value="{{VALUE}}" class="js-id-checklist-is-item js-id-checklist-is-item-{{VALUE}} tasks-checklist-item mode-read {{APPEARANCE}} {{READONLY}} {{ITEM_SET_INVISIBLE}}">
					<div class="task-checklist-field generic">
						<div class="task-checklist-field-inner">
							<span class="js-id-checklist-is-i-drag-handle task-field-drg-btn"></span>
							<input id="chl_item_{{VALUE}}" class="js-id-checklist-is-i-toggle task-checklist-field-checkbox" type="checkbox" {{CHECKED_ATTRIBUTE}} {{DISABLED_ATTRIBUTE}} />

							<?//read mode?>
							<label class="block-read task-checklist-field-label" for="chl_item_{{VALUE}}"><span class="js-id-checklist-is-i-number">{{NUMBER}}</span>.&nbsp;<span class="js-id-checklist-is-i-title {{STROKE_CSS}}">{{{DISPLAY}}}&nbsp;</span></label>
							<span class="js-id-checklist-is-i-edit block-read task-field-title-edit tasks-btn-edit"></span>

							<?//edit mode?>
							<input class="js-id-checklist-is-i-new-title block-edit task-checklist-field-add" type="text" value="{{TITLE}}" placeholder="<?=Loc::getMessage('TASKS_TTDP_CHECKLIST_WHAT_TO_BE_DONE')?>" maxlength="255" />
							<span class="js-id-checklist-is-i-apply block-edit tasks-btn-apply task-field-title-ok"></span>

							<?//any mode?>
							<span class="js-id-checklist-is-i-delete task-field-title-del tasks-btn-delete"></span>

							<input type="hidden" class="js-id-checklist-is-i-title-field" name="<?=$inputPrefix?>[{{VALUE}}][TITLE]" value="{{TITLE}}" />
						</div>
					</div>

					<div class="js-id-checklist-is-i-drag-handle task-field-divider separator">
						<div class="js-id-checklist-is-i-delete task-field-divider-close"></div>
					</div>

					<div class="tasks-checklist-item-marker"></div>

					<input type="hidden" name="<?=$inputPrefix?>[{{VALUE}}][ID]" value="{{ID}}" />
					<input class="js-id-checklist-is-i-sort-fld" type="hidden" name="<?=$inputPrefix?>[{{VALUE}}][<?=$tData['FIELDS']['SORT']?>]" value="{{<?=$tData['FIELDS']['SORT']?>}}" />
					<input class="js-id-checklist-is-i-complete-fld" type="hidden" name="<?=$inputPrefix?>[{{VALUE}}][<?=$tData['FIELDS']['CHECKED']?>]" value="{{<?=$tData['FIELDS']['CHECKED']?>}}" />
				</div>
				<?$template = trim(ob_get_flush());?>

			</script>
			<script data-bx-id="checklist-is-item-flying" type="text/html">
				<div class="task-checklist-field flying {{APPEARANCE}}">
					<div class="task-checklist-field-inner task-checklist-flying-generic">
						<span class="task-field-drg-btn"></span>
						<input id="chl_item_{{VALUE}}-f" class="task-checklist-field-checkbox" type="checkbox" {{CHECKED_ATTRIBUTE}} />
						<label for="chl_item_{{VALUE}}-f" class="task-checklist-field-label">{{{DISPLAY}}}</label>
					</div>
					<div class="task-field-divider task-checklist-flying-separator">
					</div>
				</div>
			</script>

			<?
			foreach($arParams['DATA'] as $item)
			{
				if(!$item['CHECKED'])
				{
					print($helper->fillTemplate($template, $item));
				}
			}
			?>

		</div>

		<?if($arParams['CAN_ADD']):?>
			<div class="task-checklist-field tasks-checklist-dropzone tasks-checklist-form"><?
				?><span class="js-id-checklist-is-add-item-form block-on task-checklist-field-inner-add invisible"><?
					?><span class="task-checklist-form-vpadding"><?
						?><input type="text" class="js-id-checklist-is-form-title task-checklist-field-add" placeholder="<?=Loc::getMessage('TASKS_TTDP_CHECKLIST_WHAT_TO_BE_DONE')?>" maxlength="255" />
						<span class="js-id-checklist-is-form-submit block-edit tasks-btn-apply task-field-title-ok"></span>
						<span class="js-id-checklist-is-form-close tasks-btn-delete task-field-title-del"></span>
					</span><?
				?></span><?

				?><div class="task-checklist-actions"><?
					?><span class="block-off task-dashed-link"><span class="js-id-checklist-is-open-form task-dashed-link-inner"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_ADD')?></span></span><?
					?><span class="js-id-checklist-add-separator task-dashed-link"><span class="js-id-checklist-is-add-separator task-dashed-link-inner"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_SEPARATOR')?></span></span><?
				?></div><?
			?></div>
		<?endif?>

		<div class="js-id-checklist-complete-block task-checklist-resolved<?=($tData['COUNTER_CHECKED'] > 0 ? '' : ' hidden')?>">
			<div class="task-checklist-subtitle"><span class="js-id-checklist-toggle-complete task-checklist-subtitle-arrow"><?=Loc::getMessage('TASKS_TTDP_CHECKLIST_COMPLETE')?> (<span class="js-id-checklist-complete-counter"><?=$tData['COUNTER_CHECKED']?></span>)</span></div>
			<div class="js-id-checklist-is-items-complete tasks-checklist-dropzone invisible">
				<div class="tasks-checklist-zone-marker"></div>

				<?
				foreach($arParams['DATA'] as $item)
				{
					if($item['CHECKED'])
					{
						print($helper->fillTemplate($template, $item));
					}
				}
				?>

			</div>
		</div>

		<?// in case of all items removed, the field should be sent anyway?>
		<input type="hidden" name="<?=$inputPrefix?>[]" value="" />
	</div>

	<?$helper->initializeExtension();?>

<?endif?>
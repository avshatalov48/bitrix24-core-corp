<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$blocks = $arParams['BLOCKS'];
$state = $arResult['JS_DATA']['state'];

$inputPrefix = $arParams['INPUT_PREFIX'];
?>

<?$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">
		<?$helper->displayWarnings();?>

		<div class="task-info">
			<div class="task-info-panel">
				<div class="task-info-panel-important"><?=$blocks['HEAD_TOP_RIGHT']['HTML']?></div>
				<div class="task-info-panel-title"><?=$blocks['HEAD_TOP_LEFT']['HTML']?></div>
			</div>
			<div class="task-info-editor">
				<?=$blocks['HEAD']['HTML']?>
			</div>
		</div>

		<?$blockClasses = array(); // tmp?>

		<?foreach($blocks['HEAD_BOTTOM'] as $block):?>

			<?
			$blockName = $block['CODE'];
			$blockNameJs = ToLower($block['CODE']);

			$pinableClass = $block['IS_PINABLE'] ? 'pinable-block' : '';
			$invisibleClass = $state['BLOCKS'][$blockName]['OPENED'] ? '' : 'invisible';
			$pinnedClass = $state['BLOCKS'][$blockName]['PINNED'] ? 'pinned' : '';
			?>
			<div data-block-name="<?=htmlspecialcharsbx($blockName)?>" class="task-checklist-container task-openable-block js-id-wfr-edit-form-<?=$blockNameJs?> <?=$pinableClass?> <?=$invisibleClass?> <?=$pinnedClass?>">

				<div class="task-options task-checklist">
					<?=$block['HTML']?>
				</div>

				<span data-target="<?=$blockNameJs?>" class="js-id-wfr-edit-form-pinner task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TWF_T_PINNER_HINT')?>"></span>
			</div>

		<?endforeach?>

		<div class="task-options task-options-main">

			<?if(count($blocks['STATIC'])):?>

				<div class="task-options-item-destination-wrap">

					<?foreach($blocks['STATIC'] as $block):?>

						<?
						$blockName = $block['CODE'];
						$blockNameJs = ToLower($block['CODE']);

						$pinableClass = $block['IS_PINABLE'] ? 'pinable-block' : '';
						$invisibleClass = $state['BLOCKS'][$blockName]['OPENED'] ? '' : 'invisible';
						$pinnedClass = $state['BLOCKS'][$blockName]['PINNED'] ? 'pinned' : '';
						?>

						<div data-block-name="<?=htmlspecialcharsbx($blockName)?>"
						     class="
						        js-id-wfr-edit-form-<?=htmlspecialcharsbx($blockNameJs)?>
						        task-openable-block
								<?=$pinableClass?> <?=$invisibleClass?> <?=$pinnedClass?>
							 ">

							<div class="task-options-item task-options-item-destination">

								<?if($block['IS_PINABLE']):?>
									<span data-target="<?=htmlspecialcharsbx($blockNameJs)?>" class="js-id-wfr-edit-form-pinner task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TWF_T_PINNER_HINT')?>"></span>
								<?endif?>

								<span class="task-options-item-param"><?=htmlspecialcharsbx($block['TITLE'])?></span>
								<div class="task-options-item-open-inner">

									<?=$block['HTML']?>

									<?if(count($block['TOGGLE'])):?>
										<span class="task-dashed-link task-dashed-link-add tasks-additional-block-link">
											<?foreach($block['TOGGLE'] as $link):?>
			                                    <span class="js-id-wfr-edit-form-toggler task-dashed-link-inner" data-target="<?=htmlspecialcharsbx(ToLower($link['TARGET']))?>"><?=htmlspecialcharsbx($link['TITLE'])?></span>
			                                <?endforeach?>
										</span>
									<?endif?>

								</div>

								<?if($block['SUB'] && count($block['SUB'])):?>
									<div class="task-options-item-open-inner task-options-item-open-inner-sh task-options-item-open-inner-sett">
										<?foreach($block['SUB'] as $sub):?>

											<?
											$subBlockName = $sub['CODE'];
											$subBlockNameJs = ToLower($sub['CODE']);

											$subPinableClass = $sub['IS_PINABLE'] ? 'pinable-block' : '';
											$subInvisibleClass = $state['BLOCKS'][$subBlockName]['OPENED'] ? '' : 'invisible';
											$subPinnedClass = $state['BLOCKS'][$subBlockName]['PINNED'] ? 'pinned' : '';
											?>

											<div data-block-name="<?=htmlspecialcharsbx($subBlockName)?>" class="
												js-id-wfr-edit-form-<?=htmlspecialcharsbx($subBlockNameJs)?>
												task-openable-block
												<?=$subPinableClass?> <?=$subInvisibleClass?> <?=$subPinnedClass?>"
											>
												<div class="task-options-sheduling-block">
													<div class="task-options-divider"></div>
													<?=$sub['HTML']?>
													<span data-target="<?=htmlspecialcharsbx($subBlockNameJs)?>" class="js-id-wfr-edit-form-pinner task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TWF_T_PINNER_HINT')?>"></span>
												</div>
											</div>
										<?endforeach?>
									</div>
								<?endif?>

							</div>
						</div>
					<?endforeach?>
				</div>

			<?endif?>

			<?// PINNED DYNAMIC?>
			<?if(count($blocks['DYNAMIC'])):?>

				<div class="js-id-wfr-edit-form-chosen-blocks">

					<?foreach($blocks['DYNAMIC'] as $block):?>

						<?
						$blockName = $block['CODE'];
						$blockNameJs = ToLower($block['CODE']);

						$invisibleClass = $state['BLOCKS'][$blockName]['OPENED'] ? '' : 'invisible';
						$pinnedClass = 'pinned';
						?>

						<div class="js-id-wfr-edit-form-<?=$blockNameJs?>-block-place wfr-edit-form-block-place">
							<?if($state['BLOCKS'][$blockName]['PINNED']):?>
								<div data-block-name="<?=$blockName?>" class="js-id-wfr-edit-form-<?=$blockNameJs?>-block pinable-block task-openable-block task-options-item-<?=$blockNameJs?> <?=$pinableClass?> <?=$invisibleClass?> <?=$pinnedClass?>">
									<div class="task-options-item">
										<span data-target="<?=$blockNameJs?>-block" class="js-id-wfr-edit-form-pinner task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_PINNER_HINT')?>"></span>
										<span class="task-options-item-param"><?=htmlspecialcharsbx($block['TITLE'])?></span>
										<div class="task-options-item-open-inner">
											<?=$block['HTML']?>
										</div>
									</div>
								</div>
							<?endif?>
						</div>

					<?endforeach?>

				</div>

			<?endif?>
		</div>

		<?// UN-PINNED DYNAMIC?>
		<?if(count($blocks['DYNAMIC'])):?>

			<div class="js-id-wfr-edit-form-additional task-additional-block <?=($arResult['TEMPLATE_DATA']['ADDITIONAL_DYNAMIC_DISPLAYED'] ? '' : 'hidden')?>">

				<?// generate block link with block names ?>
				<div class="js-id-wfr-edit-form-additional-header task-additional-alt opened">
					<div class="task-additional-alt-more">
						<?=Loc::getMessage('TASKS_TWF_T_ADDITIONAL_OPEN')?>
					</div>
					<div class="task-additional-alt-promo">
						<?foreach($blocks['DYNAMIC'] as $block):?>
							<?if((string) $block['TITLE_SHORT'] != ''):?>
								<span class="task-additional-alt-promo-text"><?=htmlspecialcharsbx($block['TITLE_SHORT']);?></span>
							<?endif?>
						<?endforeach?>
					</div>
				</div>

				<div class="js-id-wfr-edit-form-unchosen-blocks task-options task-options-more">

					<?// put un-chosen?>
					<?foreach($blocks['DYNAMIC'] as $block):?>

						<?
						$blockName = $block['CODE'];
						$blockNameJs = ToLower($block['CODE']);

						$invisibleClass = $state['BLOCKS'][$blockName]['OPENED'] ? '' : 'invisible';
						$pinnedClass = '';
						?>

						<div class="js-id-wfr-edit-form-<?=$blockNameJs?>-block-place wfr-edit-form-block-place">
							<?if(!$state['BLOCKS'][$blockName]['PINNED']):?>
								<div data-block-name="<?=$blockName?>" class="js-id-wfr-edit-form-<?=$blockNameJs?>-block pinable-block task-openable-block task-options-item-<?=$blockNameJs?> <?=$pinableClass?> <?=$invisibleClass?> <?=$pinnedClass?>">
									<div class="task-options-item">
										<span data-target="<?=$blockNameJs?>-block" class="js-id-wfr-edit-form-pinner task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_PINNER_HINT')?>"></span>
										<span class="task-options-item-param"><?=htmlspecialcharsbx($block['TITLE'])?></span>
										<div class="task-options-item-open-inner">
											<?=$block['HTML']?>
										</div>
									</div>
								</div>
							<?endif?>
						</div>

					<?endforeach?>

				</div>
			</div>

		<?endif?>

		<?if($arParams['FOOTER']['IS_ENABLED']):?>

			<div class="js-id-wfr-edit-form-footer webform-buttons pinable-block <?=($state['FLAGS']['FORM_FOOTER_PIN'] ? 'pinned' : '')?>">

				<div class="tasks-form-footer-container">

					<?if($arParams['FOOTER']['IS_PINABLE']):?>
						<span class="js-id-wfr-edit-form-pin-footer task-option-fixedbtn" title="<?=Loc::getMessage('TASKS_TASK_TEMPLATE_COMPONENT_TEMPLATE_PINNER_HINT')?>"></span>
					<?endif?>

					<?foreach($arParams['FOOTER']['BUTTONS'] as $button):?>

						<?if($button['TYPE'] == 'LINK'):?>
							<a href="<?=htmlspecialcharsbx($button['URL'])?>" class="js-id-wfr-edit-form-cancel-button webform-button-link"><?=htmlspecialcharsbx($button['TEXT'])?></a>
						<?else:?>
							<button class="js-id-wfr-edit-form-submit webform-small-button webform-small-button-accept">
		                        <span class="webform-small-button-text">
			                        <?=htmlspecialcharsbx($button['TEXT'])?>
		                        </span>
							</button>
						<?endif?>

					<?endforeach?>

				</div>
			</div>

		<?endif?>

		<?/*
		<div class="js-id-wfr-edit-form-state">
			<input class="js-id-id-wfr-edit-form-operation" type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[OPERATION]" value="runtime:templateActionSetState" disabled="disabled" />
			<div class="js-id-id-wfr-edit-form-inputs">
				<script data-bx-id="id-wfr-edit-form-block" type="text/html">
					<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[ARGUMENTS][state][BLOCKS][{{NAME}}][PINNED]" value="{{VALUE}}" />
				</script>
				<script data-bx-id="id-wfr-edit-form-flag" type="text/html">
					<input type="hidden" name="<?=htmlspecialcharsbx($inputPrefix)?>[ARGUMENTS][state][FLAGS][{{NAME}}]" value="{{VALUE}}" />
				</script>
			</div>
		</div>
		*/?>

	</div>
	<?$helper->initializeExtension();?>

<?endif?>
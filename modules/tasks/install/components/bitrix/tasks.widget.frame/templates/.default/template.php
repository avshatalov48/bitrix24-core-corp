<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\RestrictionUrl;

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

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
								<?php
									$lockClassName = 'task-options-item-open-inner';
									$onLockClick = '';
									$lockClassStyle = '';
									if ($block['RESTRICTED'] ?? null)
									{
										$lockClassName .= ' tasks-btn-restricted';
										$onLockClick =
											"top.BX.UI.InfoHelper.show('"
											. RestrictionUrl::TASK_LIMIT_OBSERVERS_SLIDER_URL
											. "',{isLimit: true,limitAnalyticsLabels: {module: 'tasks',}});"
										;
										$lockClassStyle = "cursor: pointer;";
									}
								?>
								<div class="<?=$lockClassName?>" onclick="<?=$onLockClick?>" style="<?=$lockClassStyle?>">

									<?=$block['HTML']?>

									<?if (($block['TOGGLE'] ?? null) && count($block['TOGGLE'])):?>
										<span class="task-dashed-link task-dashed-link-add tasks-additional-block-link">
											<?foreach($block['TOGGLE'] as $link):?>
			                                    <span class="js-id-wfr-edit-form-toggler task-dashed-link-inner" data-target="<?=htmlspecialcharsbx(ToLower($link['TARGET']))?>"><?=htmlspecialcharsbx($link['TITLE'])?></span>
			                                <?endforeach?>
										</span>
									<?endif?>

								</div>

								<?if (($block['SUB'] ?? null) && count($block['SUB'])):?>
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
		<?if(count($blocks['DYNAMIC'])):
			$opened = false;
			foreach($blocks['DYNAMIC'] as $block)
			{
				$blockName = $block['CODE'];
				if (!$state['BLOCKS'][$blockName]['PINNED'] && $state['BLOCKS'][$blockName]['OPENED'])
				{
					$opened = true;
					break;
				}
			}
			?>
			<div class="js-id-wfr-edit-form-additional task-additional-block <?=($arResult['TEMPLATE_DATA']['ADDITIONAL_DYNAMIC_DISPLAYED'] ? '' : 'hidden')?><?=($opened ? ' opened' : '') ?>">

				<?// generate block link with block names ?>
				<div class="js-id-wfr-edit-form-additional-header task-additional-alt <?=($opened ? 'opened' : '') ?>">
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
		<?php
		if($arParams['FOOTER']['IS_ENABLED'])
		{
			$APPLICATION->IncludeComponent(
				'bitrix:ui.button.panel',
				'',
				[
					'BUTTONS' => $arParams['FOOTER']['BUTTONS'],
				]
			);
		}
		?>
	</div>
	<?$helper->initializeExtension();?>

<?endif?>
<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

if(!$arResult['CONVERTED'] && $arResult['CAN_CONVERT'])
{
	?>
	<?$arResult['HELPER']->displayFatals();?>
	<?if(!$arResult['HELPER']->checkHasFatals()):?>
		<?$arResult['HELPER']->displayWarnings();?>

		<div id="<?=$arResult['HELPER']->getScopeId()?>" class="tasks">

			<div class="js-id-util-proc-notification-wrap tasks-proc-notification-wrap">
				<div class="js-id-util-proc-notification task-message-label tasks-proc-notification">
					<div class="tasks-proc-notification-text">
						<?=Loc::getMessage('TASKS_TUP_TEMPLATE_PROMPT')?>
					</div>
					<div class="tasks-proc-notification-buttons">
						<div>
							<button class="js-id-util-proc-start tasks-proc-notification-btn-start webform-small-button webform-small-button-transparent">
								<span class="webform-small-button-text"><?=Loc::getMessage('TASKS_TUP_TEMPLATE_START')?></span>
							</button>
							<a href="javascript:void(0);" class="js-id-util-proc-hide-notification tasks-proc-notification-btn-cancel tasks-btn-delete" title="<?=Loc::getMessage('TASKS_TUP_TEMPLATE_CLOSE_NOTIFICATION')?>"></a>
						</div>
					</div>
				</div>
			</div>

			<div class="no-display">
				<div class="js-id-util-proc-popup-content tasks-util-proc-popup-content tasks">

					<div class="tasks-pbar-container">
						<div class="tasks-pbar-bar">
							<div class="js-id-progress-popup-fill tasks-pbar-fill"></div>
							<span class="js-id-progress-popup-percent tasks-pbar-text">0%</span>
						</div>
					</div>
					<div class="js-id-progress-popup-error no-display task-message-label error offset-top">&nbsp;</div>
					<div class="js-id-progress-popup-success no-display task-message-label offset-top"><?=Loc::getMessage('TASKS_TUP_TEMPLATE_SUCCESS')?></div>

				</div>
			</div>

		</div>

		<?$arResult['HELPER']->initializeExtension();?>

	<?endif?>
	<?
}

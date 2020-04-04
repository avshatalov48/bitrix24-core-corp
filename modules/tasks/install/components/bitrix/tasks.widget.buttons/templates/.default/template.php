<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be
?>

<?//$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">

		<?//$helper->displayWarnings();?>

		<span data-bx-id="task-view-b-buttonset">

			<?/*
			<span data-action="" class="task-view-button timer-start webform-small-button webform-small-button-accept">
				<span class="webform-small-button-text">
					Green button
				</span>
			</span>

			<span data-action="" class="task-view-button timer-pause webform-small-button">
				<span class="webform-small-button-icon task-button-icon-pause"></span>
				<span class="webform-small-button-text">
					White button
				</span>
			</span>

			<span data-action="" class="task-view-button disapprove webform-small-button webform-small-button-decline">
				<span class="webform-small-button-text">
					Red button
				</span>
			</span>
			*/?>

			<?foreach($arResult['BUTTONS'] as $button):?>

				<?if(!$button['ACTIVE']):?>
					<?continue;?>
				<?endif?>

				<?if($button['TYPE'] == 'link'):?>
					<a href="<?=htmlspecialcharsbx($button['URL'])?>" class="task-view-button edit webform-small-button-link task-button-edit-link">
						<?=htmlspecialcharsbx($button['TITLE'])?>
					</a>
				<?elseif($button['TYPE'] == 'group'):?>
					<span class="js-id-buttons-group task-more-button webform-small-button webform-small-button-transparent" data-code="<?=htmlspecialcharsbx($button['CODE'])?>">
						<span class="webform-small-button-text">
							<?=htmlspecialcharsbx($button['TITLE'])?>
						</span>
					</span>
				<?endif?>

			<?endforeach?>

		</span>

	</div>

	<?$helper->initializeExtension();?>

<?endif?>
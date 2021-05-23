<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

if (!$arResult['IFRAME'])
{
	?>
	<div class="cashbox-page-menu-sidebar">
		<?$APPLICATION->ShowViewContent("left-panel");?>
	</div>
	<?
}

// menu
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrappermenu',
	"",
	array(
		'TITLE' => Loc::getMessage('SC_MENU_TITLE'),
		'ITEMS' => $arResult['CONFIG_MENU'],
	)
);
?>

<div id="salescenter-wrapper" class="salescenter-wrapper <?=($arResult['CASHBOX_ID']) ? '' : 'salescenter-wrapper-template'?>">
	<?if (!$arResult['CASHBOX_ID']):?>
		<div id="salescenter-cashbox-info">
			<div class="ui-mb-15 ui-p-15 ui-bg-color-white">
				<div class="salescenter-main-header">

					<div class="salescenter-main-header-left-block">
						<div class="salescenter-logo-container">
							<div class="salescenter-atol-icon ui-icon" style="width:97px;"><i></i></div>
						</div>
					</div>

					<div class="salescenter-main-header-right-block">

						<div class="ui-title-3 ui-mb-15"><?=Loc::getMessage("SC_CASHBOX_ATOL_TITLE")?></div>
						<hr class="ui-hr ui-mb-15">
						<div class="ui-text-2 ui-mb-20"><?=Loc::getMessage("SC_CASHBOX_ATOL_DESCRITION")?></div>
						<div class="salescenter-button-container ui-pt-20 ui-mb-20">
							<button class="ui-btn ui-btn-md ui-btn-primary" id="bx-salescenter-connect-button"><?=Loc::getMessage("SC_ADD_CASHBOX_BUTTOM")?></button>
						</div>

					</div>

				</div>
			</div>

			<div class="ui-mb-15 ui-p-15 ui-bg-color-white">
				<div class="ui-title-4"><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_TITLE")?></div>
				<hr class="ui-hr">
				<ul class="ui-list ui-color-medium ui-list-icon">
					<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM1")?></li>
					<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM2")?></li>
					<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM3")?></li>
					<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM4")?></li>
					<li><?=Loc::getMessage("SC_CASHBOX_INSTRUCTION_ITEM5")?></li>
				</ul>
			</div>
		</div>
	<?endif;?>

	<form method="post" action="<?=$arResult['ACTION_URL']?>">
		<?
		// contents
		foreach ($arResult['CONFIG_MENU'] as $key => $menuItem)
		{
			$cashboxPageClass = (($key == $arResult['PAGE']) ? "salescenter-cashbox-page-show" : ".salescenter-cashbox-page-hide salescenter-cashbox-page-invisible");

			?>
			<div data-cashbox-page="<?=$key?>" class="<?=$cashboxPageClass?>">
				<?include $menuItem['PAGE'];?>
				<div data-cashbox-title="<?=$menuItem['NAME']?>" class="salescenter-cashbox-page-invisible"></div>
			</div>
			<?
		}
		?>

		<div id="salescenter-cashbox-buttons">
			<?
			$APPLICATION->IncludeComponent(
				'bitrix:ui.button.panel',
				"",
				array(
					'BUTTONS' => ['save', 'cancel' => $arParams['SALESCENTER_DIR']],
					'ALIGN' => "center"
				),
				false
			);
			?>
		</div>
	</form>
</div>

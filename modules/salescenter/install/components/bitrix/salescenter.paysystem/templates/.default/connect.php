<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

if (!$arResult['IFRAME'])
{
	?>
	<div class="paysystem-page-menu-sidebar">
		<?$APPLICATION->ShowViewContent("left-panel");?>
	</div>
	<?
}

// menu
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrappermenu',
	"",
	array(
		'TITLE' => Loc::getMessage('SALESCENTER_SP_MENU_TITLE'),
		'ITEMS' => $arResult['CONFIG_MENU'],
	)
);
?>

<div id="salescenter-wrapper" class="salescenter-wrapper <?=($arResult['PAYSYSTEM_ID']) ? '' : 'salescenter-wrapper-template'?>">
	<?if (!$arResult['PAYSYSTEM_ID']):?>
		<div id="salescenter-paysystem-info">
			<div class="ui-mb-15 ui-p-15 ui-bg-color-white">
				<div class="salescenter-main-header">

					<div class="salescenter-main-header-left-block">
						<div class="salescenter-logo-container">
							<div class="salescenter-atol-icon ui-icon" style="width:97px;"><i></i></div>
						</div>
					</div>

					<div class="salescenter-main-header-right-block">

						<div class="ui-title-3 ui-mb-15"><?=Loc::getMessage("SALESCENTER_SP_PAYMENT_ALFABANK_TITLE")?></div>
						<hr class="ui-hr ui-mb-15">
						<div class="ui-text-2 ui-mb-20"><?=Loc::getMessage("SALESCENTER_SP_PAYMENT_ALFABANK_DESCRIPTION")?></div>
						<div class="salescenter-button-container ui-pt-20 ui-mb-20">
							<button class="ui-btn ui-btn-md ui-btn-primary" id="bx-salescenter-connect-button"><?=Loc::getMessage("SALESCENTER_SP_ADD_PAYMENT_BUTTOM")?></button>
						</div>

					</div>

				</div>
			</div>
		</div>
	<?endif;?>

	<form method="post" action="<?=$arResult['ACTION_URL']?>">
		<?
		// contents
		foreach ($arResult['CONFIG_MENU'] as $key => $menuItem)
		{
			$paymentPageClass = (($key == $arResult['PAGE']) ? "salescenter-paysystem-page-show" : "salescenter-paysystem-page-hide salescenter-paysystem-page-invisible");

			?>
			<div data-paysystem-page="<?=$key?>" class="<?=$paymentPageClass?>">
				<?include $menuItem['PAGE'];?>
				<div data-paysystem-title="<?=$menuItem['NAME']?>" class="salescenter-paysystem-page-invisible"></div>
			</div>
			<?
			if (isset($menuItem['CHILDREN']) && !empty($menuItem['CHILDREN']))
			{
				foreach ($menuItem['CHILDREN'] as $childKey => $childMenuItem)
				{
					?>
					<div data-paysystem-page="<?=$childKey?>" class="<?=$paymentPageClass?>">
						<?include $childMenuItem['PAGE'];?>
						<div data-paysystem-title="<?=$childMenuItem['NAME']?>" class="salescenter-paysystem-page-invisible"></div>
					</div>
					<?
				}
			}
		}
		?>

		<div id="salescenter-paysystem-buttons">
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

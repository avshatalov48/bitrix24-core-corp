<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'spotlight',
	'ui.banner-dispatcher',
]);

/**
 * @var array $arResult
 */


$frame = $this->createFrame()->begin("");
?>
<div id="intranet-settings-widget-open-button_<?=$arResult['NUMBER']?>" class="intranet-settings-widget__logo-btn" data-role="holding_widget_pointer">
	<i class="ui-icon-set --settings"></i>
</div>

<script>
	BX.ready(() => {
		const button = BX('intranet-settings-widget-open-button_<?=$arResult['NUMBER']?>');
		const bindCallbackInitial = () => {
			BX.unbindAll(button);
			BX.Intranet.SettingsWidgetLoader.init({
				isRequisite: <?= Json::encode($arResult['IS_REQUISITE']) ?>,
				isBitrix24: <?= Json::encode($arResult['IS_BITRIX24']) ?>,
				isAdmin: <?= Json::encode($arResult['IS_ADMIN']) ?>,
				isMainPageAvailable: <?= Json::encode($arResult['IS_WIDGET_MENU_ITEM_SHOW']) ?>,
			}).showOnce(button);
		}
		BX.bind(button, 'click', bindCallbackInitial);
		<?php if ($arResult['SPOTLIGHT'] || $arResult['SPOTLIGHT_AFTER_CREATE']): ?>
		<?php
		if ($arResult['SPOTLIGHT'])
		{
			$spotlightId = 'intranet-main-page';
			$spotlightTitle = Loc::getMessage('INTRANET_SETTINGS_WIDGET_SPOTLIGHT_TITLE');
			$spotlightDescription = Loc::getMessage('INTRANET_SETTINGS_WIDGET_SPOTLIGHT_DESCRIPTION');
		}
		if ($arResult['SPOTLIGHT_AFTER_CREATE'])
		{
			$spotlightId = 'intranet-main-page-after-create';
			$spotlightTitle = Loc::getMessage('INTRANET_SETTINGS_WIDGET_SPOTLIGHT_AFTER_CREATE_TITLE');
			$spotlightDescription = Loc::getMessage('INTRANET_SETTINGS_WIDGET_SPOTLIGHT_AFTER_CREATE_DESCRIPTION_MSGVER_1');
		}
		?>
			if (window.getComputedStyle(button).visibility !== 'hidden')
			{
				const content = BX.Tag.render`
					<div class="intranet-settings-widget-spotlight__wrapper">
						<span class="intranet-settings-widget-spotlight__title"><?= \CUtil::JSEscape($spotlightTitle)?></span>
						<span class="intranet-settings-widget-spotlight__description"><?= \CUtil::JSEscape($spotlightDescription) ?></span>
					</div>
				`;
				const spotlightId = '<?= \CUtil::JSEscape($spotlightId)?>';
				BX.UI.BannerDispatcher.toQueue((onDone) => {
					const spotlight = new BX.SpotLight({
						id: spotlightId,
						targetElement: button,
						top: 2,
						content: content,
						targetVertex: 'middle-center',
						lightMode: true,
						autoSave: true,
						events: {
							onClose: () => {
								const buttons = document.querySelectorAll('.intranet-settings-widget__logo-btn');
								const spotlights = [];
								buttons.forEach(button => {
									if (button.spotlight && button.spotlight !== spotlight)
									{
										spotlights.push(button.spotlight);
									}

									delete button.spotlight;
								});

								spotlights.forEach(sp => sp.destroy());
								onDone();
							}
						},
					});

					button.spotlight = spotlight;
					spotlight.show();
					setTimeout(() => {
						spotlight.getPopup().show();
						spotlight.save();
					}, 1500);
				});
			}
		<?php endif; ?>
	});
</script>
<?php
$frame->end();

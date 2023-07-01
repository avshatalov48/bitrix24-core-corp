<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

Extension::load(
	[
		'ui.buttons',
		'ui.alerts',
	]
);
?>
<script>
	top.BX.loadCSS([
		'<?= CUtil::JSescape($templateFolder) ?>/style.css',
	]);
</script>

<style>#workarea-content {background: transparent !important;}</style>
<div class="market-app-install-wrapper">
	<div class="market-app-install-inner">
		<?php if (is_array($arParams['RIGHTS'])) : ?>
			<div class="market-app-install-inner-rights">
				<div class="market-app-install-inner-right-list-title">
					<?=Loc::getMessage('REST_MARKETPLACE_INSTALL_REQUIRED_RIGHTS')?>
				</div>
				<div class="market-app-install-inner-rights-list">
					<?php foreach ($arParams['RIGHTS'] as $key => $scope): ?>
						<div class="market-app-install-inner-rights-item">
							<div class="market-app-install-inner-rights-item-header">
								<div class="market-app-install-inner-rights-item-title"><?= htmlspecialcharsbx($scope['TITLE']) ?></div>
							</div>
							<div class="market-app-install-inner-rights-item-description"><?= htmlspecialcharsbx($scope['DESCRIPTION']) ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
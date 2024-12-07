<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load([
	'sidepanel',
	'ui.buttons',
	'ui.icons',
	'ui.notification',
	'ui.fonts.opensans',
	'biconnector.grid',
	'spotlight',
	'ui.tour',
	'ui.switcher',
]);

$APPLICATION->SetTitle(Loc::getMessage('CT_BBKL_TITLE'));
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-background no-all-paddings pagetitle-toolbar-field-view');

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult['GRID'],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>
<script>
	BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);

	<?php if ($arResult['IS_AVAILABLE_ONBOARDING'] ?? null): ?>
		BX.ready(() => {
			const grid = new BX.BIConnector.KeysGrid({
				bindElement: document.querySelector("[data-btn-uniqid='<?= CUtil::JSEscape($arResult['ONBOARDING_BUTTON_ID']) ?>']"),
				article: '17606238',
			});
			grid.showOnboarding();
		});
	<?php endif; ?>
</script>
<?php
if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '');
}

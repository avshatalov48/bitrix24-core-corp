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

CModule::includeModule('biconnector');
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load([
	'sidepanel',
	'ui.buttons',
	'ui.icons',
	'biconnector.grid',
	'spotlight',
	'ui.tour',
]);

$APPLICATION->SetTitle(Loc::getMessage('CT_BBDL_TITLE'));
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-background no-all-paddings pagetitle-toolbar-field-view');

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult['GRID'],
	$component,
	['HIDE_ICONS' => 'Y']
);

if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '');
}

?>
	<script>
		BX.message(<?= Json::encode(Loc::loadLanguageFile(__FILE__)) ?>);
		<?php if ($arResult['IS_AVAILABLE_ONBOARDING'] ?? null): ?>
			BX.ready(() => {
				const grid = new BX.BIConnector.DashboardGrid({
					bindElement: document.querySelector("[data-btn-uniqid='<?= CUtil::JSEscape($arResult['ONBOARDING_BUTTON_ID']) ?>']"),
					article: '17606238',
				});
				grid.showOnboarding();
			});
		<?php endif; ?>
	</script>
<?php

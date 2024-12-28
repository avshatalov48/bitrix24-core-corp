<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var \CMain $APPLICATION */
/** @var array $arParams */
/** @var string $templateFolder */
/** @var array $arResult */

Loc::loadLanguageFile(__DIR__ . '/template.php');

\Bitrix\Main\UI\Extension::load([
	'crm.entity-editor',
	'sign.v2.ui.tokens',
	'sign.onboarding',
]);

$showWelcomeTour = $arResult['SHOW_WELCOME_TOUR'] ?? false;
$showByEmployeeTour = $arResult['SHOW_BY_EMPLOYEE_TOUR'] ?? false;
$showBtnCreateTour = $arResult['SHOW_TOUR_BTN_CREATE'] ?? false;
$portalRegion = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
$tourId = $arResult['TOUR_ID'] ?? null;

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.item.kanban',
		'POPUP_COMPONENT_PARAMS' => [
			'entityTypeId' => $arResult['ENTITY_TYPE_ID'],
			'categoryId' => '0',
			'performance' => [
				'layoutFooterEveryItemRender' => 'Y',
			],
		],
		'USE_UI_TOOLBAR' => 'Y',
	],
	$this->getComponent()
);

if ($arResult['SHOW_TARIFF_SLIDER'] ?? false):
?>
<script>
	BX.ready(function()
	{
		const el = document.getElementsByClassName('sign-b2e-js-tarriff-slider-trigger');
		if (el && el[0])
		{
			BX.bind(el[0], 'click', function()
			{
				top.BX.UI.InfoHelper.show('limit_office_e_signature');
			});
		}
	});
</script>
<?php
endif;
?>

<?php if ($showWelcomeTour): ?>
	<script>
		BX.ready(() => {
			new BX.Sign.Onboarding().startB2eWelcomeOnboarding({
				region: '<?= CUtil::JSescape($portalRegion) ?>',
				tourId: '<?= CUtil::JSescape($tourId) ?>',
			});
		});
	</script>
<?php endif; ?>

<?php if ($showByEmployeeTour): ?>
	<script>
		BX.ready(() => {
			(new BX.Sign.Onboarding()).startB2eByEmployeeOnboarding({
				region: '<?= CUtil::JSescape($portalRegion) ?>',
				tourId: '<?= CUtil::JSescape($tourId) ?>',
			});
		});
	</script>
<?php endif; ?>

<?php if ($showBtnCreateTour): ?>
	<script>
		BX.ready(() => {
			(new BX.Sign.Onboarding()).startB2eFallbackOnboarding({
				region: '<?= CUtil::JSescape($portalRegion) ?>',
				tourId: '<?= CUtil::JSescape($tourId) ?>',
			});
		});
	</script>
<?php endif; ?>

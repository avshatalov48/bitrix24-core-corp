<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var \CMain $APPLICATION */
/** @var array $arParams */
/** @var string $templateFolder */
/** @var array $arResult */

Loc::loadLanguageFile(__DIR__ . '/template.php');

Extension::load([
	'crm.entity-editor',
	'sign.v2.ui.tokens',
	'sign.onboarding',
	'sign.v2.grid.b2e.templates',
]);

$portalRegion = $arResult['PORTAL_REGION'] ?? null;
$byEmployeeEnabled = $arResult['BY_EMPLOYEE_ENABLED'] ?? false;
$showWelcomeTour = $arResult['SHOW_WELCOME_TOUR'] ?? false;
$showWelcomeTourWithTestSigning = $arResult['SHOW_WELCOME_TOUR_TEST_SIGNING'] ?? false;
$showOnboardingBanner = $arResult['SHOW_ONBOARDING_SIGNING_BANNER'] ?? false;
$company = $arResult['COMPANY'] ?? null;
$hasCompanies = $company !== null;
$showTariffSlider = $arResult['SHOW_TARIFF_SLIDER'] ?? false;
$tourId = $arResult['TOUR_ID'] ?? null;
$canCreateDocument = $arResult['CAN_ADD_DOCUMENT'] ?? false;
$canEditDocument = $arResult['CAN_EDIT_DOCUMENT'] ?? false;

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
		BX.bindDelegate(
			document.body,
			'click',
			{
				className: 'sign-b2e-js-tarriff-slider-trigger'
			},
			function(event) {
				event.preventDefault();
				event.stopPropagation();

				top.BX.UI.InfoHelper.show('limit_office_e_signature');

				return false;
			}
		);
	});
</script>
<?php
endif;
?>

<?php if ($showWelcomeTour): ?>
	<script>
		BX.ready(() => {
			new BX.Sign.Onboarding().startB2eWelcomeOnboarding(
				{
					region: '<?= CUtil::JSescape($portalRegion) ?>',
					byEmployeeEnabled: <?= $byEmployeeEnabled ? 'true' : 'false' ?>,
				}
			);
		});
	</script>
<?php endif; ?>

<?php if ($showWelcomeTourWithTestSigning): ?>
	<script>
		BX.ready(() => {
			new BX.Sign.Onboarding().startB2eWelcomeOnboardingWithTestSigning(
				{
					region: '<?= CUtil::JSescape($portalRegion) ?>',
					hasCompanies: <?= $hasCompanies ? 'true' : 'false' ?>,
					byEmployeeEnabled: <?= $byEmployeeEnabled ? 'true' : 'false' ?>,
					showTariffSlider: <?= $showTariffSlider ? 'true' : 'false' ?>,
					canEditDocument: <?= $canEditDocument ? 'true' : 'false' ?>,
					canCreateDocument: <?= $canCreateDocument ? 'true' : 'false' ?>,
				}
			);
		});
	</script>
<?php endif; ?>

<?php if ($showOnboardingBanner): ?>
<script>
	BX.ready(() => {
		(new BX.Sign.Onboarding()).showTestSigningBanner(
			{
				hasCompanies: <?= $hasCompanies ? 'true' : 'false' ?>,
				showTariffSlider: <?= $showTariffSlider ? 'true' : 'false' ?>,
			}
		);
	});
</script>
<?php endif; ?>
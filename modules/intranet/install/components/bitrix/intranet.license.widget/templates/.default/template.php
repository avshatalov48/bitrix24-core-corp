<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
$isExperimentalTrialTemplate = !in_array(\CBitrix24::getPortalZone(), ['ru', 'kz', 'by'])
	&& \Bitrix\Main\Config\Option::get('intranet', 'abtest:license.widget', 'N') === 'Y';
if ($isExperimentalTrialTemplate)
{
	$arResult['buttonName'] = Loc::getMessage('INTRANET_LICENSE_WIDGET_START_FREE_TRIAL');
}

\Bitrix\Main\UI\Extension::load(['ui.button', 'ui.vue', 'ui.feedback.form']);

$frame = $this->createFrame()->begin();
?>

<span data-id="licenseWidgetWrapper">
	<button class="<?=$arResult['buttonClassName']?>">
		<?=$arResult['buttonName']?>
	</button>
</span>

<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);

	BX.ready(function () {
		BX.Intranet.LicenseWidget = new BX.Intranet.LicenseWidget({
			wrapper: document.querySelector("[data-id='licenseWidgetWrapper']"),
			isFreeLicense: '<?=$arResult['isFreeLicense'] ? 'Y' : 'N'?>',
			isDemoLicense: '<?=$arResult['isDemoLicense'] ? 'Y' : 'N'?>',
			isAutoPay: '<?=$arResult['isAutoPay'] ? 'Y' : 'N'?>',
			licenseType: '<?=$arResult['licenseType']?>',
			isLicenseAlmostExpired: '<?=$arResult['isLicenseAlmostExpired'] ? 'Y' : 'N'?>',
			isLicenseExpired: '<?=$arResult['isLicenseExpired'] ? 'Y' : 'N'?>',
			isAlmostLocked: '<?=$arResult['isAlmostLocked'] ? 'Y' : 'N'?>',
			isExperimentalTemplate: '<?=$isExperimentalTrialTemplate ? 'Y': 'N'?>',
		});
	});
</script>

<?php $frame->beginStub(); ?>

<button class="<?=$arResult['buttonClassName']?>">
	<?=$arResult['buttonName']?>
</button>

<?php $frame->end(); ?>

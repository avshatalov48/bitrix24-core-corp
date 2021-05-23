<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.button', 'ui.vue']);

$frame = $this->createFrame()->begin('');
?>

<span data-id="licenseWidgetWrapper"></span>

<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);

	BX.ready(function () {
		BX.Intranet.LicenseWidget = new BX.Intranet.LicenseWidget({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName() ?>',
			wrapper: document.querySelector("[data-id='licenseWidgetWrapper']"),
			isFreeLicense: '<?=$arResult['isFreeLicense'] ? 'Y' : 'N'?>',
			isDemoLicense: '<?=$arResult['isDemoLicense'] ? 'Y' : 'N'?>',
			isAutoPay: '<?=$arResult['isAutoPay'] ? 'Y' : 'N'?>',
			licenseType: '<?=$arResult['licenseType']?>',
			isLicenseAlmostExpired: '<?=$arResult['isLicenseAlmostExpired'] ? 'Y' : 'N'?>',
			isLicenseExpired: '<?=$arResult['isLicenseExpired'] ? 'Y' : 'N'?>',
		});
	});
</script>

<?$frame->end(); ?>

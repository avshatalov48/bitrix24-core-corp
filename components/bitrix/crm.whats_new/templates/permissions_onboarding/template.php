<?php

use Bitrix\Main\UI\Extension;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Extension::load('crm.perms.onboarding-popup');

?>

<script>
	BX.ready(() => {
		const onboardingPopup = new BX.Crm.Perms.OnboardingPopup({
			closeOptionCategory: '<?= $arParams['CLOSE_OPTION_CATEGORY'] ?? '' ?>',
			closeOptionName: '<?= $arParams['CLOSE_OPTION_NAME'] ?? '' ?>',
		});

		onboardingPopup.show();
	});
</script>

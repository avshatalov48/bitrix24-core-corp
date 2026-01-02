<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\UI\Extension;

Extension::load('crm.repeat-sale.onboarding-popup');

?>

<script>
	BX.ready(() => {
		const onboardingPopup = new BX.Crm.RepeatSale.OnboardingPopup({
			closeOptionCategory: '<?= $arParams['CLOSE_OPTION_CATEGORY'] ?? '' ?>',
			closeOptionName: '<?= $arParams['CLOSE_OPTION_NAME'] ?? '' ?>',
			analytics: <?= \Bitrix\Main\Web\Json::encode($arParams['OPTIONS']['ANALYTICS'] ?? []) ?>,
		});

		onboardingPopup.show();
	});
</script>
